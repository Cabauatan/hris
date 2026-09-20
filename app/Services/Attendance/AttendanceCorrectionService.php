<?php

namespace App\Services\Attendance;

use App\Models\AttendanceCorrectionRequest;
use App\Models\DailyAttendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceCorrectionService
{
    public function paginate(
        int $perPage = 15,
        ?int $employeeId = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): LengthAwarePaginator {
        return AttendanceCorrectionRequest::query()
            ->with([
                'employee',
                'dailyAttendance',
            ])
            ->when(
                $employeeId !== null,
                fn ($query) => $query->where(
                    'employee_id',
                    $employeeId
                )
            )
            ->when(
                $status !== null,
                fn ($query) => $query->where(
                    'status',
                    $status
                )
            )
            ->when(
                $dateFrom !== null,
                fn ($query) => $query->whereDate(
                    'attendance_date',
                    '>=',
                    Carbon::parse($dateFrom)->toDateString()
                )
            )
            ->when(
                $dateTo !== null,
                fn ($query) => $query->whereDate(
                    'attendance_date',
                    '<=',
                    Carbon::parse($dateTo)->toDateString()
                )
            )
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Employee submits a correction request.
     *
     * Original attendance values are captured as snapshots.
     */
    public function submit(
        Employee $employee,
        array $data
    ): AttendanceCorrectionRequest {
        return DB::transaction(function () use ($employee, $data) {
            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($employee->id);

            $date = Carbon::parse(
                $data['attendance_date']
            )->toDateString();

            $attendance = DailyAttendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $date)
                ->lockForUpdate()
                ->first();

            /*
             * daily_attendance_id is nullable in our architecture,
             * so a correction may still be requested when no generated
             * DailyAttendance row exists yet.
             */

            $this->ensureNoPendingRequest(
                $employee,
                $date
            );

            $requestedIn = isset($data['requested_in'])
                && $data['requested_in'] !== null
                    ? Carbon::parse($data['requested_in'])
                    : null;

            $requestedOut = isset($data['requested_out'])
                && $data['requested_out'] !== null
                    ? Carbon::parse($data['requested_out'])
                    : null;

            $this->validateRequestedTimes(
                $requestedIn,
                $requestedOut
            );

            return AttendanceCorrectionRequest::create([
                'employee_id' => $employee->id,
                'daily_attendance_id' => $attendance?->id,
                'attendance_date' => $date,

                /*
                 * Snapshot what the system currently knows.
                 */
                'original_in' => $attendance?->actual_in,
                'original_out' => $attendance?->actual_out,

                /*
                 * Employee-requested corrected values.
                 */
                'requested_in' => $requestedIn,
                'requested_out' => $requestedOut,

                'reason' => $data['reason'],
                'status' => 'pending',
            ]);
        });
    }

    /**
     * Apply an already-authorized approval.
     *
     * Authorization itself belongs to Policy/ApprovalService later.
     */
    public function approve(
        AttendanceCorrectionRequest $request,
        User $actedBy,
        ?string $remarks = null
    ): AttendanceCorrectionRequest {
        return DB::transaction(function () use (
            $request,
            $actedBy,
            $remarks
        ) {
            $request = AttendanceCorrectionRequest::query()
                ->lockForUpdate()
                ->findOrFail($request->id);

            $this->ensurePending($request);

            /*
             * Critical integrity check:
             * a linked DailyAttendance must belong to the same employee.
             */
            $attendance = null;

            if ($request->daily_attendance_id !== null) {
                $attendance = DailyAttendance::query()
                    ->lockForUpdate()
                    ->find($request->daily_attendance_id);

                if (! $attendance) {
                    throw ValidationException::withMessages([
                        'daily_attendance_id' =>
                            'The linked attendance record no longer exists.',
                    ]);
                }

                if (
                    $attendance->employee_id
                    !== $request->employee_id
                ) {
                    throw ValidationException::withMessages([
                        'daily_attendance_id' =>
                            'The linked attendance record belongs to another employee.',
                    ]);
                }

                if (
                    Carbon::parse($attendance->attendance_date)
                        ->toDateString()
                    !== Carbon::parse($request->attendance_date)
                        ->toDateString()
                ) {
                    throw ValidationException::withMessages([
                        'daily_attendance_id' =>
                            'The linked attendance record does not match the correction date.',
                    ]);
                }
            }

            /*
             * If no DailyAttendance existed when submitted,
             * resolve/create the employee-date row now.
             */
            if (! $attendance) {
                $attendance = DailyAttendance::query()
                    ->where('employee_id', $request->employee_id)
                    ->whereDate(
                        'attendance_date',
                        $request->attendance_date
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $attendance) {
                    $attendance = DailyAttendance::create([
                        'employee_id' => $request->employee_id,
                        'attendance_date' =>
                            $request->attendance_date,
                    ]);
                }

                $request->daily_attendance_id = $attendance->id;
            }

            $requestedIn = $request->requested_in
                ? Carbon::parse($request->requested_in)
                : null;

            $requestedOut = $request->requested_out
                ? Carbon::parse($request->requested_out)
                : null;

            $this->validateRequestedTimes(
                $requestedIn,
                $requestedOut
            );

            /*
             * Raw TimeLog is deliberately untouched.
             */
            $attendance->update([
                'actual_in' => $requestedIn,
                'actual_out' => $requestedOut,
            ]);

            /*
             * Mark request approved.
             *
             * Exact reviewer column names must match our migration.
             */
            $request->status = 'approved';

            if (
                array_key_exists(
                    'reviewed_by_user_id',
                    $request->getAttributes()
                )
            ) {
                $request->reviewed_by_user_id = $actedBy->id;
            }

            if (
                array_key_exists(
                    'reviewed_at',
                    $request->getAttributes()
                )
            ) {
                $request->reviewed_at = now();
            }

            if (
                $remarks !== null
                && array_key_exists(
                    'review_remarks',
                    $request->getAttributes()
                )
            ) {
                $request->review_remarks = $remarks;
            }

            $request->save();

            return $request->refresh();
        });
    }

    public function reject(
        AttendanceCorrectionRequest $request,
        User $actedBy,
        string $remarks
    ): AttendanceCorrectionRequest {
        return DB::transaction(function () use (
            $request,
            $actedBy,
            $remarks
        ) {
            $request = AttendanceCorrectionRequest::query()
                ->lockForUpdate()
                ->findOrFail($request->id);

            $this->ensurePending($request);

            $request->status = 'rejected';

            if (
                array_key_exists(
                    'reviewed_by_user_id',
                    $request->getAttributes()
                )
            ) {
                $request->reviewed_by_user_id = $actedBy->id;
            }

            if (
                array_key_exists(
                    'reviewed_at',
                    $request->getAttributes()
                )
            ) {
                $request->reviewed_at = now();
            }

            if (
                array_key_exists(
                    'review_remarks',
                    $request->getAttributes()
                )
            ) {
                $request->review_remarks = $remarks;
            }

            $request->save();

            return $request->refresh();
        });
    }

    public function cancel(
        AttendanceCorrectionRequest $request,
        Employee $employee
    ): AttendanceCorrectionRequest {
        return DB::transaction(function () use (
            $request,
            $employee
        ) {
            $request = AttendanceCorrectionRequest::query()
                ->lockForUpdate()
                ->findOrFail($request->id);

            if ($request->employee_id !== $employee->id) {
                throw ValidationException::withMessages([
                    'request' =>
                        'The correction request does not belong to this employee.',
                ]);
            }

            $this->ensurePending($request);

            $request->update([
                'status' => 'cancelled',
            ]);

            return $request->refresh();
        });
    }

    private function ensurePending(
        AttendanceCorrectionRequest $request
    ): void {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' =>
                    'Only pending attendance correction requests may be changed.',
            ]);
        }
    }

    private function ensureNoPendingRequest(
        Employee $employee,
        string $date
    ): void {
        $exists = AttendanceCorrectionRequest::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $date)
            ->where('status', 'pending')
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'attendance_date' =>
                    'A pending attendance correction already exists for this date.',
            ]);
        }
    }

    private function validateRequestedTimes(
        ?Carbon $requestedIn,
        ?Carbon $requestedOut
    ): void {
        if (
            $requestedIn
            && $requestedOut
            && $requestedOut->lte($requestedIn)
        ) {
            throw ValidationException::withMessages([
                'requested_out' =>
                    'The requested time out must be later than the requested time in.',
            ]);
        }
    }
}