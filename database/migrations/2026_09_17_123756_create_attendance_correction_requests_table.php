<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_correction_requests', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * DAILY ATTENDANCE RECORD
             *
             * Nullable because correction may be submitted
             * before a daily attendance record exists.
             */
            $table->foreignId('daily_attendance_id')
                ->nullable()
                ->constrained('daily_attendance')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * ATTENDANCE / WORK DATE
             */
            $table->date('attendance_date');

            /*
             * CORRECTION TYPE
             *
             * Suggested values:
             *
             * missing_in
             * missing_out
             * incorrect_in
             * incorrect_out
             * missing_logs
             * other
             */
            $table->string('correction_type', 30);

            /*
             * ORIGINAL VALUES
             *
             * Snapshot of the attendance values when
             * the request was submitted.
             */
            $table->dateTime('original_in')
                ->nullable();

            $table->dateTime('original_out')
                ->nullable();

            /*
             * REQUESTED CORRECTED VALUES
             *
             * Either one or both may be provided.
             */
            $table->dateTime('requested_in')
                ->nullable();

            $table->dateTime('requested_out')
                ->nullable();

            /*
             * EMPLOYEE REASON
             */
            $table->text('reason');

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
             * APPROVAL INFORMATION
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
             * Usually the employee's user account,
             * but HR may encode it for the employee.
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
                ['employee_id', 'attendance_date'],
                'idx_attendance_corrections_employee_date'
            );

            $table->index(
                ['status', 'attendance_date'],
                'idx_attendance_corrections_status_date'
            );

            $table->index(
                ['employee_id', 'status'],
                'idx_attendance_corrections_employee_status'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_correction_requests');
    }
};