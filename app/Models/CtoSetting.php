<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'is_enabled',
    'minimum_eligible_minutes',
    'earning_multiplier',
    'request_increment_minutes',
    'minimum_request_minutes',
    'maximum_balance_minutes',
    'expiry_days',
    'allow_half_day',
    'require_approved_overtime',
    'remarks',
])]
class CtoSetting extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'minimum_eligible_minutes' => 'integer',
            'earning_multiplier' => 'decimal:2',
            'request_increment_minutes' => 'integer',
            'minimum_request_minutes' => 'integer',
            'maximum_balance_minutes' => 'integer',
            'expiry_days' => 'integer',
            'allow_half_day' => 'boolean',
            'require_approved_overtime' => 'boolean',
        ];
    }
}