<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_attendance', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * WORK DATE
             *
             * This is the attendance date, NOT necessarily
             * the calendar date of every time log.
             *
             * Example night shift:
             *
             * Attendance Date: Sep 16
             * Time In:  Sep 16 22:00
             * Time Out: Sep 17 06:00
             */
            $table->date('attendance_date');

            /*
             * SHIFT USED FOR THIS ATTENDANCE
             *
             * Keeping the shift reference helps us know
             * which shift produced this attendance record.
             */
            $table->foreignId('shift_id')
                ->nullable()
                ->constrained('shifts')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * SCHEDULED DATE/TIME
             *
             * Datetime instead of time because overnight
             * shifts can end on the following day.
             */
            $table->dateTime('scheduled_in')
                ->nullable();

            $table->dateTime('scheduled_out')
                ->nullable();

            /*
             * ACTUAL FIRST IN / LAST OUT
             *
             * These are derived from valid raw time logs.
             */
            $table->dateTime('actual_in')
                ->nullable();

            $table->dateTime('actual_out')
                ->nullable();

            /*
             * WORK MINUTES
             *
             * Total payable/recognized worked minutes
             * before payroll-specific computation.
             */
            $table->unsignedInteger('worked_minutes')
                ->default(0);

            /*
             * ATTENDANCE EXCEPTIONS
             */
            $table->unsignedInteger('late_minutes')
                ->default(0);

            $table->unsignedInteger('undertime_minutes')
                ->default(0);

            /*
             * OVERTIME MINUTES
             *
             * Detected/recognized attendance OT.
             *
             * Approval and payroll treatment will be
             * handled separately.
             */
            $table->unsignedInteger('overtime_minutes')
                ->default(0);

            /*
             * DAY CLASSIFICATION
             */
            $table->boolean('is_rest_day')
                ->default(false);

            $table->boolean('is_holiday')
                ->default(false);

            /*
             * If this attendance date matched a holiday,
             * keep the reference.
             */
            $table->foreignId('holiday_id')
                ->nullable()
                ->constrained('holidays')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * ATTENDANCE STATUS
             *
             * Suggested values:
             *
             * present
             * absent
             * incomplete
             * rest_day
             * holiday
             * on_leave
             *
             * Additional statuses can be added later
             * without changing the schema.
             */
            $table->string('status', 30);

            /*
             * PROCESSING STATUS
             *
             * Useful when attendance needs to be
             * recalculated after a correction.
             *
             * Suggested:
             *
             * pending
             * processed
             * needs_review
             */
            $table->string('processing_status', 30)
                ->default('processed');

            /*
             * OPTIONAL REMARKS
             */
            $table->text('remarks')
                ->nullable();

            /*
             * LAST PROCESSING TIME
             */
            $table->timestamp('processed_at')
                ->nullable();

            $table->timestamps();

            /*
             * One attendance result per employee
             * per work date.
             */
            $table->unique(
                ['employee_id', 'attendance_date'],
                'uq_daily_attendance_employee_date'
            );

            /*
             * INDEXES
             */
            $table->index(
                ['attendance_date', 'status'],
                'idx_daily_attendance_date_status'
            );

            $table->index(
                ['employee_id', 'status', 'attendance_date'],
                'idx_daily_attendance_employee_status'
            );

            $table->index(
                ['processing_status', 'attendance_date'],
                'idx_daily_attendance_processing'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_attendance');
    }
};