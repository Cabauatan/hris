<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_schedule_assignments', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * WORK SCHEDULE
             *
             * Example:
             * OFFICE-5D
             * NIGHT-5D
             */
            $table->foreignId('work_schedule_id')
                ->constrained('work_schedules')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * EFFECTIVITY
             *
             * Example:
             *
             * OFFICE-5D
             * Jan 01 → Jun 30
             *
             * NIGHT-5D
             * Jul 01 → present
             */
            $table->date('effective_from');

            /*
             * NULL means the assignment currently
             * has no specified end date.
             */
            $table->date('effective_to')
                ->nullable();

            /*
             * CURRENT ASSIGNMENT
             *
             * Application layer will ensure normally
             * only one current schedule assignment
             * exists per employee.
             */
            $table->boolean('is_current')
                ->default(true);

            /*
             * OPTIONAL REASON
             *
             * Examples:
             * Initial schedule
             * Shift transfer
             * Department transfer
             * Schedule change
             */
            $table->string('reason', 255)
                ->nullable();

            /*
             * OPTIONAL NOTES
             */
            $table->text('remarks')
                ->nullable();

            /*
             * WHO ASSIGNED THE SCHEDULE
             */
            $table->foreignId('assigned_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            /*
             * INDEXES
             */
            $table->index(
                ['employee_id', 'is_current'],
                'idx_employee_schedule_current'
            );

            $table->index(
                ['employee_id', 'effective_from', 'effective_to'],
                'idx_employee_schedule_effectivity'
            );

            $table->index(
                ['work_schedule_id', 'is_current'],
                'idx_employee_schedule_work_schedule'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_schedule_assignments');
    }
};