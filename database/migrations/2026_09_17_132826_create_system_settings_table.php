<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();

            /*
             * SETTING GROUP
             *
             * Examples:
             *
             * general
             * employee
             * attendance
             * leave
             * cto
             * payroll
             * notification
             * dashboard
             */
            $table->string('group', 50)
                ->default('general');

            /*
             * UNIQUE SETTING KEY
             *
             * Examples:
             *
             * general.date_format
             * general.time_format
             * attendance.default_grace_minutes
             * payroll.default_pay_frequency
             * notification.email_enabled
             */
            $table->string('key', 150)
                ->unique();

            /*
             * SETTING VALUE
             *
             * Stored as text so we can support
             * strings, numbers, booleans and
             * simple JSON values.
             */
            $table->text('value')
                ->nullable();

            /*
             * VALUE TYPE
             *
             * Suggested values:
             *
             * string
             * integer
             * decimal
             * boolean
             * json
             */
            $table->string('value_type', 20)
                ->default('string');

            /*
             * HUMAN-READABLE LABEL
             *
             * Useful for the Admin Settings UI.
             */
            $table->string('label', 150);

            /*
             * OPTIONAL DESCRIPTION
             */
            $table->text('description')
                ->nullable();

            /*
             * CAN ADMIN EDIT THIS?
             *
             * Some settings may be system-managed
             * but still visible in the UI.
             */
            $table->boolean('is_editable')
                ->default(true);

            /*
             * ACTIVE FLAG
             */
            $table->boolean('is_active')
                ->default(true);

            /*
             * LAST USER WHO CHANGED THE SETTING
             */
            $table->foreignId('updated_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            /*
             * INDEXES
             */
            $table->index(
                ['group', 'is_active'],
                'idx_system_settings_group_active'
            );

            $table->index(
                ['is_editable', 'is_active'],
                'idx_system_settings_editable_active'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};