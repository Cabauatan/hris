<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_earnings', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE PAYROLL RESULT
             */
            $table->foreignId('payroll_employee_result_id')
                ->constrained('payroll_employee_results')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * EARNING CODE
             *
             * Suggested values:
             *
             * BASIC_PAY
             * OVERTIME_PAY
             * HOLIDAY_PAY
             * REST_DAY_PAY
             * NIGHT_DIFFERENTIAL
             * ALLOWANCE
             * BONUS
             * INCENTIVE
             * COMMISSION
             * OTHER
             */
            $table->string('code', 50);

            /*
             * DISPLAY NAME
             *
             * Snapshot of the earning name used
             * during this payroll.
             *
             * Examples:
             * Basic Pay
             * Overtime Pay
             * Transportation Allowance
             */
            $table->string('name', 150);

            /*
             * EARNING CATEGORY
             *
             * Suggested values:
             *
             * basic
             * overtime
             * holiday
             * allowance
             * bonus
             * incentive
             * commission
             * other
             */
            $table->string('category', 30)
                ->default('other');

            /*
             * QUANTITY
             *
             * Optional basis for the calculation.
             *
             * Examples:
             * 10.00 days
             * 2.00 hours
             * 1.00 fixed allowance
             */
            $table->decimal('quantity', 12, 4)
                ->nullable();

            /*
             * RATE
             *
             * Optional rate used for calculation.
             *
             * Examples:
             * 800.00 per day
             * 150.00 per hour
             */
            $table->decimal('rate', 15, 4)
                ->nullable();

            /*
             * MULTIPLIER
             *
             * Optional calculation multiplier.
             *
             * Stored as a snapshot of what was
             * actually used during payroll.
             */
            $table->decimal('multiplier', 8, 4)
                ->nullable();

            /*
             * FINAL EARNING AMOUNT
             */
            $table->decimal('amount', 15, 2);

            /*
             * TAXABLE FLAG
             *
             * Snapshot used by payroll calculation.
             */
            $table->boolean('is_taxable')
                ->default(true);

            /*
             * STATUTORY / CONTRIBUTION BASIS FLAG
             *
             * Indicates whether this earning was
             * considered part of the applicable
             * contribution basis during calculation.
             */
            $table->boolean('is_contribution_basis')
                ->default(false);

            /*
             * SOURCE TYPE / SOURCE ID
             *
             * Optional trace back to where this
             * earning came from.
             *
             * Example:
             *
             * source_type = overtime_request
             * source_id   = 123
             *
             * No database FK because different
             * source tables may be referenced.
             */
            $table->string('source_type', 50)
                ->nullable();

            $table->unsignedBigInteger('source_id')
                ->nullable();

            /*
             * OPTIONAL DESCRIPTION / REMARKS
             */
            $table->string('description', 255)
                ->nullable();

            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            /*
             * INDEXES
             */
            $table->index(
                ['payroll_employee_result_id', 'category'],
                'idx_payroll_earnings_result_category'
            );

            $table->index(
                ['payroll_employee_result_id', 'code'],
                'idx_payroll_earnings_result_code'
            );

            $table->index(
                ['source_type', 'source_id'],
                'idx_payroll_earnings_source'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_earnings');
    }
};