<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_employee_results', function (Blueprint $table) {
            $table->id();

            /*
             * PAYROLL RUN
             */
            $table->foreignId('payroll_run_id')
                ->constrained('payroll_runs')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * EMPLOYEE
             */
            $table->foreignId('employee_id')
                ->constrained('employees')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * EMPLOYEE SNAPSHOT
             *
             * Preserve identifying information used
             * during this payroll run.
             */
            $table->string('employee_number', 50);

            $table->string('employee_name', 255);

            /*
             * COMPENSATION SNAPSHOT
             *
             * monthly
             * daily
             * hourly
             */
            $table->string('pay_type', 20);

            /*
             * BASIC RATE USED FOR THIS PAYROLL
             *
             * This comes from employee_compensations
             * based on the applicable effective date.
             */
            $table->decimal('basic_rate', 15, 2);

            /*
             * ATTENDANCE SNAPSHOT
             */
            $table->decimal('scheduled_days', 8, 2)
                ->default(0);

            $table->decimal('worked_days', 8, 2)
                ->default(0);

            $table->decimal('paid_leave_days', 8, 2)
                ->default(0);

            $table->decimal('unpaid_leave_days', 8, 2)
                ->default(0);

            $table->decimal('absent_days', 8, 2)
                ->default(0);

            $table->unsignedInteger('late_minutes')
                ->default(0);

            $table->unsignedInteger('undertime_minutes')
                ->default(0);

            $table->unsignedInteger('overtime_minutes')
                ->default(0);

            /*
             * BASIC PAY FOR THIS PAYROLL PERIOD
             *
             * This is the calculated basic pay
             * applicable to the cutoff, not
             * necessarily the employee's full
             * monthly basic_rate.
             */
            $table->decimal('basic_pay', 15, 2)
                ->default(0);

            /*
             * ATTENDANCE-RELATED DEDUCTIONS
             *
             * Stored separately for easy review.
             */
            $table->decimal('absence_deduction', 15, 2)
                ->default(0);

            $table->decimal('late_deduction', 15, 2)
                ->default(0);

            $table->decimal('undertime_deduction', 15, 2)
                ->default(0);

            /*
             * EARNINGS SUMMARY
             *
             * Detailed components will be stored
             * in payroll_earnings.
             */
            $table->decimal('total_earnings', 15, 2)
                ->default(0);

            /*
             * GROSS PAY
             */
            $table->decimal('gross_pay', 15, 2)
                ->default(0);

            /*
             * GOVERNMENT / STATUTORY DEDUCTIONS
             *
             * Detailed calculations will be
             * handled by later payroll tables.
             */
            $table->decimal('sss_contribution', 15, 2)
                ->default(0);

            $table->decimal('philhealth_contribution', 15, 2)
                ->default(0);

            $table->decimal('pagibig_contribution', 15, 2)
                ->default(0);

            $table->decimal('withholding_tax', 15, 2)
                ->default(0);

            /*
             * OTHER DEDUCTIONS
             *
             * Loans, cash advances, other employee
             * deductions, etc.
             */
            $table->decimal('other_deductions', 15, 2)
                ->default(0);

            /*
             * TOTAL DEDUCTIONS
             */
            $table->decimal('total_deductions', 15, 2)
                ->default(0);

            /*
             * FINAL NET PAY
             */
            $table->decimal('net_pay', 15, 2)
                ->default(0);

            /*
             * PROCESSING STATUS
             *
             * Suggested values:
             *
             * pending
             * calculated
             * needs_review
             * finalized
             * excluded
             */
            $table->string('status', 30)
                ->default('pending');

            /*
             * OPTIONAL PAYROLL REMARKS
             */
            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            /*
             * One employee result per payroll run.
             */
            $table->unique(
                ['payroll_run_id', 'employee_id'],
                'uq_payroll_employee_result'
            );

            /*
             * INDEXES
             */
            $table->index(
                ['payroll_run_id', 'status'],
                'idx_payroll_results_run_status'
            );

            $table->index(
                ['employee_id', 'created_at'],
                'idx_payroll_results_employee'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_employee_results');
    }
};