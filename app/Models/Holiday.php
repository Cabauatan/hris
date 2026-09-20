<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'holiday_date',
    'holiday_type',
    'is_working_day',
    'description',
    'is_active',
])]
class Holiday extends Model
{
    use HasFactory;

    /**
     * Daily attendance records associated with this holiday.
     */
    public function dailyAttendances(): HasMany
    {
        return $this->hasMany(DailyAttendance::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'holiday_date' => 'date',
            'is_working_day' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}