<?php

namespace App\Services\Employee;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeStatus;
use App\Models\EmploymentType;
use App\Models\Position;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeService
{
    public function __construct(
        private AuditService $audit
    ) {}

    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?bool $isActive = null,
        ?int $departmentId = null,
        ?int $positionId = null
    ): LengthAwarePaginator {
        return Employee::query()
            ->with([
                'user',
                'department.division',
                'position',
                'employmentType',
                'employeeStatus',
                'supervisor',
            ])
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where(
                            'employee_number',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'first_name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'middle_name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'last_name',
                            'like',
                            "%{$search}%"
                        );
                });
            })
            ->when(
                $isActive !== null,
                fn ($query) =>
                    $query->where('is_active', $isActive)
            )
            ->when(
                $departmentId !== null,
                fn ($query) =>
                    $query->where(
                        'department_id',
                        $departmentId
                    )
            )
            ->when(
                $positionId !== null,
                fn ($query) =>
                    $query->where(
                        'position_id',
                        $positionId
                    )
            )
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate($perPage);
    }

    public function create(
        array $data,
        User $actor
    ): Employee {
        return DB::transaction(function () use (
            $data,
            $actor
        ) {
            $this->validateReferences($data);

            $employee = Employee::create(
                $this->employeeData($data)
            );

            $this->audit->logModel(
                module: 'employee',
                action: 'created',
                auditableType: 'employee',
                model: $employee,
                actor: $actor,
                description:
                    "Created employee [{$employee->employee_number}].",
                newValues: $this->auditValues($employee),
            );

            return $employee->load([
                'user',
                'department.division',
                'position',
                'employmentType',
                'employeeStatus',
                'supervisor',
            ]);
        });
    }

    public function update(
        Employee $employee,
        array $data,
        User $actor
    ): Employee {
        return DB::transaction(function () use (
            $employee,
            $data,
            $actor
        ) {
            /*
             * Lock the current employee row because this is
             * a material master-data mutation.
             */
            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($employee->id);

            /*
             * Employment-related fields are deliberately
             * excluded here.
             *
             * Department, position, employment type/status
             * and similar changes go through
             * EmployeeEmploymentService so employment
             * history cannot be bypassed.
             */
            $allowed = [
                'first_name',
                'middle_name',
                'last_name',
                'suffix',
                'preferred_name',
                'birth_date',
                'gender',
                'civil_status',
                'personal_email',
                'company_email',
                'mobile_number',
            ];

            $updates = [];

            foreach ($allowed as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            if ($updates === []) {
                return $employee->load([
                    'user',
                    'department.division',
                    'position',
                    'employmentType',
                    'employeeStatus',
                    'supervisor',
                ]);
            }

            /*
             * Capture only fields being changed.
             */
            $oldValues = $employee->only(
                array_keys($updates)
            );

            /*
             * Remove values that are actually unchanged.
             * This avoids unnecessary UPDATE + audit rows.
             */
            $changedValues = [];

            foreach ($updates as $field => $value) {
                if ($oldValues[$field] != $value) {
                    $changedValues[$field] = $value;
                }
            }

            if ($changedValues === []) {
                return $employee->load([
                    'user',
                    'department.division',
                    'position',
                    'employmentType',
                    'employeeStatus',
                    'supervisor',
                ]);
            }

            $oldChangedValues = [];

            foreach (array_keys($changedValues) as $field) {
                $oldChangedValues[$field] =
                    $oldValues[$field];
            }

            $employee->update($changedValues);

            $employee->refresh();

            $newChangedValues = $employee->only(
                array_keys($changedValues)
            );

            $this->audit->logModel(
                module: 'employee',
                action: 'updated',
                auditableType: 'employee',
                model: $employee,
                actor: $actor,
                description:
                    "Updated employee [{$employee->employee_number}].",
                oldValues: $oldChangedValues,
                newValues: $newChangedValues,
            );

            return $employee->load([
                'user',
                'department.division',
                'position',
                'employmentType',
                'employeeStatus',
                'supervisor',
            ]);
        });
    }

    public function linkUser(
        Employee $employee,
        User $user,
        User $actor
    ): Employee {
        return DB::transaction(function () use (
            $employee,
            $user,
            $actor
        ) {
            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($employee->id);

            if (
                $user->employee()->exists()
                && $user->employee()->value('id')
                    !== $employee->id
            ) {
                throw ValidationException::withMessages([
                    'user_id' =>
                        'The selected user is already linked to another employee.',
                ]);
            }

            /*
             * Already linked to the same user.
             */
            if ($employee->user_id === $user->id) {
                return $employee
                    ->refresh()
                    ->load('user');
            }

            $oldUserId = $employee->user_id;

            $employee->update([
                'user_id' => $user->id,
            ]);

            $employee->refresh();

            $this->audit->logModel(
                module: 'employee',
                action: 'user_linked',
                auditableType: 'employee',
                model: $employee,
                actor: $actor,
                description:
                    "Linked a user account to employee [{$employee->employee_number}].",
                oldValues: [
                    'user_id' => $oldUserId,
                ],
                newValues: [
                    'user_id' => $employee->user_id,
                ],
            );

            return $employee->load('user');
        });
    }

    public function unlinkUser(
        Employee $employee,
        User $actor
    ): Employee {
        return DB::transaction(function () use (
            $employee,
            $actor
        ) {
            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($employee->id);

            /*
             * Nothing to unlink.
             */
            if ($employee->user_id === null) {
                return $employee;
            }

            $oldUserId = $employee->user_id;

            $employee->update([
                'user_id' => null,
            ]);

            $employee->refresh();

            $this->audit->logModel(
                module: 'employee',
                action: 'user_unlinked',
                auditableType: 'employee',
                model: $employee,
                actor: $actor,
                description:
                    "Unlinked the user account from employee [{$employee->employee_number}].",
                oldValues: [
                    'user_id' => $oldUserId,
                ],
                newValues: [
                    'user_id' => null,
                ],
            );

            return $employee;
        });
    }

    private function validateReferences(
        array $data
    ): void {
        if (! empty($data['user_id'])) {
            $alreadyLinked = Employee::query()
                ->where('user_id', $data['user_id'])
                ->exists();

            if ($alreadyLinked) {
                throw ValidationException::withMessages([
                    'user_id' =>
                        'The selected user is already linked to an employee.',
                ]);
            }
        }

        if (! empty($data['department_id'])) {
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

        if (! empty($data['position_id'])) {
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

        if (! empty($data['employment_type_id'])) {
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

        if (! empty($data['employee_status_id'])) {
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

        if (! empty($data['supervisor_id'])) {
            $supervisor = Employee::query()
                ->find($data['supervisor_id']);

            if (
                ! $supervisor
                || ! $supervisor->is_active
            ) {
                throw ValidationException::withMessages([
                    'supervisor_id' =>
                        'The selected supervisor is invalid or inactive.',
                ]);
            }
        }
    }

    private function employeeData(
        array $data
    ): array {
        return [
            'user_id' =>
                $data['user_id'] ?? null,

            'employee_number' =>
                $data['employee_number'],

            'first_name' =>
                $data['first_name'],

            'middle_name' =>
                $data['middle_name'] ?? null,

            'last_name' =>
                $data['last_name'],

            'suffix' =>
                $data['suffix'] ?? null,

            'preferred_name' =>
                $data['preferred_name'] ?? null,

            'birth_date' =>
                $data['birth_date'] ?? null,

            'gender' =>
                $data['gender'] ?? null,

            'civil_status' =>
                $data['civil_status'] ?? null,

            'personal_email' =>
                $data['personal_email'] ?? null,

            'company_email' =>
                $data['company_email'] ?? null,

            'mobile_number' =>
                $data['mobile_number'] ?? null,

            'department_id' =>
                $data['department_id'] ?? null,

            'position_id' =>
                $data['position_id'] ?? null,

            'employment_type_id' =>
                $data['employment_type_id'] ?? null,

            'employee_status_id' =>
                $data['employee_status_id'] ?? null,

            'supervisor_id' =>
                $data['supervisor_id'] ?? null,

            'hire_date' =>
                $data['hire_date'],

            'regularization_date' =>
                $data['regularization_date'] ?? null,

            'employment_end_date' =>
                $data['employment_end_date'] ?? null,

            'is_active' =>
                $data['is_active'] ?? true,
        ];
    }

    /*
     * Explicit allow-list for employee creation audit.
     *
     * Do not blindly pass $employee->toArray() into
     * audit logs because future relationships or sensitive
     * attributes may accidentally be included.
     */
    private function auditValues(
        Employee $employee
    ): array {
        return $employee->only([
            'user_id',
            'employee_number',
            'first_name',
            'middle_name',
            'last_name',
            'suffix',
            'preferred_name',
            'birth_date',
            'gender',
            'civil_status',
            'personal_email',
            'company_email',
            'mobile_number',
            'department_id',
            'position_id',
            'employment_type_id',
            'employee_status_id',
            'supervisor_id',
            'hire_date',
            'regularization_date',
            'employment_end_date',
            'is_active',
        ]);
    }
}