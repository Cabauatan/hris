<?php

namespace App\Services\Employee;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeEmploymentHistory;
use App\Models\EmployeeStatus;
use App\Models\EmploymentType;
use App\Models\Position;
use App\Models\User;
use App\Services\Audit\AuditService;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeEmploymentService
{
    public function __construct(
        private AuditService $audit
    ) {}

    /**
     * Apply an employment change and preserve the previous/current
     * employment state in EmployeeEmploymentHistory.
     */
    public function change(
        Employee $employee,
        array $data,
        User $actor
    ): Employee {
        return DB::transaction(function () use (
            $employee,
            $data,
            $actor
        ) {
            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($employee->id);

            $this->validateReferences(
                $employee,
                $data
            );

            $changes = $this->buildChanges(
                $employee,
                $data
            );

            if ($changes === []) {
                return $employee;
            }

            $effectiveDate =
                $data['effective_date']
                ?? now()->toDateString();

            /*
             * Capture ONLY the fields that are actually
             * going to change.
             */
            $oldValues = $employee->only(
                array_keys($changes)
            );

            /*
             * Close the current employment-history record.
             */
            $this->closeCurrentHistory(
                $employee,
                $effectiveDate
            );

            /*
             * Update current employee state.
             */
            $employee->update($changes);

            $employee->refresh();

            /*
             * Create the new historical snapshot.
             */
            $this->createHistory(
                $employee,
                $effectiveDate,
                $data
            );

            /*
             * Get the persisted new values.
             */
            $newValues = $employee->only(
                array_keys($changes)
            );

            /*
             * Employment history and audit trail are part
             * of the same DB transaction.
             *
             * If audit insertion fails, the employee update
             * and employment-history changes also roll back.
             */
            $this->audit->logModel(
                module: 'employee',
                action: 'employment_changed',
                auditableType: 'employee',
                model: $employee,
                actor: $actor,
                description:
                    "Changed employment details for employee [{$employee->employee_number}].",
                oldValues: $oldValues,
                newValues: $newValues,
            );

            return $employee->load([
                'department.division',
                'position',
                'employmentType',
                'employeeStatus',
                'supervisor',
            ]);
        });
    }

    private function buildChanges(
        Employee $employee,
        array $data
    ): array {
        $allowed = [
            'department_id',
            'position_id',
            'employment_type_id',
            'employee_status_id',
            'supervisor_id',
            'regularization_date',
            'employment_end_date',
        ];

        $changes = [];

        foreach ($allowed as $field) {
            if (
                array_key_exists($field, $data)
                && $data[$field] != $employee->{$field}
            ) {
                $changes[$field] = $data[$field];
            }
        }

        return $changes;
    }

    private function validateReferences(
        Employee $employee,
        array $data
    ): void {
        if (
            array_key_exists('department_id', $data)
            && $data['department_id'] !== null
        ) {
            $department = Department::query()
                ->with('division')
                ->find($data['department_id']);

            if (
                ! $department
                || ! $department->is_active
            ) {
                throw ValidationException::withMessages([
                    'department_id' =>
                        'The selected department is invalid or inactive.',
                ]);
            }

            if (
                $department->division
                && ! $department->division->is_active
            ) {
                throw ValidationException::withMessages([
                    'department_id' =>
                        'The selected department belongs to an inactive division.',
                ]);
            }
        }

        if (
            array_key_exists('position_id', $data)
            && $data['position_id'] !== null
        ) {
            $position = Position::query()
                ->find($data['position_id']);

            if (
                ! $position
                || ! $position->is_active
            ) {
                throw ValidationException::withMessages([
                    'position_id' =>
                        'The selected position is invalid or inactive.',
                ]);
            }
        }

        if (
            array_key_exists('employment_type_id', $data)
            && $data['employment_type_id'] !== null
        ) {
            $type = EmploymentType::query()
                ->find($data['employment_type_id']);

            if (
                ! $type
                || ! $type->is_active
            ) {
                throw ValidationException::withMessages([
                    'employment_type_id' =>
                        'The selected employment type is invalid or inactive.',
                ]);
            }
        }

        if (
            array_key_exists('employee_status_id', $data)
            && $data['employee_status_id'] !== null
        ) {
            $status = EmployeeStatus::query()
                ->find($data['employee_status_id']);

            if (
                ! $status
                || ! $status->is_active
            ) {
                throw ValidationException::withMessages([
                    'employee_status_id' =>
                        'The selected employee status is invalid or inactive.',
                ]);
            }
        }

        if (
            array_key_exists('supervisor_id', $data)
            && $data['supervisor_id'] !== null
        ) {
            $supervisorId =
                (int) $data['supervisor_id'];

            if ($supervisorId === $employee->id) {
                throw ValidationException::withMessages([
                    'supervisor_id' =>
                        'An employee cannot be their own supervisor.',
                ]);
            }

            $supervisor = Employee::query()
                ->find($supervisorId);

            if (
                ! $supervisor
                || ! $supervisor->is_active
            ) {
                throw ValidationException::withMessages([
                    'supervisor_id' =>
                        'The selected supervisor is invalid or inactive.',
                ]);
            }

            $this->ensureNoSupervisorCycle(
                $employee,
                $supervisor
            );
        }
    }

    /**
     * Prevent:
     *
     * Juan -> Pedro -> Maria -> Juan
     */
    private function ensureNoSupervisorCycle(
        Employee $employee,
        Employee $supervisor
    ): void {
        $current = $supervisor;
        $visited = [];

        while ($current !== null) {
            if ($current->id === $employee->id) {
                throw ValidationException::withMessages([
                    'supervisor_id' =>
                        'The supervisor assignment would create a reporting cycle.',
                ]);
            }

            if (isset($visited[$current->id])) {
                throw ValidationException::withMessages([
                    'supervisor_id' =>
                        'The existing supervisor hierarchy contains a cycle.',
                ]);
            }

            $visited[$current->id] = true;

            if ($current->supervisor_id === null) {
                break;
            }

            $current = Employee::query()
                ->find($current->supervisor_id);
        }
    }

    private function closeCurrentHistory(
        Employee $employee,
        string|CarbonInterface $effectiveDate
    ): void {
        $current = EmployeeEmploymentHistory::query()
            ->where('employee_id', $employee->id)
            ->whereNull('effective_to')
            ->lockForUpdate()
            ->latest('effective_from')
            ->first();

        if (! $current) {
            return;
        }

        $endDate = Carbon::parse($effectiveDate)
            ->subDay()
            ->toDateString();

        if (
            $endDate
            < $current->effective_from->toDateString()
        ) {
            throw ValidationException::withMessages([
                'effective_date' =>
                    'The effective date conflicts with the current employment history.',
            ]);
        }

        $current->update([
            'effective_to' => $endDate,
        ]);
    }

    private function createHistory(
        Employee $employee,
        string|CarbonInterface $effectiveDate,
        array $data
    ): void {
        EmployeeEmploymentHistory::create([
            'employee_id' =>
                $employee->id,

            'department_id' =>
                $employee->department_id,

            'position_id' =>
                $employee->position_id,

            'employment_type_id' =>
                $employee->employment_type_id,

            'employee_status_id' =>
                $employee->employee_status_id,

            'supervisor_id' =>
                $employee->supervisor_id,

            'effective_from' =>
                $effectiveDate,

            'effective_to' =>
                null,

            'change_type' =>
                $data['change_type']
                ?? 'employment_change',

            'remarks' =>
                $data['remarks']
                ?? null,
        ]);
    }
}