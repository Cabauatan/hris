<?php

namespace App\Services\Cto;

use App\Models\CtoEarning;
use App\Models\CtoRequest;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CtoService
{
    public function __construct(
        private readonly CtoBalanceService $balances
    ) {
    }

    public function paginate(
        int $perPage = 15,
        ?int $employeeId = null,
        ?string $status = null
    ): LengthAwarePaginator {
        return CtoRequest::query()
            ->with('employee')
            ->when(
                $employeeId !== null,
                fn ($query) =>
                    $query->where('employee_id', $employeeId)
            )
            ->when(
                $status !== null,
                fn ($query) =>
                    $query->where('status', $status)
            )
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Convert eligible approved overtime into CTO earning.
     *
     * This operation must be idempotent.
     */
    public function earnFromOvertime(
        OvertimeRequest $overtime,
        array $data = []
    ): CtoEarning {
        return DB::transaction(function () use (
            $overtime,
            $data
        ) {
            $overtime = OvertimeRequest::query()
                ->lockForUpdate()
                ->findOrFail($overtime->id);

            if ($overtime->status !== 'approved') {
                throw ValidationException::withMessages([
                    'overtime_request' =>
                        'Only approved overtime may generate CTO.',
                ]);
            }

            if (
                ! $overtime->approved_minutes
                || $overtime->approved_minutes <= 0
            ) {
                throw ValidationException::withMessages([
                    'approved_minutes' =>
                        'The overtime request has no approved minutes.',
                ]);
            }

            /*
             * Never credit the same OT twice.
             *
             * Exact eligibility rules from CtoSetting are applied
             * before this operation is exposed in production.
             */
            $existing = CtoEarning::query()
                ->where(
                    'overtime_request_id',
                    $overtime->id
                )
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $employee = Employee::query()
                ->findOrFail($overtime->employee_id);

            /*
             * For now approved_minutes is the source quantity.
             *
             * CtoSetting may later apply conversion ratios,
             * caps and expiry policy.
             */
            $earnedMinutes =
                (int) $overtime->approved_minutes;

            $expiresAt = isset($data['expires_at'])
                ? Carbon::parse($data['expires_at'])
                : null;

            $earning = CtoEarning::create([
                'employee_id' => $employee->id,

                'overtime_request_id' => $overtime->id,

                'earned_minutes' => $earnedMinutes,

                'earned_at' =>
                    $data['earned_at'] ?? now(),

                'expires_at' => $expiresAt,

                'remarks' => $data['remarks'] ?? null,
            ]);

            $this->balances->transact(
                $employee,
                $earnedMinutes,
                'earning',
                [
                    'source_type' => 'cto_earning',
                    'source_id' => $earning->id,
                    'remarks' =>
                        'CTO earned from approved overtime.',
                ]
            );

            return $earning->refresh();
        });
    }

    /**
     * Submit a CTO usage request.
     *
     * requested_minutes must be calculated/validated by backend.
     */
    public function submit(
        Employee $employee,
        array $data
    ): CtoRequest {
        return DB::transaction(function () use (
            $employee,
            $data
        ) {
            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($employee->id);

            if (! $employee->is_active) {
                throw ValidationException::withMessages([
                    'employee_id' =>
                        'An inactive employee cannot submit a CTO request.',
                ]);
            }

            $dateFrom = Carbon::parse(
                $data['date_from']
            );

            $dateTo = Carbon::parse(
                $data['date_to']
            );

            if ($dateTo->lte($dateFrom)) {
                throw ValidationException::withMessages([
                    'date_to' =>
                        'The CTO end must be later than the start.',
                ]);
            }

            $requestedMinutes =
                $dateFrom->diffInMinutes($dateTo);

            if ($requestedMinutes <= 0) {
                throw ValidationException::withMessages([
                    'requested_minutes' =>
                        'CTO duration must be greater than zero.',
                ]);
            }

            return CtoRequest::create([
                'employee_id' => $employee->id,

                'date_from' => $dateFrom,
                'date_to' => $dateTo,

                'requested_minutes' => $requestedMinutes,
                'approved_minutes' => null,

                'reason' => $data['reason'] ?? null,

                'status' => 'pending',
            ]);
        });
    }

    /**
     * Apply an already-authorized approval.
     *
     * ApprovalService will later handle authority/delegation,
     * ApprovalAction and audit.
     */
    public function approve(
        CtoRequest $request,
        int $approvedMinutes,
        User $actedBy,
        ?string $remarks = null
    ): CtoRequest {
        return DB::transaction(function () use (
            $request,
            $approvedMinutes,
            $actedBy,
            $remarks
        ) {
            $request = CtoRequest::query()
                ->lockForUpdate()
                ->findOrFail($request->id);

            $this->ensurePending($request);

            if ($approvedMinutes <= 0) {
                throw ValidationException::withMessages([
                    'approved_minutes' =>
                        'Approved CTO minutes must be greater than zero.',
                ]);
            }

            if (
                $approvedMinutes
                > $request->requested_minutes
            ) {
                throw ValidationException::withMessages([
                    'approved_minutes' =>
                        'Approved CTO minutes cannot exceed requested minutes.',
                ]);
            }

            $employee = Employee::query()
                ->findOrFail($request->employee_id);

            /*
             * Permanent consumption happens only at approval.
             */
            $this->balances->transact(
                $employee,
                -$approvedMinutes,
                'usage',
                [
                    'source_type' => 'cto_request',
                    'source_id' => $request->id,
                    'remarks' =>
                        'Approved CTO request usage.',
                ]
            );

            $request->approved_minutes = $approvedMinutes;
            $request->status = 'approved';

            /*
             * Exact review columns remain governed by
             * the audited migration.
             */
            $request->save();

            return $request->refresh();
        });
    }

    public function reject(
        CtoRequest $request,
        User $actedBy,
        string $remarks
    ): CtoRequest {
        return DB::transaction(function () use (
            $request,
            $actedBy,
            $remarks
        ) {
            $request = CtoRequest::query()
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
        CtoRequest $request,
        Employee $employee
    ): CtoRequest {
        return DB::transaction(function () use (
            $request,
            $employee
        ) {
            $request = CtoRequest::query()
                ->lockForUpdate()
                ->findOrFail($request->id);

            if ($request->employee_id !== $employee->id) {
                throw ValidationException::withMessages([
                    'request' =>
                        'The CTO request does not belong to this employee.',
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
        CtoRequest $request
    ): void {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' =>
                    'Only pending CTO requests may be changed.',
            ]);
        }
    }
}