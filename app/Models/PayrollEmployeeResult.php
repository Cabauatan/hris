<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'payroll_run_id',
    'employee_id',
    'employee_number',
    'employee_name',
    'pay_type',
    'basic_rate',

    'scheduled_days',
    'worked_days',
    'paid_leave_days',
    'unpaid_leave_days',
    'absent_days',
    'late_minutes',
    'undertime_minutes',
    'overtime_minutes',

    'basic_pay',
    'absence_deduction',
    'late_deduction',
    'undertime_deduction',

    'total_earnings',
    'gross_pay',

    'sss_contribution',
    'philhealth_contribution',
    'pagibig_contribution',
    'withholding_tax',
    'other_deductions',
    'total_deductions',
    'net_pay',

    'status',
    'remarks',
])]
class PayrollEmployeeResult extends Model
{
    use HasFactory;

    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
    public function earnings(): HasMany
    {
        return $this->hasMany(PayrollEarning::class);
    }
    public function deductions(): HasMany
    {
        return $this->hasMany(PayrollDeduction::class);
    }
    public function governmentContributions(): HasMany
    {
        return $this->hasMany(PayrollGovernmentContribution::class);
    }
    public function payslip(): HasOne
    {
        return $this->hasOne(Payslip::class);
    }

    protected function casts(): array
    {
        return [
            'payroll_run_id' => 'integer',
            'employee_id' => 'integer',

            'basic_rate' => 'decimal:2',

            'scheduled_days' => 'decimal:2',
            'worked_days' => 'decimal:2',
            'paid_leave_days' => 'decimal:2',
            'unpaid_leave_days' => 'decimal:2',
            'absent_days' => 'decimal:2',

            'late_minutes' => 'integer',
            'undertime_minutes' => 'integer',
            'overtime_minutes' => 'integer',

            'basic_pay' => 'decimal:2',
            'absence_deduction' => 'decimal:2',
            'late_deduction' => 'decimal:2',
            'undertime_deduction' => 'decimal:2',

            'total_earnings' => 'decimal:2',
            'gross_pay' => 'decimal:2',

            'sss_contribution' => 'decimal:2',
            'philhealth_contribution' => 'decimal:2',
            'pagibig_contribution' => 'decimal:2',
            'withholding_tax' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_pay' => 'decimal:2',
        ];
    }
}