<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'employee_id',
    'overtime_date',
    'requested_start',
    'requested_end',
    'requested_minutes',
    'reason',
    'status',
    'approved_by_user_id',
    'approved_at',
    'approved_minutes',
    'approval_remarks',
    'cancelled_at',
    'cancellation_reason',
    'created_by_user_id',
])]
class OvertimeRequest extends Model
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
    public function ctoEarnings(): HasMany
    {
        return $this->hasMany(CtoEarning::class);
    }
    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'overtime_date' => 'date',

            'requested_start' => 'datetime',
            'requested_end' => 'datetime',
            'requested_minutes' => 'integer',

            'approved_by_user_id' => 'integer',
            'approved_at' => 'datetime',
            'approved_minutes' => 'integer',

            'cancelled_at' => 'datetime',
            'created_by_user_id' => 'integer',
        ];
    }
}