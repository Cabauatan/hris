<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'daily_attendance_id',
    'attendance_date',
    'correction_type',
    'original_in',
    'original_out',
    'requested_in',
    'requested_out',
    'reason',
    'status',
    'approved_by_user_id',
    'approved_at',
    'approval_remarks',
    'cancelled_at',
    'cancellation_reason',
    'created_by_user_id',
])]
class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function dailyAttendance(): BelongsTo
    {
        return $this->belongsTo(DailyAttendance::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'daily_attendance_id' => 'integer',
            'attendance_date' => 'date',

            'original_in' => 'datetime',
            'original_out' => 'datetime',
            'requested_in' => 'datetime',
            'requested_out' => 'datetime',

            'approved_by_user_id' => 'integer',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'created_by_user_id' => 'integer',
        ];
    }
}