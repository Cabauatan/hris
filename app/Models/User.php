<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'name',
    'email',
    'password',
    'is_active',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Automatically generate a UUID for new users.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }
        });
    }
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function createdNotifications(): HasMany
    {
        return $this->hasMany(
            Notification::class,
            'created_by_user_id'
        );
    }
    public function reviewedProfileChangeRequests(): HasMany
    {
        return $this->hasMany(
            EmployeeProfileChangeRequest::class,
            'reviewed_by_user_id'
        );
    }

    public function createdProfileChangeRequests(): HasMany
    {
        return $this->hasMany(
            EmployeeProfileChangeRequest::class,
            'created_by_user_id'
        );
    }
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }
    public function delegatedApprovals(): HasMany
    {
        return $this->hasMany(
            ApprovalDelegation::class,
            'delegator_user_id'
        );
    }

    public function receivedApprovalDelegations(): HasMany
    {
        return $this->hasMany(
            ApprovalDelegation::class,
            'delegate_user_id'
        );
    }

    public function createdApprovalDelegations(): HasMany
    {
        return $this->hasMany(
            ApprovalDelegation::class,
            'created_by_user_id'
        );
    }
    public function approvalActions(): HasMany
    {
        return $this->hasMany(
            ApprovalAction::class,
            'acted_by_user_id'
        );
    }

    public function updatedSystemSettings(): HasMany
    {
        return $this->hasMany(
            SystemSetting::class,
            'updated_by_user_id'
        );
    }

    public function createdAnnouncements(): HasMany
    {
        return $this->hasMany(
            Announcement::class,
            'created_by_user_id'
        );
    }

    public function finalizedPayrollPeriods(): HasMany
    {
        return $this->hasMany(
            PayrollPeriod::class,
            'finalized_by_user_id'
        );
    }

    public function lockedPayrollPeriods(): HasMany
    {
        return $this->hasMany(
            PayrollPeriod::class,
            'locked_by_user_id'
        );
    }
    public function processedPayrollRuns(): HasMany
    {
        return $this->hasMany(
            PayrollRun::class,
            'processed_by_user_id'
        );
    }

    public function finalizedPayrollRuns(): HasMany
    {
        return $this->hasMany(
            PayrollRun::class,
            'finalized_by_user_id'
        );
    }
    public function createdRecurringEarnings(): HasMany
    {
        return $this->hasMany(
            EmployeeRecurringEarning::class,
            'created_by_user_id'
        );
    }
    public function createdRecurringDeductions(): HasMany
    {
        return $this->hasMany(
            EmployeeRecurringDeduction::class,
            'created_by_user_id'
        );
    }
    public function createdGovernmentContributionSchedules(): HasMany
    {
        return $this->hasMany(
            GovernmentContributionSchedule::class,
            'created_by_user_id'
        );
    }
    public function overriddenGovernmentContributions(): HasMany
    {
        return $this->hasMany(
            PayrollGovernmentContribution::class,
            'overridden_by_user_id'
        );
    }
    public function createdWithholdingTaxBrackets(): HasMany
    {
        return $this->hasMany(
            WithholdingTaxBracket::class,
            'created_by_user_id'
        );
    }
    public function publishedPayslips(): HasMany
    {
        return $this->hasMany(
            Payslip::class,
            'published_by_user_id'
        );
    }

    public function revokedPayslips(): HasMany
    {
        return $this->hasMany(
            Payslip::class,
            'revoked_by_user_id'
        );
    }

    public function publishedAnnouncements(): HasMany
    {
        return $this->hasMany(
            Announcement::class,
            'published_by_user_id'
        );
    }
    /**
     * Roles assigned to this user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withTimestamps();
    }

    /**
     * Permissions assigned directly to this user.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'permission_user'
        )->withTimestamps();
    }

    /**
     * Determine whether the user has an active role.
     */
    public function hasRole(string $code): bool
    {
        return $this->roles()
            ->where('roles.code', $code)
            ->where('roles.is_active', true)
            ->exists();
    }

    /**
     * Determine whether the user has a permission,
     * either directly or through an active role.
     */
    public function hasPermission(string $code): bool
    {
        $hasDirectPermission = $this->permissions()
            ->where('permissions.code', $code)
            ->where('permissions.is_active', true)
            ->exists();

        if ($hasDirectPermission) {
            return true;
        }

        return $this->roles()
            ->where('roles.is_active', true)
            ->whereHas('permissions', function ($query) use ($code) {
                $query->where('permissions.code', $code)
                    ->where('permissions.is_active', true);
            })
            ->exists();
    }
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
    public function createdCalendarEvents(): HasMany
    {
        return $this->hasMany(
            CalendarEvent::class,
            'created_by_user_id'
        );
    }

    public function publishedCalendarEvents(): HasMany
    {
        return $this->hasMany(
            CalendarEvent::class,
            'published_by_user_id'
        );
    }

    public function cancelledCalendarEvents(): HasMany
    {
        return $this->hasMany(
            CalendarEvent::class,
            'cancelled_by_user_id'
        );
    }

    /**
     * Determine whether the user account is active.
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}