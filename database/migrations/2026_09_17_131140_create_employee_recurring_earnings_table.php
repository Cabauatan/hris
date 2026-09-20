<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_recurring_earnings', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * EARNING CODE
             *
             * Examples:
             *
             * TRANSPORT_ALLOWANCE
             * COMMUNICATION_ALLOWANCE
             * MEAL_ALLOWANCE
             * FIXED_INCENTIVE
             * OTHER_ALLOWANCE
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
             * allowance
             * incentive
             * commission
             * other
             */
            $table->string('category', 30)
                ->default('allowance');

            /*
             * AMOUNT
             *
             * Amount configured for this
             * recurring earning.
             */
            $table->decimal('amount', 15, 2);

            /*
             * FREQUENCY
             *
             * Suggested values:
             *
             * per_payroll
             * monthly
             *
             * per_payroll:
             * Apply the configured amount on
             * every applicable payroll.
             *
             * monthly:
             * Represents a monthly amount that
             * Payroll Service allocates according
             * to company payroll rules.
             */
            $table->string('frequency', 30)
                ->default('per_payroll');

            /*
             * TAX TREATMENT
             *
             * These are configuration defaults.
             * The payroll result will still save
             * the actual treatment used.
             */
            $table->boolean('is_taxable')
                ->default(true);

            $table->boolean('is_contribution_basis')
                ->default(false);

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
                'idx_recurring_earnings_employee_effectivity'
            );

            $table->index(
                ['employee_id', 'code'],
                'idx_recurring_earnings_employee_code'
            );

            $table->index(
                ['is_active', 'effective_from'],
                'idx_recurring_earnings_active_effective'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_recurring_earnings');
    }
};