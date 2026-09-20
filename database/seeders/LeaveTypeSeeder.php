<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $leaveTypes = [
            [
                'code' => 'VL',
                'name' => 'Vacation Leave',
                'description' => 'Leave used for vacation or planned personal time off.',
                'is_paid' => true,
                'deduct_from_balance' => true,
                'allow_half_day' => true,
                'requires_reason' => true,
                'requires_document' => false,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'code' => 'SL',
                'name' => 'Sick Leave',
                'description' => 'Leave used when an employee is unable to work due to illness.',
                'is_paid' => true,
                'deduct_from_balance' => true,
                'allow_half_day' => true,
                'requires_reason' => true,
                'requires_document' => false,
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'code' => 'EL',
                'name' => 'Emergency Leave',
                'description' => 'Leave used for urgent or unforeseen personal situations.',
                'is_paid' => true,
                'deduct_from_balance' => true,
                'allow_half_day' => true,
                'requires_reason' => true,
                'requires_document' => false,
                'is_active' => true,
                'sort_order' => 30,
            ],
            [
                'code' => 'ML',
                'name' => 'Maternity Leave',
                'description' => 'Maternity-related leave subject to applicable policy and eligibility rules.',
                'is_paid' => true,
                'deduct_from_balance' => false,
                'allow_half_day' => false,
                'requires_reason' => true,
                'requires_document' => true,
                'is_active' => true,
                'sort_order' => 40,
            ],
            [
                'code' => 'PL',
                'name' => 'Paternity Leave',
                'description' => 'Paternity-related leave subject to applicable policy and eligibility rules.',
                'is_paid' => true,
                'deduct_from_balance' => false,
                'allow_half_day' => false,
                'requires_reason' => true,
                'requires_document' => true,
                'is_active' => true,
                'sort_order' => 50,
            ],
            [
                'code' => 'UL',
                'name' => 'Unpaid Leave',
                'description' => 'Approved leave without pay.',
                'is_paid' => false,
                'deduct_from_balance' => false,
                'allow_half_day' => true,
                'requires_reason' => true,
                'requires_document' => false,
                'is_active' => true,
                'sort_order' => 60,
            ],
        ];

        foreach ($leaveTypes as $leaveType) {
            $existingId = DB::table('leave_types')
                ->where('code', $leaveType['code'])
                ->value('id');

            if ($existingId) {
                DB::table('leave_types')
                    ->where('id', $existingId)
                    ->update([
                        'name' => $leaveType['name'],
                        'description' => $leaveType['description'],
                        'is_paid' => $leaveType['is_paid'],
                        'deduct_from_balance' => $leaveType['deduct_from_balance'],
                        'allow_half_day' => $leaveType['allow_half_day'],
                        'requires_reason' => $leaveType['requires_reason'],
                        'requires_document' => $leaveType['requires_document'],
                        'is_active' => $leaveType['is_active'],
                        'sort_order' => $leaveType['sort_order'],
                        'updated_at' => $now,
                    ]);

                continue;
            }

            DB::table('leave_types')->insert([
                ...$leaveType,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}