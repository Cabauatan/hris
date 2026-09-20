<?php

namespace App\Services\Organization;

use App\Models\Division;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DivisionService
{
    /**
     * Get paginated divisions.
     */
    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?bool $isActive = null
    ): LengthAwarePaginator {
        return Division::query()
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
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Create a division.
     */
    public function create(array $data): Division
    {
        return DB::transaction(function () use ($data) {
            return Division::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    /**
     * Update a division.
     */
    public function update(Division $division, array $data): Division
    {
        return DB::transaction(function () use ($division, $data) {
            $division->update([
                'code' => $data['code'] ?? $division->code,
                'name' => $data['name'] ?? $division->name,
                'description' => array_key_exists('description', $data)
                    ? $data['description']
                    : $division->description,
                'is_active' => $data['is_active'] ?? $division->is_active,
            ]);

            return $division->refresh();
        });
    }

    /**
     * Deactivate a division.
     */
    public function deactivate(Division $division): Division
    {
        return DB::transaction(function () use ($division) {
            if (! $division->is_active) {
                return $division;
            }

            $division->update([
                'is_active' => false,
            ]);

            return $division->refresh();
        });
    }

    /**
     * Reactivate a division.
     */
    public function activate(Division $division): Division
    {
        return DB::transaction(function () use ($division) {
            if ($division->is_active) {
                return $division;
            }

            $division->update([
                'is_active' => true,
            ]);

            return $division->refresh();
        });
    }
}