<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'first_name',
    'middle_name',
    'last_name',
    'suffix',
    'relationship',
    'mobile_number',
    'alternate_number',
    'email',
    'address_line_1',
    'address_line_2',
    'barangay',
    'city_municipality',
    'province',
    'postal_code',
    'country',
    'is_primary',
    'is_active',
])]
class EmployeeEmergencyContact extends Model
{
    use HasFactory;

    /**
     * Employee who owns this emergency contact.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}