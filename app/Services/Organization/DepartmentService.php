<?php

namespace App\Services\Organization;

use App\Models\Department;
use App\Models\Division;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepartmentService
{
    /**
     * Get paginated departments.
     */
    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?bool $isActive = null,
        ?int $divisionId = null
    ): LengthAwarePaginator {
        return Department::query()
            ->with('division')
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
                $divisionId !== null,
                fn ($query) => $query->where('division_id', $divisionId)
            )
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Create a department.
     */
    public function create(array $data): Department
    {
        return DB::transaction(function () use ($data) {
            $divisionId = $data['division_id'] ?? null;

            $this->validateDivision($divisionId);

            return Department::create([
                'division_id' => $divisionId,
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    /**
     * Update a department.
     */
    public function update(
        Department $department,
        array $data
    ): Department {
        return DB::transaction(function () use ($department, $data) {
            $divisionId = array_key_exists('division_id', $data)
                ? $data['division_id']
                : $department->division_id;

            $this->validateDivision($divisionId);

            $department->update([
                'division_id' => $divisionId,
                'code' => $data['code'] ?? $department->code,
                'name' => $data['name'] ?? $department->name,
                'description' => array_key_exists('description', $data)
                    ? $data['description']
                    : $department->description,
                'is_active' => $data['is_active']
                    ?? $department->is_active,
            ]);

            return $department->refresh()->load('division');
        });
    }

    /**
     * Deactivate a department.
     */
    public function deactivate(Department $department): Department
    {
        return DB::transaction(function () use ($department) {
            if (! $department->is_active) {
                return $department;
            }

            $department->update([
                'is_active' => false,
            ]);

            return $department->refresh();
        });
    }

    /**
     * Reactivate a department.
     */
    public function activate(Department $department): Department
    {
        return DB::transaction(function () use ($department) {
            $this->validateDivision($department->division_id);

            if ($department->is_active) {
                return $department;
            }

            $department->update([
                'is_active' => true,
            ]);

            return $department->refresh()->load('division');
        });
    }

    /**
     * Validate the assigned division.
     *
     * A department may exist without a division.
     */
    private function validateDivision(?int $divisionId): void
    {
        if ($divisionId === null) {
            return;
        }

        $division = Division::query()->find($divisionId);

        if (! $division) {
            throw ValidationException::withMessages([
                'division_id' => 'The selected division does not exist.',
            ]);
        }

        if (! $division->is_active) {
            throw ValidationException::withMessages([
                'division_id' => 'The selected division is inactive.',
            ]);
        }
    }
}