<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payroll_employee_result_id',
    'code',
    'name',
    'category',
    'quantity',
    'rate',
    'amount',
    'source_type',
    'source_id',
    'description',
    'remarks',
])]
class PayrollDeduction extends Model
{
    use HasFactory;

    public function payrollEmployeeResult(): BelongsTo
    {
        return $this->belongsTo(PayrollEmployeeResult::class);
    }

    protected function casts(): array
    {
        return [
            'payroll_employee_result_id' => 'integer',
            'quantity' => 'decimal:4',
            'rate' => 'decimal:4',
            'amount' => 'decimal:2',
            'source_id' => 'integer',
        ];
    }
}