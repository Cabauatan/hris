<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            /*
             * LOGIN / DISPLAY INFORMATION
             */
            $table->string('name', 150);

            $table->string('email', 150)
                ->unique();

            $table->timestamp('email_verified_at')
                ->nullable();

            $table->string('password');

            /*
             * ACCOUNT STATUS
             *
             * Allows us to disable login access without
             * deleting the user account.
             */
            $table->boolean('is_active')
                ->default(true);

            /*
             * Useful for showing the user's last login
             * in the admin panel later.
             */
            $table->timestamp('last_login_at')
                ->nullable();

            $table->rememberToken();

            $table->timestamps();

            $table->index(
                'is_active',
                'idx_users_active'
            );
        });

        /*
         * Laravel password reset support.
         */
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();

            $table->string('token');

            $table->timestamp('created_at')
                ->nullable();
        });

        /*
         * Laravel database session support.
         *
         * Keep this because we may use database
         * sessions for the web application.
         */
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();

            $table->foreignId('user_id')
                ->nullable()
                ->index();

            $table->string('ip_address', 45)
                ->nullable();

            $table->text('user_agent')
                ->nullable();

            $table->longText('payload');

            $table->integer('last_activity')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};