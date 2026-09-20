<?php

namespace App\Services\Leave;

use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveType;
use App\Services\Scheduling\EmployeeScheduleAssignmentService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class LeaveCalculationService
{
    public function __construct(
        private readonly EmployeeScheduleAssignmentService $scheduleService
    ) {
    }

    public function calculateRequestedDays(
        Employee $employee,
        LeaveType $leaveType,
        string $dateFrom,
        string $dateTo,
        ?string $dayPart = null
    ): string {
        $from = Carbon::parse($dateFrom)->startOfDay();
        $to = Carbon::parse($dateTo)->startOfDay();

        if ($to->lt($from)) {
            throw ValidationException::withMessages([
                'date_to' =>
                    'The leave end date cannot be earlier than the start date.',
            ]);
        }

        $this->validateHalfDay(
            $leaveType,
            $from,
            $to,
            $dayPart
        );

        /*
         * Half-day request is necessarily a single work date.
         */
        if ($dayPart !== null) {
            if (! $this->isLeaveEligibleWorkDay($employee, $from)) {
                throw ValidationException::withMessages([
                    'date_from' =>
                        'Half-day leave must fall on a scheduled working day.',
                ]);
            }

            return '0.50';
        }

        $days = 0;

        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            if ($this->isLeaveEligibleWorkDay($employee, $cursor)) {
                $days++;
            }

            $cursor->addDay();
        }

        return number_format($days, 2, '.', '');
    }

    private function validateHalfDay(
        LeaveType $leaveType,
        Carbon $from,
        Carbon $to,
        ?string $dayPart
    ): void {
        if ($dayPart === null) {
            return;
        }

        if (! $leaveType->allow_half_day) {
            throw ValidationException::withMessages([
                'day_part' =>
                    'The selected leave type does not allow half-day leave.',
            ]);
        }

        if (!$from->isSameDay($to)) {
            throw ValidationException::withMessages([
                'day_part' =>
                    'Half-day leave must start and end on the same date.',
            ]);
        }
    }

    private function isLeaveEligibleWorkDay(
        Employee $employee,
        Carbon $date
    ): bool {
        $assignment = $this->scheduleService->applicableOn(
            $employee,
            $date->toDateString()
        );

        if (!$assignment) {
            return false;
        }

        $scheduleDay = $assignment
            ->workSchedule
            ->days
            ->firstWhere(
                'day_of_week',
                $date->isoWeekday()
            );

        if (
            !$scheduleDay
            || !$scheduleDay->is_working_day
        ) {
            return false;
        }

        $holiday = Holiday::query()
            ->whereDate(
                'holiday_date',
                $date->toDateString()
            )
            ->where('is_active', true)
            ->first();

        /*
         * A holiday configured as a non-working day does not consume
         * employee leave.
         */
        if (
            $holiday
            && !$holiday->is_working_day
        ) {
            return false;
        }

        return true;
    }
}