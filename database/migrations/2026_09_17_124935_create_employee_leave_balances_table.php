<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_leave_balances', function (Blueprint $table) {
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
             */
            $table->foreignId('leave_type_id')
                ->constrained('leave_types')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * LEAVE YEAR
             *
             * Example:
             * 2026
             * 2027
             */
            $table->unsignedSmallInteger('year');

            /*
             * ALLOCATED CREDITS
             *
             * Initial/regular credits granted
             * for this leave year.
             *
             * Example:
             * 15.00 days
             */
            $table->decimal('allocated_days', 8, 2)
                ->default(0);

            /*
             * ADDITIONAL EARNED CREDITS
             *
             * Optional credits earned during
             * the leave year.
             */
            $table->decimal('earned_days', 8, 2)
                ->default(0);

            /*
             * MANUAL ADJUSTMENTS
             *
             * Can be positive or negative.
             *
             * Examples:
             * +1.00
             * -0.50
             */
            $table->decimal('adjustment_days', 8, 2)
                ->default(0);

            /*
             * USED CREDITS
             *
             * Approved leave already consumed.
             */
            $table->decimal('used_days', 8, 2)
                ->default(0);

            /*
             * CURRENT AVAILABLE BALANCE
             */
            $table->decimal('balance_days', 8, 2)
                ->default(0);

            /*
             * OPTIONAL REMARKS
             */
            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            /*
             * One balance record per:
             *
             * Employee + Leave Type + Year
             */
            $table->unique(
                ['employee_id', 'leave_type_id', 'year'],
                'uq_employee_leave_balance'
            );

            /*
             * INDEXES
             */
            $table->index(
                ['employee_id', 'year'],
                'idx_leave_balances_employee_year'
            );

            $table->index(
                ['leave_type_id', 'year'],
                'idx_leave_balances_type_year'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_leave_balances');
    }
};