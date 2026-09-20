<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_deductions', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE PAYROLL RESULT
             */
            $table->foreignId('payroll_employee_result_id')
                ->constrained('payroll_employee_results')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * DEDUCTION CODE
             *
             * Suggested examples:
             *
             * ABSENCE
             * LATE
             * UNDERTIME
             * SSS
             * PHILHEALTH
             * PAGIBIG
             * WITHHOLDING_TAX
             * LOAN
             * CASH_ADVANCE
             * OTHER
             */
            $table->string('code', 50);

            /*
             * DISPLAY NAME
             *
             * Snapshot of the deduction name
             * used during this payroll.
             */
            $table->string('name', 150);

            /*
             * CATEGORY
             *
             * Suggested values:
             *
             * attendance
             * government
             * tax
             * loan
             * cash_advance
             * other
             */
            $table->string('category', 30)
                ->default('other');

            /*
             * OPTIONAL QUANTITY
             *
             * Examples:
             *
             * 1.00 absent day
             * 45 late minutes
             * 30 undertime minutes
             */
            $table->decimal('quantity', 12, 4)
                ->nullable();

            /*
             * OPTIONAL RATE
             *
             * Snapshot of the rate used
             * in the calculation.
             */
            $table->decimal('rate', 15, 4)
                ->nullable();

            /*
             * FINAL DEDUCTION AMOUNT
             *
             * Store deduction amount as positive.
             *
             * Example:
             * 500.00
             *
             * NOT:
             * -500.00
             */
            $table->decimal('amount', 15, 2);

            /*
             * SOURCE REFERENCE
             *
             * Examples:
             *
             * daily_attendance
             * recurring_deduction
             * government_contribution
             * withholding_tax
             * manual
             *
             * No FK because different tables
             * may be referenced.
             */
            $table->string('source_type', 50)
                ->nullable();

            $table->unsignedBigInteger('source_id')
                ->nullable();

            /*
             * OPTIONAL DESCRIPTION
             */
            $table->string('description', 255)
                ->nullable();

            /*
             * OPTIONAL REMARKS
             */
            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            /*
             * INDEXES
             */
            $table->index(
                ['payroll_employee_result_id', 'category'],
                'idx_payroll_deductions_result_category'
            );

            $table->index(
                ['payroll_employee_result_id', 'code'],
                'idx_payroll_deductions_result_code'
            );

            $table->index(
                ['source_type', 'source_id'],
                'idx_payroll_deductions_source'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_deductions');
    }
};