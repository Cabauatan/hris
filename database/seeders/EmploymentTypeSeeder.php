<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmploymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $employmentTypes = [
            [
                'code' => 'REG',
                'name' => 'Regular',
                'description' => 'Regular employment.',
                'requires_end_date' => false,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'PROB',
                'name' => 'Probationary',
                'description' => 'Probationary employment.',
                'requires_end_date' => false,
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'code' => 'CONT',
                'name' => 'Contractual',
                'description' => 'Employment governed by a defined contract period.',
                'requires_end_date' => true,
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'code' => 'PART',
                'name' => 'Part-Time',
                'description' => 'Part-time employment.',
                'requires_end_date' => false,
                'is_active' => true,
                'sort_order' => 40,
            ],
            [
                'code' => 'PROJ',
                'name' => 'Project-Based',
                'description' => 'Employment associated with a defined project or project period.',
                'requires_end_date' => true,
                'is_active' => true,
                'sort_order' => 50,
            ],
        ];

        foreach ($employmentTypes as $employmentType) {
            $existingId = DB::table('employment_types')
                ->where('code', $employmentType['code'])
                ->value('id');

            if ($existingId) {
                DB::table('employment_types')
                    ->where('id', $existingId)
                    ->update([
                        'name' => $employmentType['name'],
                        'description' => $employmentType['description'],
                        'requires_end_date' => $employmentType['requires_end_date'],
                        'is_active' => $employmentType['is_active'],
                        'sort_order' => $employmentType['sort_order'],
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('employment_types')->insert([
                ...$employmentType,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}