<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'employee_id',
    'overtime_request_id',
    'earned_date',
    'source_overtime_minutes',
    'earning_multiplier',
    'earned_minutes',
    'remaining_minutes',
    'expires_on',
    'status',
    'reason',
    'remarks',
    'created_by_user_id',
])]
class CtoEarning extends Model
{
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function overtimeRequest(): BelongsTo
    {
        return $this->belongsTo(OvertimeRequest::class);
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
            'overtime_request_id' => 'integer',

            'earned_date' => 'date',
            'source_overtime_minutes' => 'integer',
            'earning_multiplier' => 'decimal:2',

            'earned_minutes' => 'integer',
            'remaining_minutes' => 'integer',

            'expires_on' => 'date',
            'created_by_user_id' => 'integer',
        ];
    }
}