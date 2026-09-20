<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'leave_type_id',
    'employee_leave_balance_id',
    'leave_request_id',
    'transaction_type',
    'transaction_date',
    'days',
    'balance_before',
    'balance_after',
    'description',
    'remarks',
    'created_by_user_id',
])]
class LeaveCreditTransaction extends Model
{
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function employeeLeaveBalance(): BelongsTo
    {
        return $this->belongsTo(EmployeeLeaveBalance::class);
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'leave_type_id' => 'integer',
            'employee_leave_balance_id' => 'integer',
            'leave_request_id' => 'integer',

            'transaction_date' => 'date',

            'days' => 'decimal:2',
            'balance_before' => 'decimal:2',
            'balance_after' => 'decimal:2',

            'created_by_user_id' => 'integer',
        ];
    }
}