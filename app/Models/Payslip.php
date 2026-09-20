<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'payroll_employee_result_id',
    'employee_id',
    'payslip_number',
    'status',
    'published_at',
    'published_by_user_id',
    'pdf_path',
    'revoked_at',
    'revoked_by_user_id',
    'revocation_reason',
    'remarks',
])]
class Payslip extends Model
{
    use HasFactory;

    public function payrollEmployeeResult(): BelongsTo
    {
        return $this->belongsTo(PayrollEmployeeResult::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'published_by_user_id'
        );
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'revoked_by_user_id'
        );
    }

    protected function casts(): array
    {
        return [
            'payroll_employee_result_id' => 'integer',
            'employee_id' => 'integer',
            'published_at' => 'datetime',
            'published_by_user_id' => 'integer',
            'revoked_at' => 'datetime',
            'revoked_by_user_id' => 'integer',
        ];
    }
}