<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'id_type',
    'id_number',
    'issued_date',
    'expiry_date',
    'document_path',
    'remarks',
    'is_active',
])]
class EmployeeGovernmentId extends Model
{
    use HasFactory;

    /**
     * Employee who owns this government ID.
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
            'issued_date' => 'date',
            'expiry_date' => 'date',
            'is_active' => 'boolean',
        ];
    }
}