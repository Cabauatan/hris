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
    'is_employed',
    'is_working',
    'is_active',
    'sort_order',
])]
class EmployeeStatus extends Model
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
            'is_employed' => 'boolean',
            'is_working' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}