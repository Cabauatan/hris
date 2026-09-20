<?php

namespace App\Services\Leave;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public function __construct(
        private readonly LeaveCalculationService $calculator,
        private readonly LeaveBalanceService $balances
    ) {
    }

    public function submit(
        Employee $employee,
        LeaveType $leaveType,
        array $data
    ): LeaveRequest {
        return DB::transaction(function () use (
            $employee,
            $leaveType,
            $data
        ) {
            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($employee->id);

            $leaveType = LeaveType::query()
                ->findOrFail($leaveType->id);

            if (!$employee->is_active) {
                throw ValidationException::withMessages([
                    'employee_id' =>
                        'An inactive employee cannot submit leave.',
                ]);
            }

            if (!$leaveType->is_active) {
                throw ValidationException::withMessages([
                    'leave_type_id' =>
                        'The selected leave type is inactive.',
                ]);
            }

            $dateFrom = Carbon::parse(
                $data['date_from']
            )->toDateString();

            $dateTo = Carbon::parse(
                $data['date_to']
            )->toDateString();

            $dayPart = $data['day_part'] ?? null;

            $requestedDays =
                $this->calculator->calculateRequestedDays(
                    $employee,
                    $leaveType,
                    $dateFrom,
                    $dateTo,
                    $dayPart
                );

            if (bccomp($requestedDays, '0.00', 2) <= 0) {
                throw ValidationException::withMessages([
                    'date_from' =>
                        'The selected period contains no leave-eligible working days.',
                ]);
            }

            $this->validateRequirements(
                $leaveType,
                $data
            );

            return LeaveRequest::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,

                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'day_part' => $dayPart,

                'requested_days' => $requestedDays,

                'reason' => $data['reason'] ?? null,
                'document_path' =>
                    $data['document_path'] ?? null,

                'status' => 'pending',
            ]);
        });
    }

    /**
     * Business-side approval operation.
     *
     * Generic ApprovalService will later orchestrate authority,
     * delegation, ApprovalAction and audit.
     */
    public function approve(
        LeaveRequest $request,
        User $actedBy,
        ?string $remarks = null
    ): LeaveRequest {
        return DB::transaction(function () use (
            $request,
            $actedBy,
            $remarks
        ) {
            $request = LeaveRequest::query()
                ->lockForUpdate()
                ->findOrFail($request->id);

            $this->ensurePending($request);

            $employee = Employee::query()
                ->findOrFail($request->employee_id);

            $leaveType = LeaveType::query()
                ->findOrFail($request->leave_type_id);

            /*
             * Recalculate at approval time.
             *
             * Do not blindly trust the value captured at submission.
             */
            $approvedDays =
                $this->calculator->calculateRequestedDays(
                    $employee,
                    $leaveType,
                    $request->date_from,
                    $request->date_to,
                    $request->day_part
                );

            if ($leaveType->deduct_from_balance) {
                $year = Carbon::parse(
                    $request->date_from
                )->year;

                $this->balances->transact(
                    $employee,
                    $leaveType,
                    $year,
                    bcmul($approvedDays, '-1', 2),
                    'usage',
                    [
                        'source_type' => 'leave_request',
                        'source_id' => $request->id,
                        'remarks' =>
                            'Approved leave request usage.',
                    ]
                );
            }

            $request->requested_days = $approvedDays;
            $request->status = 'approved';

            /*
             * Exact approval/review columns should match our
             * audited migration.
             */
            $request->save();

            return $request->refresh();
        });
    }

    public function reject(
        LeaveRequest $request,
        User $actedBy,
        string $remarks
    ): LeaveRequest {
        return DB::transaction(function () use (
            $request,
            $actedBy,
            $remarks
        ) {
            $request = LeaveRequest::query()
                ->lockForUpdate()
                ->findOrFail($request->id);

            $this->ensurePending($request);

            $request->update([
                'status' => 'rejected',
            ]);

            return $request->refresh();
        });
    }

    public function cancel(
        LeaveRequest $request,
        Employee $employee
    ): LeaveRequest {
        return DB::transaction(function () use (
            $request,
            $employee
        ) {
            $request = LeaveRequest::query()
                ->lockForUpdate()
                ->findOrFail($request->id);

            if ($request->employee_id !== $employee->id) {
                throw ValidationException::withMessages([
                    'request' =>
                        'The leave request does not belong to this employee.',
                ]);
            }

            $this->ensurePending($request);

            $request->update([
                'status' => 'cancelled',
            ]);

            return $request->refresh();
        });
    }

    private function validateRequirements(
        LeaveType $leaveType,
        array $data
    ): void {
        if (
            $leaveType->requires_reason
            && empty(trim((string) ($data['reason'] ?? '')))
        ) {
            throw ValidationException::withMessages([
                'reason' =>
                    'A reason is required for this leave type.',
            ]);
        }

        if (
            $leaveType->requires_document
            && empty($data['document_path'])
        ) {
            throw ValidationException::withMessages([
                'document' =>
                    'A supporting document is required for this leave type.',
            ]);
        }
    }

    private function ensurePending(
        LeaveRequest $request
    ): void {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' =>
                    'Only pending leave requests may be changed.',
            ]);
        }
    }
}