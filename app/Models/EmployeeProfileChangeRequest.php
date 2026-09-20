<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'change_type',
    'target_type',
    'target_id',
    'requested_changes',
    'reason',
    'document_path',
    'status',
    'reviewed_by_user_id',
    'reviewed_at',
    'review_remarks',
    'cancelled_at',
    'cancellation_reason',
    'created_by_user_id',
])]
class EmployeeProfileChangeRequest extends Model
{
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by_user_id'
        );
    }

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
            'employee_id' => 'integer',
            'target_id' => 'integer',
            'requested_changes' => 'array',
            'reviewed_by_user_id' => 'integer',
            'reviewed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'created_by_user_id' => 'integer',
        ];
    }
}