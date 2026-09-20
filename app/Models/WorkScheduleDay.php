<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'work_schedule_id',
    'day_of_week',
    'shift_id',
    'is_rest_day',
])]
class WorkScheduleDay extends Model
{
    use HasFactory;

    /**
     * Work schedule that owns this day configuration.
     */
    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    /**
     * Shift assigned to this day.
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'work_schedule_id' => 'integer',
            'day_of_week' => 'integer',
            'shift_id' => 'integer',
            'is_rest_day' => 'boolean',
        ];
    }
}