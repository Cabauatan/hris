<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'document_type',
    'title',
    'document_number',
    'file_path',
    'original_filename',
    'mime_type',
    'file_size',
    'issued_date',
    'expiry_date',
    'has_expiry',
    'remarks',
    'is_active',
])]
class EmployeeDocument extends Model
{
    use HasFactory;

    /**
     * Employee who owns this document.
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
            'file_size' => 'integer',
            'issued_date' => 'date',
            'expiry_date' => 'date',
            'has_expiry' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}