<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_recurring_deductions', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * DEDUCTION CODE
             *
             * Examples:
             *
             * COMPANY_LOAN
             * CASH_ADVANCE
             * INSURANCE
             * COOPERATIVE
             * OTHER_DEDUCTION
             */
            $table->string('code', 50);

            /*
             * DISPLAY NAME
             */
            $table->string('name', 150);

            /*
             * CATEGORY
             *
             * Suggested values:
             *
             * loan
             * cash_advance
             * insurance
             * cooperative
             * other
             */
            $table->string('category', 30)
                ->default('other');

            /*
             * DEDUCTION AMOUNT
             *
             * Store as positive amount.
             */
            $table->decimal('amount', 15, 2);

            /*
             * FREQUENCY
             *
             * Suggested values:
             *
             * per_payroll
             * monthly
             */
            $table->string('frequency', 30)
                ->default('per_payroll');

            /*
             * ORIGINAL / TOTAL OBLIGATION
             *
             * Optional.
             *
             * Useful for loans and cash advances.
             */
            $table->decimal('original_balance', 15, 2)
                ->nullable();

            /*
             * REMAINING BALANCE
             *
             * Optional.
             *
             * Payroll processing reduces this
             * after a successful/finalized deduction.
             */
            $table->decimal('remaining_balance', 15, 2)
                ->nullable();

            /*
             * EFFECTIVITY
             */
            $table->date('effective_from');

            $table->date('effective_to')
                ->nullable();

            /*
             * ACTIVE FLAG
             */
            $table->boolean('is_active')
                ->default(true);

            /*
             * OPTIONAL REFERENCE
             *
             * Example:
             * Loan reference / authorization number
             */
            $table->string('reference_number', 100)
                ->nullable();

            /*
             * OPTIONAL REMARKS
             */
            $table->text('remarks')
                ->nullable();

            /*
             * WHO CREATED / CONFIGURED IT
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
                [
                    'employee_id',
                    'is_active',
                    'effective_from',
                    'effective_to'
                ],
                'idx_recurring_deductions_employee_effectivity'
            );

            $table->index(
                ['employee_id', 'code'],
                'idx_recurring_deductions_employee_code'
            );

            $table->index(
                ['category', 'is_active'],
                'idx_recurring_deductions_category_active'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_recurring_deductions');
    }
};