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
    'start_time',
    'end_time',
    'is_overnight',
    'break_minutes',
    'grace_period_minutes',
    'required_work_minutes',
    'is_active',
    'sort_order',
])]
class Shift extends Model
{
    use HasFactory;

    /**
     * Work schedule days using this shift.
     */
    public function workScheduleDays(): HasMany
    {
        return $this->hasMany(WorkScheduleDay::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_overnight' => 'boolean',
            'break_minutes' => 'integer',
            'grace_period_minutes' => 'integer',
            'required_work_minutes' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}