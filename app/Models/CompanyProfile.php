<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name',
    'legal_name',
    'registration_number',
    'tin',
    'email',
    'phone',
    'website',
    'address_line_1',
    'address_line_2',
    'city',
    'province',
    'postal_code',
    'country',
    'timezone',
    'currency',
    'logo_path',
    'is_active',
])]
class CompanyProfile extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'company_profile';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}