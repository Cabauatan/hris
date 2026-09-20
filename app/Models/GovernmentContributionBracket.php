<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'government_contribution_schedule_id',
    'compensation_from',
    'compensation_to',
    'contribution_base',
    'employee_fixed_amount',
    'employee_rate',
    'employer_fixed_amount',
    'employer_rate',
    'employee_min_amount',
    'employee_max_amount',
    'employer_min_amount',
    'employer_max_amount',
    'sort_order',
    'is_active',
    'remarks',
])]
class GovernmentContributionBracket extends Model
{
    use HasFactory;

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(
            GovernmentContributionSchedule::class,
            'government_contribution_schedule_id'
        );
    }
    public function payrollContributions(): HasMany
    {
        return $this->hasMany(PayrollGovernmentContribution::class);
    }

    protected function casts(): array
    {
        return [
            'government_contribution_schedule_id' => 'integer',

            'compensation_from' => 'decimal:2',
            'compensation_to' => 'decimal:2',
            'contribution_base' => 'decimal:2',

            'employee_fixed_amount' => 'decimal:2',
            'employee_rate' => 'decimal:6',

            'employer_fixed_amount' => 'decimal:2',
            'employer_rate' => 'decimal:6',

            'employee_min_amount' => 'decimal:2',
            'employee_max_amount' => 'decimal:2',
            'employer_min_amount' => 'decimal:2',
            'employer_max_amount' => 'decimal:2',

            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}