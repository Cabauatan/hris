<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'employee_number',
    'user_id',

    'first_name',
    'middle_name',
    'last_name',
    'suffix',
    'preferred_name',

    'birth_date',
    'sex',
    'civil_status',

    'personal_email',
    'company_email',
    'mobile_number',

    'department_id',
    'position_id',
    'employment_type_id',
    'employee_status_id',
    'supervisor_id',

    'hire_date',
    'regularization_date',
    'contract_end_date',
    'separation_date',

    'photo_path',
    'is_active',
])]
class Employee extends Model
{
    use HasFactory;

    /**
     * HRIS user account linked to this employee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Current department of the employee.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Current position of the employee.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * Current employment type.
     */
    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }
    public function profileChangeRequests(): HasMany
    {
        return $this->hasMany(EmployeeProfileChangeRequest::class);
    }
    public function payrollResults(): HasMany
    {
        return $this->hasMany(PayrollEmployeeResult::class);
    }
    public function recurringEarnings(): HasMany
    {
        return $this->hasMany(EmployeeRecurringEarning::class);
    }
    public function recurringDeductions(): HasMany
    {
        return $this->hasMany(EmployeeRecurringDeduction::class);
    }
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }
    public function announcementReads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    /**
     * Current employee status.
     */
    public function employeeStatus(): BelongsTo
    {
        return $this->belongsTo(EmployeeStatus::class);
    }

    /**
     * Direct supervisor of the employee.
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(
            Employee::class,
            'supervisor_id'
        );
    }

    /**
     * Employees directly reporting to this employee.
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(
            Employee::class,
            'supervisor_id'
        );
    }
    /**
    * Addresses of the employee.
    */
    public function addresses(): HasMany
    {
        return $this->hasMany(EmployeeAddress::class);
    }

    /**
     * Emergency contacts of the employee.
     */
    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmployeeEmergencyContact::class);
    }

    /**
     * Government IDs of the employee.
     */
    public function governmentIds(): HasMany
    {
        return $this->hasMany(EmployeeGovernmentId::class);
    }

    /**
     * Bank accounts of the employee.
     */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(EmployeeBankAccount::class);
    }


    /**
     * Documents belonging to the employee.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    /**
     * Dependents of the employee.
     */
    public function dependents(): HasMany
    {
        return $this->hasMany(EmployeeDependent::class);
    }
    /**
     * Employment movement/history records of the employee.
     */
    public function employmentHistory(): HasMany
    {
        return $this->hasMany(EmployeeEmploymentHistory::class);
    }
    /**
     * Compensation history of the employee.
     */
    public function compensations(): HasMany
    {
        return $this->hasMany(EmployeeCompensation::class);
    }
    /**
     * Work schedule assignment history of the employee.
     */
    public function scheduleAssignments(): HasMany
    {
        return $this->hasMany(EmployeeScheduleAssignment::class);
    }

    /**
     * Raw attendance/time punches of the employee.
     */
    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }
    /**
     * Processed daily attendance records of the employee.
     */
    public function dailyAttendances(): HasMany
    {
        return $this->hasMany(DailyAttendance::class);
    }

    public function attendanceCorrectionRequests(): HasMany
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }
    public function overtimeRequests(): HasMany
    {
        return $this->hasMany(OvertimeRequest::class);
    }
    public function leaveRequests(): HasMany
    {
       return $this->hasMany(LeaveRequest::class);
    }
    public function leaveBalances(): HasMany
    {
        return $this->hasMany(EmployeeLeaveBalance::class);
    }
    public function leaveCreditTransactions(): HasMany
    {
        return $this->hasMany(LeaveCreditTransaction::class);
    }

    public function ctoEarnings(): HasMany
    {
        return $this->hasMany(CtoEarning::class);
    }
    public function ctoRequests(): HasMany
    {
        return $this->hasMany(CtoRequest::class);
    }
    public function ctoTransactions(): HasMany
    {
        return $this->hasMany(CtoTransaction::class);
    }
    public function ctoBalance(): HasOne
    {
        return $this->hasOne(EmployeeCtoBalance::class);
    }
    public function approvalActions(): HasMany
    {
        return $this->hasMany(ApprovalAction::class);
    }
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'department_id' => 'integer',
            'position_id' => 'integer',
            'employment_type_id' => 'integer',
            'employee_status_id' => 'integer',
            'supervisor_id' => 'integer',

            'birth_date' => 'date',
            'hire_date' => 'date',
            'regularization_date' => 'date',
            'contract_end_date' => 'date',
            'separation_date' => 'date',

            'is_active' => 'boolean',
        ];
    }
}