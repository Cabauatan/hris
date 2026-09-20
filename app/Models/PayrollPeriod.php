<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'period_start',
    'period_end',
    'attendance_cutoff_start',
    'attendance_cutoff_end',
    'pay_date',
    'pay_frequency',
    'status',
    'finalized_at',
    'finalized_by_user_id',
    'locked_at',
    'locked_by_user_id',
    'remarks',
])]
class PayrollPeriod extends Model
{
    use HasFactory;

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'finalized_by_user_id'
        );
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'locked_by_user_id'
        );
    }
    public function payrollRuns(): HasMany
    {
        return $this->hasMany(PayrollRun::class);
    }

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'attendance_cutoff_start' => 'date',
            'attendance_cutoff_end' => 'date',
            'pay_date' => 'date',

            'finalized_at' => 'datetime',
            'finalized_by_user_id' => 'integer',

            'locked_at' => 'datetime',
            'locked_by_user_id' => 'integer',
        ];
    }
}