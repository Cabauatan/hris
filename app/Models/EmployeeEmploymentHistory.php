<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'employee_id',
    'movement_type',
    'department_id',
    'position_id',
    'employment_type_id',
    'employee_status_id',
    'supervisor_id',
    'effective_date',
    'end_date',
    'reason',
    'remarks',
    'created_by_user_id',
])]
class EmployeeEmploymentHistory extends Model
{
    use HasFactory;

    protected $table = 'employee_employment_history';

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }

    public function employeeStatus(): BelongsTo
    {
        return $this->belongsTo(EmployeeStatus::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'department_id' => 'integer',
            'position_id' => 'integer',
            'employment_type_id' => 'integer',
            'employee_status_id' => 'integer',
            'supervisor_id' => 'integer',
            'created_by_user_id' => 'integer',

            'effective_date' => 'date',
            'end_date' => 'date',
        ];
    }
}