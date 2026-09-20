<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Models\EmployeeAddress;
use App\Models\EmployeeBankAccount;
use App\Models\EmployeeEmergencyContact;
use App\Models\EmployeeGovernmentId;
use App\Models\EmployeeProfileChangeRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeProfileChangeService
{
    /*
     * Strict internal whitelist.
     *
     * Never accept a model/class name directly from the frontend.
     */
    private const CHANGE_TYPES = [
        'personal_information',
        'address',
        'emergency_contact',
        'bank_account',
        'government_id',
    ];

    private const TARGET_TYPES = [
        'address' => EmployeeAddress::class,
        'emergency_contact' => EmployeeEmergencyContact::class,
        'bank_account' => EmployeeBankAccount::class,
        'government_id' => EmployeeGovernmentId::class,
    ];

    private const ALLOWED_FIELDS = [
        'personal_information' => [
            'first_name',
            'middle_name',
            'last_name',
            'suffix',
            'birth_date',
            'gender',
            'civil_status',
            'personal_email',
            'mobile_number',
        ],

        'address' => [
            'address_line_1',
            'address_line_2',
            'barangay',
            'city',
            'province',
            'postal_code',
            'country',
        ],

        'emergency_contact' => [
            'name',
            'relationship',
            'mobile_number',
            'telephone_number',
            'address',
        ],

        'bank_account' => [
            'bank_name',
            'account_name',
            'account_number',
        ],

        'government_id' => [
            'id_number',
        ],
    ];

    public function submit(
        Employee $employee,
        array $data,
        ?User $createdBy = null
    ): EmployeeProfileChangeRequest {
        return DB::transaction(function () use (
            $employee,
            $data,
            $createdBy
        ) {
            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($employee->id);

            if (! $employee->is_active) {
                throw ValidationException::withMessages([
                    'employee' =>
                        'Inactive employees cannot submit profile change requests.',
                ]);
            }

            $changeType = $data['change_type'] ?? null;

            $this->validateChangeType($changeType);

            $requestedChanges = $this->sanitizeChanges(
                $changeType,
                $data['requested_changes'] ?? []
            );

            if ($requestedChanges === []) {
                throw ValidationException::withMessages([
                    'requested_changes' =>
                        'At least one valid profile change is required.',
                ]);
            }

            $target = $this->resolveTarget(
                $employee,
                $changeType,
                $data['target_id'] ?? null
            );

            $this->preventDuplicatePendingRequest(
                $employee,
                $changeType,
                $target?->getKey()
            );

            return EmployeeProfileChangeRequest::create([
                'employee_id' => $employee->id,

                'change_type' => $changeType,

                'target_type' =>
                    $target
                        ? $this->targetTypeFor($changeType)
                        : null,

                'target_id' => $target?->getKey(),

                'requested_changes' => $requestedChanges,

                'status' => 'pending',

                'reason' => $data['reason'] ?? null,

                'document_path' =>
                    $data['document_path'] ?? null,

                'created_by_user_id' =>
                    $createdBy?->id,
            ]);
        });
    }

    public function approve(
        EmployeeProfileChangeRequest $request,
        User $reviewer,
        ?string $remarks = null
    ): EmployeeProfileChangeRequest {
        return DB::transaction(function () use (
            $request,
            $reviewer,
            $remarks
        ) {
            $request = EmployeeProfileChangeRequest::query()
                ->lockForUpdate()
                ->findOrFail($request->id);

            $this->ensurePending($request);

            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($request->employee_id);

            /*
             * Re-sanitize/revalidate at approval time.
             * Never trust old request JSON blindly.
             */
            $changes = $this->sanitizeChanges(
                $request->change_type,
                $request->requested_changes
            );

            $target = $this->resolveRequestTarget(
                $request,
                $employee
            );

            $this->applyChanges(
                $employee,
                $request->change_type,
                $changes,
                $target
            );

            $request->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $reviewer->id,
                'review_remarks' => $remarks,
            ]);

            return $request->refresh();
        });
    }

    public function reject(
        EmployeeProfileChangeRequest $request,
        User $reviewer,
        string $remarks
    ): EmployeeProfileChangeRequest {
        return DB::transaction(function () use (
            $request,
            $reviewer,
            $remarks
        ) {
            $request = EmployeeProfileChangeRequest::query()
                ->lockForUpdate()
                ->findOrFail($request->id);

            $this->ensurePending($request);

            if (trim($remarks) === '') {
                throw ValidationException::withMessages([
                    'remarks' =>
                        'A rejection reason is required.',
                ]);
            }

            $request->update([
                'status' => 'rejected',
                'reviewed_at' => now(),
                'reviewed_by_user_id' => $reviewer->id,
                'review_remarks' => $remarks,
            ]);

            return $request->refresh();
        });
    }

    public function cancel(
        EmployeeProfileChangeRequest $request,
        Employee $employee
    ): EmployeeProfileChangeRequest {
        return DB::transaction(function () use (
            $request,
            $employee
        ) {
            $request = EmployeeProfileChangeRequest::query()
                ->lockForUpdate()
                ->findOrFail($request->id);

            $this->ensurePending($request);

            if ($request->employee_id !== $employee->id) {
                throw ValidationException::withMessages([
                    'request' =>
                        'The profile change request does not belong to this employee.',
                ]);
            }

            $request->update([
                'status' => 'cancelled',
            ]);

            return $request->refresh();
        });
    }

    private function applyChanges(
        Employee $employee,
        string $changeType,
        array $changes,
        ?Model $target
    ): void {
        if ($changeType === 'personal_information') {
            /*
             * Safe because $changes has already passed
             * the explicit field whitelist.
             */
            $employee->update($changes);

            return;
        }

        if (! $target) {
            throw ValidationException::withMessages([
                'target' =>
                    'The requested profile record no longer exists.',
            ]);
        }

        $target->update($changes);
    }

    private function resolveTarget(
        Employee $employee,
        string $changeType,
        mixed $targetId
    ): ?Model {
        if ($changeType === 'personal_information') {
            return null;
        }

        if ($targetId === null) {
            throw ValidationException::withMessages([
                'target_id' =>
                    'A target record is required for this change type.',
            ]);
        }

        $modelClass = self::TARGET_TYPES[$changeType] ?? null;

        if (! $modelClass) {
            throw ValidationException::withMessages([
                'change_type' =>
                    'Unsupported profile change target.',
            ]);
        }

        $target = $modelClass::query()
            ->findOrFail($targetId);

        /*
         * Critical ownership check.
         */
        if ($target->employee_id !== $employee->id) {
            throw ValidationException::withMessages([
                'target_id' =>
                    'The target record does not belong to this employee.',
            ]);
        }

        return $target;
    }

    private function resolveRequestTarget(
        EmployeeProfileChangeRequest $request,
        Employee $employee
    ): ?Model {
        if ($request->change_type === 'personal_information') {
            return null;
        }

        /*
         * Do not instantiate request->target_type blindly.
         * Resolve through our internal whitelist again.
         */
        return $this->resolveTarget(
            $employee,
            $request->change_type,
            $request->target_id
        );
    }

    private function sanitizeChanges(
        string $changeType,
        array $changes
    ): array {
        $allowed =
            self::ALLOWED_FIELDS[$changeType] ?? [];

        return array_intersect_key(
            $changes,
            array_flip($allowed)
        );
    }

    private function validateChangeType(
        ?string $changeType
    ): void {
        if (
            ! $changeType
            || ! in_array(
                $changeType,
                self::CHANGE_TYPES,
                true
            )
        ) {
            throw ValidationException::withMessages([
                'change_type' =>
                    'Unsupported profile change type.',
            ]);
        }
    }

    private function targetTypeFor(
        string $changeType
    ): string {
        return $changeType;
    }

    private function preventDuplicatePendingRequest(
        Employee $employee,
        string $changeType,
        mixed $targetId
    ): void {
        $exists = EmployeeProfileChangeRequest::query()
            ->where('employee_id', $employee->id)
            ->where('change_type', $changeType)
            ->where('status', 'pending')
            ->when(
                $targetId === null,
                fn ($query) =>
                    $query->whereNull('target_id'),
                fn ($query) =>
                    $query->where('target_id', $targetId)
            )
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'request' =>
                    'A pending change request already exists for this record.',
            ]);
        }
    }

    private function ensurePending(
        EmployeeProfileChangeRequest $request
    ): void {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages([
                'status' =>
                    'Only pending profile change requests may be processed.',
            ]);
        }
    }
}