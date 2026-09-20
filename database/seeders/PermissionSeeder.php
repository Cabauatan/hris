<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $permissions = [
            // Dashboard
            ['code' => 'dashboard.view', 'name' => 'View Dashboard', 'module' => 'dashboard'],

            // Company / Organization
            ['code' => 'company.view', 'name' => 'View Company Profile', 'module' => 'organization'],
            ['code' => 'company.update', 'name' => 'Update Company Profile', 'module' => 'organization'],

            ['code' => 'divisions.view', 'name' => 'View Divisions', 'module' => 'organization'],
            ['code' => 'divisions.manage', 'name' => 'Manage Divisions', 'module' => 'organization'],

            ['code' => 'departments.view', 'name' => 'View Departments', 'module' => 'organization'],
            ['code' => 'departments.manage', 'name' => 'Manage Departments', 'module' => 'organization'],

            ['code' => 'positions.view', 'name' => 'View Positions', 'module' => 'organization'],
            ['code' => 'positions.manage', 'name' => 'Manage Positions', 'module' => 'organization'],

            // Employees
            ['code' => 'employees.view', 'name' => 'View Employees', 'module' => 'employees'],
            ['code' => 'employees.create', 'name' => 'Create Employees', 'module' => 'employees'],
            ['code' => 'employees.update', 'name' => 'Update Employees', 'module' => 'employees'],
            ['code' => 'employees.deactivate', 'name' => 'Deactivate Employees', 'module' => 'employees'],

            ['code' => 'employees.compensation.view', 'name' => 'View Employee Compensation', 'module' => 'employees'],
            ['code' => 'employees.compensation.manage', 'name' => 'Manage Employee Compensation', 'module' => 'employees'],

            ['code' => 'employees.documents.view', 'name' => 'View Employee Documents', 'module' => 'employees'],
            ['code' => 'employees.documents.manage', 'name' => 'Manage Employee Documents', 'module' => 'employees'],

            // Attendance
            ['code' => 'attendance.view', 'name' => 'View Attendance', 'module' => 'attendance'],
            ['code' => 'attendance.manage', 'name' => 'Manage Attendance', 'module' => 'attendance'],

            ['code' => 'time-logs.view', 'name' => 'View Time Logs', 'module' => 'attendance'],
            ['code' => 'time-logs.manage', 'name' => 'Manage Time Logs', 'module' => 'attendance'],

            ['code' => 'attendance-corrections.view', 'name' => 'View Attendance Corrections', 'module' => 'attendance'],
            ['code' => 'attendance-corrections.create', 'name' => 'Create Attendance Corrections', 'module' => 'attendance'],
            ['code' => 'attendance-corrections.approve', 'name' => 'Approve Attendance Corrections', 'module' => 'attendance'],

            // Scheduling
            ['code' => 'shifts.view', 'name' => 'View Shifts', 'module' => 'scheduling'],
            ['code' => 'shifts.manage', 'name' => 'Manage Shifts', 'module' => 'scheduling'],

            ['code' => 'work-schedules.view', 'name' => 'View Work Schedules', 'module' => 'scheduling'],
            ['code' => 'work-schedules.manage', 'name' => 'Manage Work Schedules', 'module' => 'scheduling'],

            ['code' => 'employee-schedules.assign', 'name' => 'Assign Employee Schedules', 'module' => 'scheduling'],

            // Overtime
            ['code' => 'overtime.view', 'name' => 'View Overtime Requests', 'module' => 'overtime'],
            ['code' => 'overtime.create', 'name' => 'Create Overtime Requests', 'module' => 'overtime'],
            ['code' => 'overtime.approve', 'name' => 'Approve Overtime Requests', 'module' => 'overtime'],

            // Leave
            ['code' => 'leave.view', 'name' => 'View Leave Requests', 'module' => 'leave'],
            ['code' => 'leave.create', 'name' => 'Create Leave Requests', 'module' => 'leave'],
            ['code' => 'leave.approve', 'name' => 'Approve Leave Requests', 'module' => 'leave'],

            ['code' => 'leave-balances.view', 'name' => 'View Leave Balances', 'module' => 'leave'],
            ['code' => 'leave-credits.manage', 'name' => 'Manage Leave Credits', 'module' => 'leave'],

            ['code' => 'leave-types.view', 'name' => 'View Leave Types', 'module' => 'leave'],
            ['code' => 'leave-types.manage', 'name' => 'Manage Leave Types', 'module' => 'leave'],

            // CTO
            ['code' => 'cto.view', 'name' => 'View CTO', 'module' => 'cto'],
            ['code' => 'cto.create', 'name' => 'Create CTO Requests', 'module' => 'cto'],
            ['code' => 'cto.approve', 'name' => 'Approve CTO Requests', 'module' => 'cto'],
            ['code' => 'cto-credits.manage', 'name' => 'Manage CTO Credits', 'module' => 'cto'],
            ['code' => 'cto-settings.manage', 'name' => 'Manage CTO Settings', 'module' => 'cto'],

            // Payroll
            ['code' => 'payroll.view', 'name' => 'View Payroll', 'module' => 'payroll'],
            ['code' => 'payroll.create', 'name' => 'Create Payroll Runs', 'module' => 'payroll'],
            ['code' => 'payroll.process', 'name' => 'Process Payroll', 'module' => 'payroll'],
            ['code' => 'payroll.finalize', 'name' => 'Finalize Payroll', 'module' => 'payroll'],

            ['code' => 'payslips.view', 'name' => 'View Payslips', 'module' => 'payroll'],
            ['code' => 'payslips.generate', 'name' => 'Generate Payslips', 'module' => 'payroll'],

            ['code' => 'recurring-earnings.manage', 'name' => 'Manage Recurring Earnings', 'module' => 'payroll'],
            ['code' => 'recurring-deductions.manage', 'name' => 'Manage Recurring Deductions', 'module' => 'payroll'],

            ['code' => 'government-contributions.manage', 'name' => 'Manage Government Contributions', 'module' => 'payroll'],
            ['code' => 'withholding-tax.manage', 'name' => 'Manage Withholding Tax', 'module' => 'payroll'],

            // Employee Self-Service
            ['code' => 'self.profile.view', 'name' => 'View Own Profile', 'module' => 'self-service'],
            ['code' => 'self.profile.update', 'name' => 'Update Own Profile', 'module' => 'self-service'],
            ['code' => 'self.attendance.view', 'name' => 'View Own Attendance', 'module' => 'self-service'],
            ['code' => 'self.leave-balance.view', 'name' => 'View Own Leave Balance', 'module' => 'self-service'],
            ['code' => 'self.payslips.view', 'name' => 'View Own Payslips', 'module' => 'self-service'],

            // Announcements
            ['code' => 'announcements.view', 'name' => 'View Announcements', 'module' => 'announcements'],
            ['code' => 'announcements.manage', 'name' => 'Manage Announcements', 'module' => 'announcements'],

            // Calendar
            ['code' => 'calendar.view', 'name' => 'View Calendar', 'module' => 'calendar'],
            ['code' => 'calendar.manage', 'name' => 'Manage Calendar Events', 'module' => 'calendar'],

            // Approvals / Delegation
            ['code' => 'approvals.view', 'name' => 'View Approval History', 'module' => 'approvals'],
            ['code' => 'approval-delegations.manage', 'name' => 'Manage Approval Delegations', 'module' => 'approvals'],

            // Notifications
            ['code' => 'notifications.view', 'name' => 'View Notifications', 'module' => 'notifications'],

            // Reports
            ['code' => 'reports.hr.view', 'name' => 'View HR Reports', 'module' => 'reports'],
            ['code' => 'reports.attendance.view', 'name' => 'View Attendance Reports', 'module' => 'reports'],
            ['code' => 'reports.payroll.view', 'name' => 'View Payroll Reports', 'module' => 'reports'],

            // Security / RBAC
            ['code' => 'users.view', 'name' => 'View Users', 'module' => 'security'],
            ['code' => 'users.manage', 'name' => 'Manage Users', 'module' => 'security'],

            ['code' => 'roles.view', 'name' => 'View Roles', 'module' => 'security'],
            ['code' => 'roles.manage', 'name' => 'Manage Roles', 'module' => 'security'],

            ['code' => 'permissions.view', 'name' => 'View Permissions', 'module' => 'security'],
            ['code' => 'permissions.manage', 'name' => 'Manage Permissions', 'module' => 'security'],

            // System
            ['code' => 'system-settings.view', 'name' => 'View System Settings', 'module' => 'system'],
            ['code' => 'system-settings.manage', 'name' => 'Manage System Settings', 'module' => 'system'],

            ['code' => 'audit-logs.view', 'name' => 'View Audit Logs', 'module' => 'system'],
        ];

        foreach ($permissions as $permission) {
            $existingId = DB::table('permissions')
                ->where('code', $permission['code'])
                ->value('id');

            if ($existingId) {
                DB::table('permissions')
                    ->where('id', $existingId)
                    ->update([
                        'name' => $permission['name'],
                        'module' => $permission['module'],
                        'description' => null,
                        'is_active' => true,
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('permissions')->insert([
                'code' => $permission['code'],
                'name' => $permission['name'],
                'module' => $permission['module'],
                'description' => null,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}