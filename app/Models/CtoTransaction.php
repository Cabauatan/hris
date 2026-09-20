<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'employee_cto_balance_id',
    'cto_earning_id',
    'cto_request_id',
    'transaction_type',
    'transaction_date',
    'minutes',
    'balance_before',
    'balance_after',
    'description',
    'remarks',
    'created_by_user_id',
])]
class CtoTransaction extends Model
{
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function employeeCtoBalance(): BelongsTo
    {
        return $this->belongsTo(EmployeeCtoBalance::class);
    }

    public function ctoEarning(): BelongsTo
    {
        return $this->belongsTo(CtoEarning::class);
    }

    public function ctoRequest(): BelongsTo
    {
        return $this->belongsTo(CtoRequest::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'employee_cto_balance_id' => 'integer',
            'cto_earning_id' => 'integer',
            'cto_request_id' => 'integer',

            'transaction_date' => 'date',

            'minutes' => 'integer',
            'balance_before' => 'integer',
            'balance_after' => 'integer',

            'created_by_user_id' => 'integer',
        ];
    }
}