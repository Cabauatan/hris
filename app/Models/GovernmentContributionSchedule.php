<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'agency',
    'name',
    'effective_from',
    'effective_to',
    'calculation_method',
    'is_active',
    'reference',
    'remarks',
    'created_by_user_id',
])]
class GovernmentContributionSchedule extends Model
{
    use HasFactory;

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id'
        );
    }

    public function brackets(): HasMany
    {
        return $this->hasMany(GovernmentContributionBracket::class);
    }
    public function payrollContributions(): HasMany
    {
        return $this->hasMany(PayrollGovernmentContribution::class);
    }
    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'is_active' => 'boolean',
            'created_by_user_id' => 'integer',
        ];
    }
}