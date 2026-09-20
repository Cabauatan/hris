<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'description',
    'is_active',
    'sort_order',
])]
class WorkSchedule extends Model
{
    use HasFactory;

    /**
     * Days configured for this work schedule.
     */
    public function days(): HasMany
    {
        return $this->hasMany(WorkScheduleDay::class);
    }

    /**
     * Employee assignments using this work schedule.
     */
    public function employeeAssignments(): HasMany
    {
        return $this->hasMany(EmployeeScheduleAssignment::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}