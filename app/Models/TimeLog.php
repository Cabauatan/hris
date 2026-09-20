<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'logged_at',
    'log_type',
    'source',
    'device_code',
    'external_reference',
    'reason',
    'remarks',
    'created_by_user_id',
    'is_valid',
])]
class TimeLog extends Model
{
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'logged_at' => 'datetime',
            'created_by_user_id' => 'integer',
            'is_valid' => 'boolean',
        ];
    }
}