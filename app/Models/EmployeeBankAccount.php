<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'bank_name',
    'branch_name',
    'account_name',
    'account_number',
    'account_type',
    'is_primary',
    'is_active',
    'remarks',
])]
class EmployeeBankAccount extends Model
{
    use HasFactory;

    /**
     * Employee who owns this bank account.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}