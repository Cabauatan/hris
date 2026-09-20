<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $rolePermissions = [
            'HR_ADMIN' => [
                'dashboard.view',

                'company.view',
                'company.update',

                'divisions.view',
                'divisions.manage',
                'departments.view',
                'departments.manage',
                'positions.view',
                'positions.manage',

                'employees.view',
                'employees.create',
                'employees.update',
                'employees.deactivate',
                'employees.compensation.view',
                'employees.compensation.manage',
                'employees.documents.view',
                'employees.documents.manage',

                'attendance.view',
                'attendance.manage',
                'time-logs.view',
                'time-logs.manage',

                'attendance-corrections.view',
                'attendance-corrections.create',
                'attendance-corrections.approve',

                'shifts.view',
                'shifts.manage',
                'work-schedules.view',
                'work-schedules.manage',
                'employee-schedules.assign',

                'overtime.view',
                'overtime.create',
                'overtime.approve',

                'leave.view',
                'leave.create',
                'leave.approve',
                'leave-balances.view',
                'leave-credits.manage',
                'leave-types.view',
                'leave-types.manage',

                'cto.view',
                'cto.create',
                'cto.approve',
                'cto-credits.manage',
                'cto-settings.manage',

                'announcements.view',
                'announcements.manage',

                'calendar.view',
                'calendar.manage',

                'approvals.view',
                'approval-delegations.manage',

                'notifications.view',

                'reports.hr.view',
                'reports.attendance.view',

                'users.view',

                'system-settings.view',
                'audit-logs.view',
            ],

            'HR_STAFF' => [
                'dashboard.view',

                'company.view',
                'divisions.view',
                'departments.view',
                'positions.view',

                'employees.view',
                'employees.create',
                'employees.update',
                'employees.documents.view',
                'employees.documents.manage',

                'attendance.view',
                'attendance.manage',
                'time-logs.view',

                'attendance-corrections.view',
                'attendance-corrections.create',

                'shifts.view',
                'work-schedules.view',

                'overtime.view',

                'leave.view',
                'leave-balances.view',
                'leave-types.view',

                'cto.view',

                'announcements.view',
                'calendar.view',
                'notifications.view',

                'reports.hr.view',
                'reports.attendance.view',
            ],

            'PAYROLL_ADMIN' => [
                'dashboard.view',

                'employees.view',
                'employees.compensation.view',

                'attendance.view',
                'time-logs.view',

                'payroll.view',
                'payroll.create',
                'payroll.process',
                'payroll.finalize',

                'payslips.view',
                'payslips.generate',

                'recurring-earnings.manage',
                'recurring-deductions.manage',

                'government-contributions.manage',
                'withholding-tax.manage',

                'notifications.view',

                'reports.payroll.view',
                'audit-logs.view',
            ],

            'MANAGER' => [
                'dashboard.view',

                'employees.view',
                'attendance.view',

                'attendance-corrections.view',
                'attendance-corrections.create',
                'attendance-corrections.approve',

                'overtime.view',
                'overtime.create',
                'overtime.approve',

                'leave.view',
                'leave.create',
                'leave.approve',
                'leave-balances.view',

                'cto.view',
                'cto.create',
                'cto.approve',

                'approvals.view',
                'approval-delegations.manage',

                'announcements.view',
                'calendar.view',
                'notifications.view',

                'self.profile.view',
                'self.profile.update',
                'self.attendance.view',
                'self.leave-balance.view',
                'self.payslips.view',
            ],

            'EMPLOYEE' => [
                'dashboard.view',

                'self.profile.view',
                'self.profile.update',
                'self.attendance.view',
                'self.leave-balance.view',
                'self.payslips.view',

                'attendance-corrections.create',
                'overtime.create',
                'leave.create',
                'cto.create',

                'announcements.view',
                'calendar.view',
                'notifications.view',
            ],
        ];

        DB::transaction(function () use ($rolePermissions, $now) {
            /*
             * SUPER ADMIN
             *
             * Receives every active permission.
             */
            $superAdminRoleId = DB::table('roles')
                ->where('code', 'SUPER_ADMIN')
                ->value('id');

            if (! $superAdminRoleId) {
                throw new \RuntimeException(
                    'Role [SUPER_ADMIN] not found.'
                );
            }

            $allPermissionIds = DB::table('permissions')
                ->where('is_active', true)
                ->pluck('id')
                ->all();

            $this->syncRolePermissions(
                $superAdminRoleId,
                $allPermissionIds,
                $now
            );

            /*
             * OTHER ROLES
             */
            foreach ($rolePermissions as $roleCode => $permissionCodes) {
                $roleId = DB::table('roles')
                    ->where('code', $roleCode)
                    ->value('id');

                if (! $roleId) {
                    throw new \RuntimeException(
                        "Role [{$roleCode}] not found."
                    );
                }

                $permissions = DB::table('permissions')
                    ->whereIn('code', $permissionCodes)
                    ->pluck('id', 'code');

                $missingPermissions = array_diff(
                    $permissionCodes,
                    $permissions->keys()->all()
                );

                if ($missingPermissions !== []) {
                    throw new \RuntimeException(
                        "Missing permissions for role [{$roleCode}]: "
                        . implode(', ', $missingPermissions)
                    );
                }

                $this->syncRolePermissions(
                    $roleId,
                    $permissions->values()->all(),
                    $now
                );
            }
        });
    }

    /**
     * Synchronize a role's permission assignments.
     */
    private function syncRolePermissions(
        int $roleId,
        array $permissionIds,
        $now
    ): void {
        DB::table('permission_role')
            ->where('role_id', $roleId)
            ->delete();

        if ($permissionIds === []) {
            return;
        }

        $rows = array_map(
            fn ($permissionId) => [
                'permission_id' => $permissionId,
                'role_id' => $roleId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $permissionIds
        );

        DB::table('permission_role')->insert($rows);
    }
}