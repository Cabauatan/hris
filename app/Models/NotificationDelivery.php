<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'notification_id',
    'channel',
    'recipient',
    'status',
    'attempt_count',
    'last_attempt_at',
    'sent_at',
    'next_retry_at',
    'provider_reference',
    'error_message',
])]
class NotificationDelivery extends Model
{
    use HasFactory;

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    protected function casts(): array
    {
        return [
            'notification_id' => 'integer',
            'attempt_count' => 'integer',
            'last_attempt_at' => 'datetime',
            'sent_at' => 'datetime',
            'next_retry_at' => 'datetime',
        ];
    }
}