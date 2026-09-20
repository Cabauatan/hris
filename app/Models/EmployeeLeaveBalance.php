<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'employee_id',
    'leave_type_id',
    'year',
    'allocated_days',
    'earned_days',
    'adjustment_days',
    'used_days',
    'balance_days',
    'remarks',
])]
class EmployeeLeaveBalance extends Model
{
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
    public function transactions(): HasMany
    {
        return $this->hasMany(LeaveCreditTransaction::class);
    }
    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'leave_type_id' => 'integer',
            'year' => 'integer',

            'allocated_days' => 'decimal:2',
            'earned_days' => 'decimal:2',
            'adjustment_days' => 'decimal:2',
            'used_days' => 'decimal:2',
            'balance_days' => 'decimal:2',
        ];
    }
}