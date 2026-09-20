<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'code',
    'name',
    'category',
    'amount',
    'frequency',
    'original_balance',
    'remaining_balance',
    'effective_from',
    'effective_to',
    'is_active',
    'reference_number',
    'remarks',
    'created_by_user_id',
])]
class EmployeeRecurringDeduction extends Model
{
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id'
        );
    }

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'amount' => 'decimal:2',
            'original_balance' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
            'created_by_user_id' => 'integer',
        ];
    }
}