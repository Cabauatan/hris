<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * LEAVE TYPE
             *
             * Examples:
             * Vacation Leave
             * Sick Leave
             * Emergency Leave
             * Unpaid Leave
             */
            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * LEAVE PERIOD
             */
            $table->date('start_date');
            $table->date('end_date');

            /*
             * DAY PORTION
             *
             * Suggested values:
             *
             * full_day
             * first_half
             * second_half
             *
             * Half-day is primarily intended for
             * single-day leave requests in V1.
             */
            $table->string('day_part', 20)
                ->default('full_day');

            /*
             * REQUESTED LEAVE DAYS
             *
             * Examples:
             * 1.00
             * 0.50
             * 3.00
             * 4.50
             *
             * Computed by the application based on
             * schedule, rest days, holidays, etc.
             */
            $table->decimal('requested_days', 8, 2);

            /*
             * EMPLOYEE REASON
             */
            $table->text('reason')
                ->nullable();

            /*
             * SUPPORTING DOCUMENT
             *
             * Example:
             * Medical certificate
             *
             * Store file in private storage.
             */
            $table->string('document_path', 500)
                ->nullable();

            /*
             * REQUEST STATUS
             *
             * pending
             * approved
             * rejected
             * cancelled
             */
            $table->string('status', 20)
                ->default('pending');

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
             * Usually employee's user account,
             * but HR may encode on their behalf.
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
                ['employee_id', 'start_date', 'end_date'],
                'idx_leave_requests_employee_dates'
            );

            $table->index(
                ['employee_id', 'status'],
                'idx_leave_requests_employee_status'
            );

            $table->index(
                ['status', 'start_date'],
                'idx_leave_requests_status_date'
            );

            $table->index(
                ['leave_type_id', 'start_date'],
                'idx_leave_requests_type_date'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};