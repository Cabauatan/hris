<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'work_schedule_id',
    'effective_from',
    'effective_to',
    'is_current',
    'reason',
    'remarks',
    'assigned_by_user_id',
])]
class EmployeeScheduleAssignment extends Model
{
    use HasFactory;

    /**
     * Employee who owns this schedule assignment.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Work schedule assigned to the employee.
     */
    public function workSchedule(): BelongsTo
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    /**
     * User who assigned the work schedule.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'work_schedule_id' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_current' => 'boolean',
            'assigned_by_user_id' => 'integer',
        ];
    }
}