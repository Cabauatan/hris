<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * ATTENDANCE DATE
             *
             * The work date where the OT belongs.
             *
             * Important for overnight shifts:
             * OT may physically end the next calendar day,
             * but still belong to the original work date.
             */
            $table->date('overtime_date');

            /*
             * REQUESTED OT PERIOD
             *
             * Datetime is used to support overnight OT.
             *
             * Example:
             * 2026-09-16 17:00
             * 2026-09-16 19:00
             */
            $table->dateTime('requested_start');

            $table->dateTime('requested_end');

            /*
             * REQUESTED MINUTES
             *
             * Stored for easier reporting.
             *
             * Example:
             * 120 = 2 hours
             */
            $table->unsignedInteger('requested_minutes');

            /*
             * REASON
             */
            $table->text('reason');

            /*
             * REQUEST STATUS
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
             * Simple one-level approval muna.
             */
            $table->foreignId('approved_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamp('approved_at')
                ->nullable();

            /*
             * APPROVED MINUTES
             *
             * Nullable until approved.
             *
             * Approver may approve less than
             * originally requested.
             */
            $table->unsignedInteger('approved_minutes')
                ->nullable();

            /*
             * APPROVER REMARKS
             */
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
             * Usually employee's own user account,
             * but HR may encode the request for them.
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
                ['employee_id', 'overtime_date'],
                'idx_overtime_employee_date'
            );

            $table->index(
                ['status', 'overtime_date'],
                'idx_overtime_status_date'
            );

            $table->index(
                ['employee_id', 'status'],
                'idx_overtime_employee_status'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
    }
};