<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_government_contributions', function (Blueprint $table) {
            $table->id();

            /*
             * EMPLOYEE PAYROLL RESULT
             */
            $table->foreignId('payroll_employee_result_id')
                ->constrained(
                    table: 'payroll_employee_results',
                    indexName: 'fk_payroll_gov_contrib_result'
                )
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * CONTRIBUTION SCHEDULE USED
             *
             * Nullable intentionally.
             *
             * Historical payroll result remains valid
             * even if the configuration is later removed.
             */
            $table->foreignId('government_contribution_schedule_id')
                ->nullable()
                ->constrained(
                    table: 'government_contribution_schedules',
                    indexName: 'fk_payroll_gov_contrib_schedule'
                )
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * BRACKET / RULE USED
             *
             * Nullable because percentage-based
             * schedules may not depend on a
             * traditional bracket.
             */
            $table->foreignId('government_contribution_bracket_id')
                ->nullable()
                ->constrained(
                    table: 'government_contribution_brackets',
                    indexName: 'fk_payroll_gov_contrib_bracket'
                )
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * AGENCY SNAPSHOT
             *
             * Suggested values:
             *
             * SSS
             * PHILHEALTH
             * PAGIBIG
             */
            $table->string('agency', 30);

            /*
             * MEMBER / GOVERNMENT ID SNAPSHOT
             *
             * Comes from employee_government_ids.
             *
             * Nullable because payroll validation
             * may encounter an employee with a
             * missing government ID.
             */
            $table->string('member_number', 100)
                ->nullable();

            /*
             * COMPENSATION / CONTRIBUTION BASIS
             *
             * Amount actually used as the basis
             * for this contribution calculation.
             */
            $table->decimal('compensation_basis', 15, 2)
                ->default(0);

            /*
             * EMPLOYEE SHARE
             *
             * Amount deducted from employee pay.
             */
            $table->decimal('employee_share', 15, 2)
                ->default(0);

            /*
             * EMPLOYER SHARE
             *
             * Not deducted from employee net pay,
             * but needed for company/statutory
             * reporting.
             */
            $table->decimal('employer_share', 15, 2)
                ->default(0);

            /*
             * TOTAL CONTRIBUTION
             *
             * Normally:
             *
             * employee_share + employer_share
             */
            $table->decimal('total_contribution', 15, 2)
                ->default(0);

            /*
             * OPTIONAL CALCULATION SNAPSHOT
             *
             * Useful for auditing what rate/base
             * was actually applied.
             */
            $table->decimal('employee_rate', 10, 6)
                ->nullable();

            $table->decimal('employer_rate', 10, 6)
                ->nullable();

            /*
             * STATUS
             *
             * Suggested values:
             *
             * calculated
             * overridden
             * waived
             * not_applicable
             */
            $table->string('status', 30)
                ->default('calculated');

            /*
             * MANUAL OVERRIDE INFORMATION
             *
             * If HR/payroll explicitly overrides
             * a calculated contribution.
             */
            $table->boolean('is_overridden')
                ->default(false);

            $table->string('override_reason', 255)
                ->nullable();

            $table->foreignId('overridden_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamp('overridden_at')
                ->nullable();

            /*
             * OPTIONAL REMARKS
             */
            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            /*
             * One contribution result per agency
             * per employee payroll result.
             */
            $table->unique(
                ['payroll_employee_result_id', 'agency'],
                'uq_payroll_gov_contribution_agency'
            );

            /*
             * INDEXES
             */
            $table->index(
                ['agency', 'created_at'],
                'idx_payroll_gov_contributions_agency'
            );

            $table->index(
                ['government_contribution_schedule_id', 'agency'],
                'idx_payroll_gov_contributions_schedule'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_government_contributions');
    }
};