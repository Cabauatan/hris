<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'group',
    'key',
    'value',
    'value_type',
    'label',
    'description',
    'is_editable',
    'is_active',
    'updated_by_user_id',
])]
class SystemSetting extends Model
{
    use HasFactory;

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'updated_by_user_id'
        );
    }

    protected function casts(): array
    {
        return [
            'is_editable' => 'boolean',
            'is_active' => 'boolean',
            'updated_by_user_id' => 'integer',
        ];
    }
}