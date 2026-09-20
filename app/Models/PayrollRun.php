<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'payroll_period_id',
    'run_number',
    'status',
    'started_at',
    'completed_at',
    'employee_count',
    'total_basic_pay',
    'total_gross_pay',
    'total_deductions',
    'total_net_pay',
    'processed_by_user_id',
    'finalized_at',
    'finalized_by_user_id',
    'error_message',
    'remarks',
])]
class PayrollRun extends Model
{
    use HasFactory;

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'processed_by_user_id'
        );
    }

    public function finalizedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'finalized_by_user_id'
        );
    }
    public function employeeResults(): HasMany
    {
        return $this->hasMany(PayrollEmployeeResult::class);
    }
    protected function casts(): array
    {
        return [
            'payroll_period_id' => 'integer',
            'run_number' => 'integer',

            'started_at' => 'datetime',
            'completed_at' => 'datetime',

            'employee_count' => 'integer',

            'total_basic_pay' => 'decimal:2',
            'total_gross_pay' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net_pay' => 'decimal:2',

            'processed_by_user_id' => 'integer',

            'finalized_at' => 'datetime',
            'finalized_by_user_id' => 'integer',
        ];
    }
}