<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'title',
    'content',
    'category',
    'priority',
    'publish_at',
    'expires_at',
    'status',
    'is_pinned',
    'attachment_path',
    'created_by_user_id',
    'published_by_user_id',
    'published_at',
])]
class Announcement extends Model
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
    public function audiences(): HasMany
    {
        return $this->hasMany(AnnouncementAudience::class);
    }
    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    protected function casts(): array
    {
        return [
            'publish_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_pinned' => 'boolean',
            'created_by_user_id' => 'integer',
            'published_by_user_id' => 'integer',
            'published_at' => 'datetime',
        ];
    }
}