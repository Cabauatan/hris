<?php

namespace App\Services\Attendance;

use App\Models\DailyAttendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\TimeLog;
use App\Services\Scheduling\EmployeeScheduleAssignmentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceCalculationService
{
    public function __construct(
        private readonly EmployeeScheduleAssignmentService $scheduleService
    ) {
    }

    /**
     * Calculate/recalculate one employee work date.
     */
    public function calculate(
        Employee $employee,
        string $attendanceDate
    ): DailyAttendance {
        return DB::transaction(function () use (
            $employee,
            $attendanceDate
        ) {
            $employee = Employee::query()
                ->findOrFail($employee->id);

            $date = Carbon::parse($attendanceDate)->startOfDay();

            $attendance = DailyAttendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $date->toDateString())
                ->lockForUpdate()
                ->first();

            $assignment = $this->scheduleService->applicableOn(
                $employee,
                $date->toDateString()
            );

            $holiday = $this->holidayOn($date);

            /*
             * No schedule assignment for this work date.
             *
             * We don't invent expected working hours.
             */
            if (! $assignment) {
                return $this->saveAttendance(
                    $attendance,
                    $employee,
                    $date,
                    [
                        'scheduled_in' => null,
                        'scheduled_out' => null,
                        'actual_in' => null,
                        'actual_out' => null,

                        'worked_minutes' => 0,
                        'late_minutes' => 0,
                        'undertime_minutes' => 0,

                        'is_rest_day' => false,
                        'is_holiday' => $holiday !== null,
                        'holiday_id' => $holiday?->id,
                    ]
                );
            }

            $schedule = $assignment->workSchedule;

            /*
             * 1 = Monday ... 7 = Sunday
             */
            $dayOfWeek = $date->isoWeekday();

            $scheduleDay = $schedule->days
                ->firstWhere('day_of_week', $dayOfWeek);

            /*
             * Missing day definition is treated conservatively.
             * A valid schedule should normally have all seven days.
             */
            if (! $scheduleDay) {
                return $this->saveAttendance(
                    $attendance,
                    $employee,
                    $date,
                    [
                        'scheduled_in' => null,
                        'scheduled_out' => null,
                        'actual_in' => null,
                        'actual_out' => null,

                        'worked_minutes' => 0,
                        'late_minutes' => 0,
                        'undertime_minutes' => 0,

                        'is_rest_day' => false,
                        'is_holiday' => $holiday !== null,
                        'holiday_id' => $holiday?->id,
                    ]
                );
            }

            /*
             * Rest day.
             */
            if (! $scheduleDay->is_working_day) {
                return $this->calculateRestDay(
                    $attendance,
                    $employee,
                    $date,
                    $holiday
                );
            }

            $shift = $scheduleDay->shift;

            /*
             * Invalid/incomplete schedule configuration.
             */
            if (! $shift) {
                return $this->saveAttendance(
                    $attendance,
                    $employee,
                    $date,
                    [
                        'scheduled_in' => null,
                        'scheduled_out' => null,
                        'actual_in' => null,
                        'actual_out' => null,

                        'worked_minutes' => 0,
                        'late_minutes' => 0,
                        'undertime_minutes' => 0,

                        'is_rest_day' => false,
                        'is_holiday' => $holiday !== null,
                        'holiday_id' => $holiday?->id,
                    ]
                );
            }

            [$scheduledIn, $scheduledOut] =
                $this->scheduledDateTimes(
                    $date,
                    $shift->start_time,
                    $shift->end_time
                );

            /*
             * Search beyond the exact scheduled boundaries.
             *
             * Example:
             * scheduled 08:00 - 17:00
             * employee clocks 07:45 and 17:12.
             */
            $windowStart = $scheduledIn->copy()->subHours(4);
            $windowEnd = $scheduledOut->copy()->addHours(6);

            $logs = $this->validLogs(
                $employee,
                $windowStart,
                $windowEnd
            );

            [$actualIn, $actualOut] =
                $this->resolveActualInOut(
                    $logs,
                    $scheduledIn,
                    $scheduledOut
                );

            $workedMinutes = $this->workedMinutes(
                $actualIn,
                $actualOut
            );

            $lateMinutes = $this->lateMinutes(
                $actualIn,
                $scheduledIn
            );

            $undertimeMinutes = $this->undertimeMinutes(
                $actualOut,
                $scheduledOut
            );

            return $this->saveAttendance(
                $attendance,
                $employee,
                $date,
                [
                    'scheduled_in' => $scheduledIn,
                    'scheduled_out' => $scheduledOut,

                    'actual_in' => $actualIn,
                    'actual_out' => $actualOut,

                    'worked_minutes' => $workedMinutes,
                    'late_minutes' => $lateMinutes,
                    'undertime_minutes' => $undertimeMinutes,

                    'is_rest_day' => false,
                    'is_holiday' => $holiday !== null,
                    'holiday_id' => $holiday?->id,
                ]
            );
        });
    }

    private function scheduledDateTimes(
        Carbon $date,
        string $startTime,
        string $endTime
    ): array {
        $scheduledIn = Carbon::parse(
            $date->toDateString() . ' ' . $startTime
        );

        $scheduledOut = Carbon::parse(
            $date->toDateString() . ' ' . $endTime
        );

        /*
         * Overnight shift:
         *
         * 22:00 -> 06:00
         *
         * scheduled_out belongs to the following calendar day.
         */
        if ($scheduledOut->lte($scheduledIn)) {
            $scheduledOut->addDay();
        }

        return [
            $scheduledIn,
            $scheduledOut,
        ];
    }

    private function validLogs(
        Employee $employee,
        Carbon $from,
        Carbon $to
    ): Collection {
        return TimeLog::query()
            ->where('employee_id', $employee->id)
            ->where('is_valid', true)
            ->whereBetween('logged_at', [$from, $to])
            ->orderBy('logged_at')
            ->get();
    }

    private function resolveActualInOut(
        Collection $logs,
        Carbon $scheduledIn,
        Carbon $scheduledOut
    ): array {
        if ($logs->isEmpty()) {
            return [null, null];
        }

        /*
         * Prefer explicit IN/OUT logs when the source provides them.
         */
        $inLog = $logs
            ->filter(
                fn (TimeLog $log) =>
                    strtoupper((string) $log->log_type) === 'IN'
            )
            ->sortBy('logged_at')
            ->first();

        $outLog = $logs
            ->filter(
                fn (TimeLog $log) =>
                    strtoupper((string) $log->log_type) === 'OUT'
            )
            ->sortByDesc('logged_at')
            ->first();

        /*
         * Fallback for sources without reliable log_type:
         *
         * first log = possible IN
         * last log  = possible OUT
         *
         * We only use the last log as OUT when there are at least
         * two distinct records.
         */
        if (! $inLog) {
            $inLog = $logs->first();
        }

        if (! $outLog && $logs->count() >= 2) {
            $outLog = $logs->last();
        }

        $actualIn = $inLog
            ? Carbon::parse($inLog->logged_at)
            : null;

        $actualOut = $outLog
            ? Carbon::parse($outLog->logged_at)
            : null;

        /*
         * Don't allow the same single punch to become both IN and OUT.
         */
        if (
            $actualIn
            && $actualOut
            && $actualIn->equalTo($actualOut)
        ) {
            $actualOut = null;
        }

        return [
            $actualIn,
            $actualOut,
        ];
    }

    private function workedMinutes(
        ?Carbon $actualIn,
        ?Carbon $actualOut
    ): int {
        if (! $actualIn || ! $actualOut) {
            return 0;
        }

        if ($actualOut->lte($actualIn)) {
            return 0;
        }

        return $actualIn->diffInMinutes(
            $actualOut
        );
    }

    private function lateMinutes(
        ?Carbon $actualIn,
        Carbon $scheduledIn
    ): int {
        if (! $actualIn) {
            return 0;
        }

        if ($actualIn->lte($scheduledIn)) {
            return 0;
        }

        return $scheduledIn->diffInMinutes(
            $actualIn
        );
    }

    private function undertimeMinutes(
        ?Carbon $actualOut,
        Carbon $scheduledOut
    ): int {
        if (! $actualOut) {
            return 0;
        }

        if ($actualOut->gte($scheduledOut)) {
            return 0;
        }

        return $actualOut->diffInMinutes(
            $scheduledOut
        );
    }

    private function holidayOn(
        Carbon $date
    ): ?Holiday {
        return Holiday::query()
            ->whereDate(
                'holiday_date',
                $date->toDateString()
            )
            ->where('is_active', true)
            ->first();
    }

    private function calculateRestDay(
        ?DailyAttendance $attendance,
        Employee $employee,
        Carbon $date,
        ?Holiday $holiday
    ): DailyAttendance {
        /*
         * For now we preserve the fact that this is a rest day.
         *
         * Rest-day work calculations/premiums belong to the
         * overtime/payroll rules, not this method.
         */
        return $this->saveAttendance(
            $attendance,
            $employee,
            $date,
            [
                'scheduled_in' => null,
                'scheduled_out' => null,
                'actual_in' => null,
                'actual_out' => null,

                'worked_minutes' => 0,
                'late_minutes' => 0,
                'undertime_minutes' => 0,

                'is_rest_day' => true,
                'is_holiday' => $holiday !== null,
                'holiday_id' => $holiday?->id,
            ]
        );
    }

    private function saveAttendance(
        ?DailyAttendance $attendance,
        Employee $employee,
        Carbon $date,
        array $values
    ): DailyAttendance {
        if (! $attendance) {
            $attendance = new DailyAttendance([
                'employee_id' => $employee->id,
                'attendance_date' => $date->toDateString(),
            ]);
        }

        $attendance->fill($values);
        $attendance->save();

        return $attendance->refresh();
    }
}