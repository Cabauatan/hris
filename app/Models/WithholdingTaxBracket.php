<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'table_name',
    'pay_frequency',
    'effective_from',
    'effective_to',
    'taxable_income_from',
    'taxable_income_to',
    'base_tax',
    'excess_over',
    'excess_rate',
    'sort_order',
    'is_active',
    'reference',
    'remarks',
    'created_by_user_id',
])]
class WithholdingTaxBracket extends Model
{
    use HasFactory;

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by_user_id'
        );
    }

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',

            'taxable_income_from' => 'decimal:2',
            'taxable_income_to' => 'decimal:2',

            'base_tax' => 'decimal:2',
            'excess_over' => 'decimal:2',
            'excess_rate' => 'decimal:6',

            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'created_by_user_id' => 'integer',
        ];
    }
}