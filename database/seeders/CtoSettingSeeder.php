<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CtoSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        /*
         * DEFAULT CTO CONFIGURATION
         *
         * These are initial system defaults only.
         * Actual values should follow company policy.
         *
         * This table is treated as a singleton:
         * only one active configuration row is expected.
         */
        $settings = [
            'is_enabled' => true,
            'minimum_eligible_minutes' => 60,
            'earning_multiplier' => 1.00,
            'request_increment_minutes' => 30,
            'minimum_request_minutes' => 60,
            'maximum_balance_minutes' => null,
            'expiry_days' => null,
            'allow_half_day' => true,
            'require_approved_overtime' => true,
            'remarks' => 'Initial CTO configuration. Review and update according to company policy.',
            'updated_at' => $now,
        ];

        $existingId = DB::table('cto_settings')
            ->orderBy('id')
            ->value('id');

        if ($existingId) {
            DB::table('cto_settings')
                ->where('id', $existingId)
                ->update($settings);

            return;
        }

        DB::table('cto_settings')->insert([
            ...$settings,
            'created_at' => $now,
        ]);
    }
}