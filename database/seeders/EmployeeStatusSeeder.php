<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $statuses = [
            [
                'code' => 'ACTIVE',
                'name' => 'Active',
                'description' => 'Employee is actively employed and working.',
                'is_employed' => true,
                'is_working' => true,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'ON_LEAVE',
                'name' => 'On Leave',
                'description' => 'Employee remains employed but is temporarily not participating in normal work processing.',
                'is_employed' => true,
                'is_working' => false,
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'code' => 'SUSPENDED',
                'name' => 'Suspended',
                'description' => 'Employee remains employed but is temporarily suspended from work.',
                'is_employed' => true,
                'is_working' => false,
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'code' => 'RESIGNED',
                'name' => 'Resigned',
                'description' => 'Employee has voluntarily ended employment.',
                'is_employed' => false,
                'is_working' => false,
                'is_active' => true,
                'sort_order' => 40,
            ],
            [
                'code' => 'TERMINATED',
                'name' => 'Terminated',
                'description' => 'Employee employment has been terminated.',
                'is_employed' => false,
                'is_working' => false,
                'is_active' => true,
                'sort_order' => 50,
            ],
            [
                'code' => 'RETIRED',
                'name' => 'Retired',
                'description' => 'Employee has retired from the company.',
                'is_employed' => false,
                'is_working' => false,
                'is_active' => true,
                'sort_order' => 60,
            ],
            [
                'code' => 'END_OF_CONTRACT',
                'name' => 'End of Contract',
                'description' => 'Employee employment ended upon completion or expiration of the applicable contract.',
                'is_employed' => false,
                'is_working' => false,
                'is_active' => true,
                'sort_order' => 70,
            ],
        ];

        foreach ($statuses as $status) {
            $existingId = DB::table('employee_statuses')
                ->where('code', $status['code'])
                ->value('id');

            if ($existingId) {
                DB::table('employee_statuses')
                    ->where('id', $existingId)
                    ->update([
                        'name' => $status['name'],
                        'description' => $status['description'],
                        'is_employed' => $status['is_employed'],
                        'is_working' => $status['is_working'],
                        'is_active' => $status['is_active'],
                        'sort_order' => $status['sort_order'],
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('employee_statuses')->insert([
                ...$status,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}