<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_logs', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * EXACT DATE AND TIME OF THE PUNCH
             *
             * Examples:
             * 2026-09-16 07:58:32
             * 2026-09-16 17:06:11
             */
            $table->dateTime('logged_at');

            /*
             * LOG TYPE
             *
             * in
             * out
             *
             * Nullable because some biometric devices
             * provide only timestamps without IN/OUT.
             */
            $table->string('log_type', 10)
                ->nullable();

            /*
             * SOURCE
             *
             * biometric
             * manual
             * import
             * web
             * mobile
             * api
             */
            $table->string('source', 30)
                ->default('manual');

            /*
             * EXTERNAL DEVICE / SOURCE IDENTIFIER
             *
             * Useful when importing biometric logs.
             *
             * Examples:
             * BIO-01
             * MAIN-OFFICE
             */
            $table->string('device_code', 100)
                ->nullable();

            /*
             * EXTERNAL RECORD ID
             *
             * If the biometric/API source provides its
             * own transaction ID, we can keep it here.
             */
            $table->string('external_reference', 150)
                ->nullable();

            /*
             * MANUAL ENTRY REASON
             *
             * Example:
             * Biometric device unavailable
             * Forgot to clock in
             */
            $table->string('reason', 255)
                ->nullable();

            /*
             * OPTIONAL REMARKS
             */
            $table->text('remarks')
                ->nullable();

            /*
             * WHO CREATED/IMPORTED THE RECORD
             *
             * NULL is allowed for automated device/API
             * records.
             */
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * RECORD STATUS
             *
             * Instead of deleting an invalid raw punch,
             * we can invalidate it and preserve the log.
             */
            $table->boolean('is_valid')
                ->default(true);

            $table->timestamps();

            /*
             * INDEXES
             */
       

            $table->index(
                ['employee_id', 'is_valid', 'logged_at'],
                'idx_time_logs_employee_valid_datetime'
            );

            $table->index(
                ['source', 'logged_at'],
                'idx_time_logs_source_datetime'
            );

            $table->index(
                'external_reference',
                'idx_time_logs_external_reference'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_logs');
    }
};