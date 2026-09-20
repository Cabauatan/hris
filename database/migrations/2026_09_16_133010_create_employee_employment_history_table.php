<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_employment_history', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * CHANGE / MOVEMENT TYPE
             *
             * Examples:
             *
             * hired
             * regularized
             * transferred
             * promoted
             * reassigned
             * status_changed
             * contract_renewed
             * resigned
             * terminated
             * retired
             * end_of_contract
             */
            $table->string('movement_type', 50);

            /*
             * DEPARTMENT AT THIS POINT IN TIME
             */
            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * POSITION AT THIS POINT IN TIME
             */
            $table->foreignId('position_id')
                ->nullable()
                ->constrained('positions')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * EMPLOYMENT TYPE
             */
            $table->foreignId('employment_type_id')
                ->nullable()
                ->constrained('employment_types')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * EMPLOYEE STATUS
             */
            $table->foreignId('employee_status_id')
                ->nullable()
                ->constrained('employee_statuses')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * SUPERVISOR AT THIS POINT IN TIME
             */
            $table->foreignId('supervisor_id')
                ->nullable()
                ->constrained('employees')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * EFFECTIVITY
             *
             * Example:
             * Promotion takes effect on 2026-10-01.
             */
            $table->date('effective_date');

            /*
             * Optional end date.
             *
             * Current/latest assignment may have NULL.
             */
            $table->date('end_date')
                ->nullable();

            /*
             * REASON
             *
             * Examples:
             *
             * Promoted after annual review
             * Employee-requested transfer
             * End of employment contract
             */
            $table->string('reason', 255)
                ->nullable();

            /*
             * NOTES
             */
            $table->text('remarks')
                ->nullable();

            /*
             * WHO RECORDED THE CHANGE
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
                ['employee_id', 'effective_date'],
                'idx_employment_history_employee_date'
            );

            $table->index(
                ['employee_id', 'movement_type'],
                'idx_employment_history_employee_movement'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_employment_history');
    }
};