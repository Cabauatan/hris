<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'calendar_event_id',
    'audience_type',
    'audience_id',
])]
class CalendarEventAudience extends Model
{
    use HasFactory;

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class);
    }

    protected function casts(): array
    {
        return [
            'calendar_event_id' => 'integer',
            'audience_id' => 'integer',
        ];
    }
}