<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OvertimeService
{
    public function paginate(
        int $perPage = 15,
        ?int $employeeId = null,
        ?string $status = null,
        ?string $dateFrom = null,
        ?string $dateTo = null
    ): LengthAwarePaginator {
        return OvertimeRequest::query()
            ->with([
                'employee',
                'ctoEarnings',
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
                    'overtime_date',
                    '>=',
                    Carbon::parse($dateFrom)->toDateString()
                )
            )
            ->when(
                $dateTo !== null,
                fn ($query) => $query->whereDate(
                    'overtime_date',
                    '<=',
                    Carbon::parse($dateTo)->toDateString()
                )
            )
            ->latest('overtime_date')
            ->paginate($perPage);
    }

    /**
     * Submit an overtime request.
     *
     * requested_minutes is always computed by the backend.
     */
    public function submit(
        Employee $employee,
        array $data
    ): OvertimeRequest {
        return DB::transaction(function () use ($employee, $data) {
            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($employee->id);

            if (! $employee->is_active) {
                throw ValidationException::withMessages([
                    'employee_id' =>
                        'An inactive employee cannot submit an overtime request.',
                ]);
            }

            $start = Carbon::parse($data['start_at']);
            $end = Carbon::parse($data['end_at']);

            $this->validateRange($start, $end);

            /*
             * overtime_date represents the work date to which
             * the overtime belongs, not necessarily the calendar
             * date of end_at.
             */
            $overtimeDate = Carbon::parse(
                $data['overtime_date']
            )->toDateString();

            $this->validateOvertimeDate(
                $overtimeDate,
                $start
            );

            $requestedMinutes = $start->diffInMinutes($end);

            if ($requestedMinutes <= 0) {
                throw ValidationException::withMessages([
                    'end_at' =>
                        'The overtime duration must be greater than zero.',
                ]);
            }

            $this->ensureNoPendingDuplicate(
                $employee,
                $overtimeDate,
                $start,
                $end
            );

            return OvertimeRequest::create([
                'employee_id' => $employee->id,

                'overtime_date' => $overtimeDate,
                'start_at' => $start,
                'end_at' => $end,

                'requested_minutes' => $requestedMinutes,
                'approved_minutes' => null,

                'reason' => $data['reason'],
                'status' => 'pending',
            ]);
        });
    }

    /**
     * Apply an already-authorized approval decision.
     *
     * Generic ApprovalService will later orchestrate authority,
     * delegation, ApprovalAction and audit logging.
     */
    public function approve(
        OvertimeRequest $request,
        int $approvedMinutes,
        User $actedBy,
        ?string $remarks = null
    ): OvertimeRequest {
        return DB::transaction(function () use (
            $request,
            $approvedMinutes,
            $actedBy,
            $remarks
        ) {
            $request = OvertimeRequest::query()
                ->lockForUpdate()
                ->findOrFail($request->id);

            $this->ensurePending($request);

            if ($approvedMinutes < 0) {
                throw ValidationException::withMessages([
                    'approved_minutes' =>
                        'Approved overtime minutes cannot be negative.',
                ]);
            }

            if ($approvedMinutes > $request->requested_minutes) {
                throw ValidationException::withMessages([
                    'approved_minutes' =>
                        'Approved overtime minutes cannot exceed the requested overtime minutes.',
                ]);
            }

            /*
             * Zero approved minutes should normally be represented
             * by rejection rather than an approved zero-minute OT.
             */
            if ($approvedMinutes === 0) {
                throw ValidationException::withMessages([
                    'approved_minutes' =>
                        'Use rejection when no overtime minutes are approved.',
                ]);
            }

            $request->approved_minutes = $approvedMinutes;
            $request->status = 'approved';

            /*
             * Replace these with the exact reviewed/approved columns
             * from our audited migration.
             */
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
        OvertimeRequest $request,
        User $actedBy,
        string $remarks
    ): OvertimeRequest {
        return DB::transaction(function () use (
            $request,
            $actedBy,
            $remarks
        ) {
            $request = OvertimeRequest::query()
                ->lockForUpdate()
                ->findOrFail($request->id);

            $this->ensurePending($request);

            $request->status = 'rejected';
            $request->approved_minutes = null;

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
        OvertimeRequest $request,
        Employee $employee
    ): OvertimeRequest {
        return DB::transaction(function () use (
            $request,
            $employee
        ) {
            $request = OvertimeRequest::query()
                ->lockForUpdate()
                ->findOrFail($request->id);

            if ($request->employee_id !== $employee->id) {
                throw ValidationException::withMessages([
                    'request' =>
                        'The overtime request does not belong to this employee.',
                ]);
            }

            $this->ensurePending($request);

            $request->update([
                'status' => 'cancelled',
            ]);

            return $request->refresh();
        });
    }

    private function validateRange(
        Carbon $start,
        Carbon $end
    ): void {
        if ($end->lte($start)) {
            throw ValidationException::withMessages([
                'end_at' =>
                    'The overtime end must be later than the overtime start.',
            ]);
        }
    }

    private function validateOvertimeDate(
        string $overtimeDate,
        Carbon $start
    ): void {
        /*
         * For V1, overtime_date normally corresponds to the
         * work date/start date.
         *
         * Overnight OT may end on the following calendar date.
         */
        if ($overtimeDate !== $start->toDateString()) {
            throw ValidationException::withMessages([
                'overtime_date' =>
                    'The overtime date must match the overtime work/start date.',
            ]);
        }
    }

    private function ensureNoPendingDuplicate(
        Employee $employee,
        string $overtimeDate,
        Carbon $start,
        Carbon $end
    ): void {
        $exists = OvertimeRequest::query()
            ->where('employee_id', $employee->id)
            ->whereDate('overtime_date', $overtimeDate)
            ->where('status', 'pending')
            ->where(function ($query) use ($start, $end) {
                /*
                 * Existing interval overlaps requested interval:
                 *
                 * existing.start < new.end
                 * AND
                 * existing.end > new.start
                 */
                $query->where('start_at', '<', $end)
                    ->where('end_at', '>', $start);
            })
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'start_at' =>
                    'The overtime period overlaps an existing pending overtime request.',
            ]);
        }
    }

    private function ensurePending(
        OvertimeRequest $request
    ): void {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' =>
                    'Only pending overtime requests may be changed.',
            ]);
        }
    }
}