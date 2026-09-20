<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'employee_id',
    'earned_minutes',
    'used_minutes',
    'expired_minutes',
    'adjustment_minutes',
    'balance_minutes',
    'remarks',
])]
class EmployeeCtoBalance extends Model
{
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CtoTransaction::class);
    }

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'earned_minutes' => 'integer',
            'used_minutes' => 'integer',
            'expired_minutes' => 'integer',
            'adjustment_minutes' => 'integer',
            'balance_minutes' => 'integer',
        ];
    }
}