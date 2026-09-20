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
    'start_date',
    'end_date',
    'day_part',
    'requested_days',
    'reason',
    'document_path',
    'status',
    'cancelled_at',
    'cancellation_reason',
    'created_by_user_id',
])]
class LeaveRequest extends Model
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
    public function creditTransactions(): HasMany
    {
        return $this->hasMany(LeaveCreditTransaction::class);
    }
    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'leave_type_id' => 'integer',

            'start_date' => 'date',
            'end_date' => 'date',

            'requested_days' => 'decimal:2',

            'cancelled_at' => 'datetime',
            'created_by_user_id' => 'integer',
        ];
    }
}