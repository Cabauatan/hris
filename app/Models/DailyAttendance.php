<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'employee_id',
    'attendance_date',
    'shift_id',
    'scheduled_in',
    'scheduled_out',
    'actual_in',
    'actual_out',
    'worked_minutes',
    'late_minutes',
    'undertime_minutes',
    'overtime_minutes',
    'is_rest_day',
    'is_holiday',
    'holiday_id',
    'status',
    'processing_status',
    'remarks',
    'processed_at',
])]
class DailyAttendance extends Model
{
    use HasFactory;

    protected $table = 'daily_attendance';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function holiday(): BelongsTo
    {
        return $this->belongsTo(Holiday::class);
    }

    public function correctionRequests(): HasMany
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'attendance_date' => 'date',
            'shift_id' => 'integer',

            'scheduled_in' => 'datetime',
            'scheduled_out' => 'datetime',
            'actual_in' => 'datetime',
            'actual_out' => 'datetime',

            'worked_minutes' => 'integer',
            'late_minutes' => 'integer',
            'undertime_minutes' => 'integer',
            'overtime_minutes' => 'integer',

            'is_rest_day' => 'boolean',
            'is_holiday' => 'boolean',
            'holiday_id' => 'integer',

            'processed_at' => 'datetime',
        ];
    }
}