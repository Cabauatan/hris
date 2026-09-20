<?php

namespace App\Services\Organization;

use App\Models\Department;
use App\Models\Position;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PositionService
{
    /**
     * Get paginated positions.
     */
    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?bool $isActive = null,
        ?int $departmentId = null
    ): LengthAwarePaginator {
        return Position::query()
            ->with('department.division')
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when(
                $isActive !== null,
                fn ($query) => $query->where('is_active', $isActive)
            )
            ->when(
                $departmentId !== null,
                fn ($query) => $query->where(
                    'department_id',
                    $departmentId
                )
            )
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Create a position.
     */
    public function create(array $data): Position
    {
        return DB::transaction(function () use ($data) {
            $departmentId = $data['department_id'] ?? null;

            $this->validateDepartment($departmentId);

            $position = Position::create([
                'department_id' => $departmentId,
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            return $position->load('department.division');
        });
    }

    /**
     * Update a position.
     *
     * Changing the position's department does not transfer
     * employees assigned to this position.
     */
    public function update(
        Position $position,
        array $data
    ): Position {
        return DB::transaction(function () use ($position, $data) {
            $departmentId = array_key_exists('department_id', $data)
                ? $data['department_id']
                : $position->department_id;

            $this->validateDepartment($departmentId);

            $position->update([
                'department_id' => $departmentId,
                'code' => $data['code'] ?? $position->code,
                'name' => $data['name'] ?? $position->name,
                'description' => array_key_exists('description', $data)
                    ? $data['description']
                    : $position->description,
                'is_active' => $data['is_active']
                    ?? $position->is_active,
            ]);

            return $position->refresh()->load('department.division');
        });
    }

    /**
     * Deactivate a position.
     */
    public function deactivate(Position $position): Position
    {
        return DB::transaction(function () use ($position) {
            if (! $position->is_active) {
                return $position;
            }

            $position->update([
                'is_active' => false,
            ]);

            return $position->refresh();
        });
    }

    /**
     * Reactivate a position.
     */
    public function activate(Position $position): Position
    {
        return DB::transaction(function () use ($position) {
            $this->validateDepartment($position->department_id);

            if ($position->is_active) {
                return $position;
            }

            $position->update([
                'is_active' => true,
            ]);

            return $position->refresh()->load('department.division');
        });
    }

    /**
     * Validate the position's assigned department.
     *
     * A position may have no default department.
     */
    private function validateDepartment(?int $departmentId): void
    {
        if ($departmentId === null) {
            return;
        }

        $department = Department::query()
            ->with('division')
            ->find($departmentId);

        if (! $department) {
            throw ValidationException::withMessages([
                'department_id' =>
                    'The selected department does not exist.',
            ]);
        }

        if (! $department->is_active) {
            throw ValidationException::withMessages([
                'department_id' =>
                    'The selected department is inactive.',
            ]);
        }

        if (
            $department->division !== null
            && ! $department->division->is_active
        ) {
            throw ValidationException::withMessages([
                'department_id' =>
                    'The selected department belongs to an inactive division.',
            ]);
        }
    }
}