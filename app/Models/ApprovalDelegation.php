<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'delegator_user_id',
    'delegate_user_id',
    'request_type',
    'effective_from',
    'effective_to',
    'reason',
    'is_active',
    'created_by_user_id',
])]
class ApprovalDelegation extends Model
{
    use HasFactory;

    public function delegator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegator_user_id');
    }

    public function delegate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'delegator_user_id' => 'integer',
            'delegate_user_id' => 'integer',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
            'is_active' => 'boolean',
            'created_by_user_id' => 'integer',
        ];
    }
}