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
    'birth_date',
    'sex',
    'is_dependent',
    'is_beneficiary',
    'mobile_number',
    'remarks',
    'is_active',
])]
class EmployeeDependent extends Model
{
    use HasFactory;

    /**
     * Employee who owns this dependent record.
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
            'birth_date' => 'date',
            'is_dependent' => 'boolean',
            'is_beneficiary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}