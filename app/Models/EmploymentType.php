<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'name',
    'description',
    'requires_end_date',
    'is_active',
    'sort_order',
])]
class EmploymentType extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_end_date' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}