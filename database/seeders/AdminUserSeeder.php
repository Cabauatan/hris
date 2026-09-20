<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        /*
         * INITIAL SUPER ADMIN USER
         *
         * IMPORTANT:
         * These credentials are intended for local development/bootstrap only.
         * Do not use hardcoded credentials in production.
         */
        $email = 'admin@hris.local';
        $password = 'ChangeMe123!';

        $userId = DB::table('users')
            ->where('email', $email)
            ->value('id');

        /*
         * Create the bootstrap admin only when it does not exist.
         *
         * Intentionally do not reset the password or UUID on reruns.
         */
        if (! $userId) {
            $userId = DB::table('users')->insertGetId([
                'uuid' => (string) Str::uuid(),
                'name' => 'System Administrator',
                'email' => $email,
                'email_verified_at' => $now,
                'password' => Hash::make($password),
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        /*
         * Find SUPER ADMIN role using the stable role code.
         */
        $superAdminRoleId = DB::table('roles')
            ->where('code', 'SUPER_ADMIN')
            ->value('id');

        if (! $superAdminRoleId) {
            throw new \RuntimeException(
                'Role [SUPER_ADMIN] not found. Run RoleSeeder before AdminUserSeeder.'
            );
        }

        /*
         * Assign SUPER ADMIN role without duplicating the pivot record.
         */
        $roleAssignmentExists = DB::table('role_user')
            ->where('role_id', $superAdminRoleId)
            ->where('user_id', $userId)
            ->exists();

        if (! $roleAssignmentExists) {
            DB::table('role_user')->insert([
                'role_id' => $superAdminRoleId,
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}