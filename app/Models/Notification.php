<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'type',
    'title',
    'message',
    'source_type',
    'source_id',
    'action_url',
    'priority',
    'read_at',
    'created_by_user_id',
])]
class Notification extends Model
{
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'source_id' => 'integer',
            'read_at' => 'datetime',
            'created_by_user_id' => 'integer',
        ];
    }
}