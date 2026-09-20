<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'request_type',
    'request_id',
    'employee_id',
    'action',
    'acted_by_user_id',
    'acted_at',
    'remarks',
])]
class ApprovalAction extends Model
{
    use HasFactory;

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'request_id' => 'integer',
            'employee_id' => 'integer',
            'acted_by_user_id' => 'integer',
            'acted_at' => 'datetime',
        ];
    }
}