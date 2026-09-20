<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'description',
    'event_type',
    'starts_at',
    'ends_at',
    'is_all_day',
    'location',
    'status',
    'attachment_path',
    'created_by_user_id',
    'published_at',
    'published_by_user_id',
    'cancelled_at',
    'cancelled_by_user_id',
    'cancellation_reason',
])]
class CalendarEvent extends Model
{
    use HasFactory;

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_user_id');
    }
    public function audiences(): HasMany
    {
        return $this->hasMany(CalendarEventAudience::class);
    }

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_all_day' => 'boolean',

            'created_by_user_id' => 'integer',

            'published_at' => 'datetime',
            'published_by_user_id' => 'integer',

            'cancelled_at' => 'datetime',
            'cancelled_by_user_id' => 'integer',
        ];
    }
}