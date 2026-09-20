<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $roles = [
            [
                'code' => 'SUPER_ADMIN',
                'name' => 'Super Admin',
                'description' => 'Full access to all HRIS modules and system configuration.',
            ],
            [
                'code' => 'HR_ADMIN',
                'name' => 'HR Admin',
                'description' => 'Manages HR operations, employees, attendance, leave, and HR configuration.',
            ],
            [
                'code' => 'HR_STAFF',
                'name' => 'HR Staff',
                'description' => 'Handles day-to-day HR transactions and employee records.',
            ],
            [
                'code' => 'PAYROLL_ADMIN',
                'name' => 'Payroll Admin',
                'description' => 'Manages payroll processing, deductions, contributions, and payslips.',
            ],
            [
                'code' => 'MANAGER',
                'name' => 'Manager',
                'description' => 'Manages and reviews requests for assigned employees.',
            ],
            [
                'code' => 'EMPLOYEE',
                'name' => 'Employee',
                'description' => 'Standard employee self-service access.',
            ],
        ];

        foreach ($roles as $role) {
            $existingId = DB::table('roles')
                ->where('code', $role['code'])
                ->value('id');

            if ($existingId) {
                DB::table('roles')
                    ->where('id', $existingId)
                    ->update([
                        'name' => $role['name'],
                        'description' => $role['description'],
                        'is_active' => true,
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('roles')->insert([
                'code' => $role['code'],
                'name' => $role['name'],
                'description' => $role['description'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}