<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'description',
    'is_paid',
    'deduct_from_balance',
    'allow_half_day',
    'requires_reason',
    'requires_document',
    'is_active',
    'sort_order',
])]
class LeaveType extends Model
{
    use HasFactory;

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
    
    public function employeeLeaveBalances(): HasMany
    {
        return $this->hasMany(EmployeeLeaveBalance::class);
    }
    public function leaveCreditTransactions(): HasMany
    {
        return $this->hasMany(LeaveCreditTransaction::class);
    }
    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'deduct_from_balance' => 'boolean',
            'allow_half_day' => 'boolean',
            'requires_reason' => 'boolean',
            'requires_document' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}