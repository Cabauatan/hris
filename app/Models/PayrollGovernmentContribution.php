<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payroll_employee_result_id',
    'government_contribution_schedule_id',
    'government_contribution_bracket_id',
    'agency',
    'member_number',
    'compensation_basis',
    'employee_share',
    'employer_share',
    'total_contribution',
    'employee_rate',
    'employer_rate',
    'status',
    'is_overridden',
    'override_reason',
    'overridden_by_user_id',
    'overridden_at',
    'remarks',
])]
class PayrollGovernmentContribution extends Model
{
    use HasFactory;

    public function payrollEmployeeResult(): BelongsTo
    {
        return $this->belongsTo(PayrollEmployeeResult::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(
            GovernmentContributionSchedule::class,
            'government_contribution_schedule_id'
        );
    }

    public function bracket(): BelongsTo
    {
        return $this->belongsTo(
            GovernmentContributionBracket::class,
            'government_contribution_bracket_id'
        );
    }

    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'overridden_by_user_id'
        );
    }

    protected function casts(): array
    {
        return [
            'payroll_employee_result_id' => 'integer',
            'government_contribution_schedule_id' => 'integer',
            'government_contribution_bracket_id' => 'integer',

            'compensation_basis' => 'decimal:2',
            'employee_share' => 'decimal:2',
            'employer_share' => 'decimal:2',
            'total_contribution' => 'decimal:2',

            'employee_rate' => 'decimal:6',
            'employer_rate' => 'decimal:6',

            'is_overridden' => 'boolean',
            'overridden_by_user_id' => 'integer',
            'overridden_at' => 'datetime',
        ];
    }
}