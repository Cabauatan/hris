<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'employee_id',
    'cto_date',
    'usage_type',
    'start_time',
    'end_time',
    'requested_minutes',
    'reason',
    'status',
    'approved_by_user_id',
    'approved_at',
    'approval_remarks',
    'cancelled_at',
    'cancellation_reason',
    'created_by_user_id',
])]
class CtoRequest extends Model
{
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
    public function transactions(): HasMany
    {
        return $this->hasMany(CtoTransaction::class);
    }
    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'cto_date' => 'date',
            'requested_minutes' => 'integer',

            'approved_by_user_id' => 'integer',
            'approved_at' => 'datetime',

            'cancelled_at' => 'datetime',
            'created_by_user_id' => 'integer',
        ];
    }
}