<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cto_requests', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * CTO DATE
             *
             * The date when the employee wants
             * to use CTO.
             */
            $table->date('cto_date');

            /*
             * USAGE TYPE
             *
             * Suggested values:
             *
             * full_day
             * first_half
             * second_half
             * hourly
             */
            $table->string('usage_type', 20)
                ->default('full_day');

            /*
             * OPTIONAL TIME RANGE
             *
             * Mainly used for hourly CTO.
             *
             * Example:
             * 14:00 - 16:00
             */
            $table->time('start_time')
                ->nullable();

            $table->time('end_time')
                ->nullable();

            /*
             * REQUESTED CTO MINUTES
             *
             * Examples:
             *
             * 60  = 1 hour
             * 120 = 2 hours
             * 240 = 4 hours
             * 480 = 8 hours
             *
             * Application calculates/validates this
             * using the employee's schedule and
             * CTO settings.
             */
            $table->unsignedInteger('requested_minutes');

            /*
             * EMPLOYEE REASON
             */
            $table->text('reason');

            /*
             * STATUS
             *
             * Suggested values:
             *
             * pending
             * approved
             * rejected
             * cancelled
             */
            $table->string('status', 20)
                ->default('pending');

            /*
             * APPROVAL
             *
             * Simple one-level approval for V1.
             */
            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamp('approved_at')
                ->nullable();

            $table->text('approval_remarks')
                ->nullable();

            /*
             * CANCELLATION
             */
            $table->timestamp('cancelled_at')
                ->nullable();

            $table->string('cancellation_reason', 255)
                ->nullable();

            /*
             * REQUEST CREATOR
             *
             * Usually the employee's account.
             * HR can also encode on behalf
             * of an employee.
             */
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            /*
             * INDEXES
             */
            $table->index(
                ['employee_id', 'cto_date'],
                'idx_cto_requests_employee_date'
            );

            $table->index(
                ['employee_id', 'status'],
                'idx_cto_requests_employee_status'
            );

            $table->index(
                ['status', 'cto_date'],
                'idx_cto_requests_status_date'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cto_requests');
    }
};