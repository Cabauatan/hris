<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();

            /*
             * PAYROLL PERIOD
             */
            $table->foreignId('payroll_period_id')
                ->constrained('payroll_periods')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * RUN NUMBER
             *
             * Allows recalculation/reprocessing
             * before finalization.
             *
             * Example:
             * Run 1
             * Run 2
             */
            $table->unsignedInteger('run_number')
                ->default(1);

            /*
             * STATUS
             *
             * Suggested values:
             *
             * draft
             * processing
             * completed
             * failed
             * finalized
             * cancelled
             */
            $table->string('status', 30)
                ->default('draft');

            /*
             * PROCESSING INFORMATION
             */
            $table->timestamp('started_at')
                ->nullable();

            $table->timestamp('completed_at')
                ->nullable();

            /*
             * NUMBER OF EMPLOYEES INCLUDED
             */
            $table->unsignedInteger('employee_count')
                ->default(0);

            /*
             * PAYROLL TOTALS
             *
             * These are summary totals for the
             * entire payroll run.
             */
            $table->decimal('total_basic_pay', 15, 2)
                ->default(0);

            $table->decimal('total_gross_pay', 15, 2)
                ->default(0);

            $table->decimal('total_deductions', 15, 2)
                ->default(0);

            $table->decimal('total_net_pay', 15, 2)
                ->default(0);

            /*
             * PROCESSING USER
             */
            $table->foreignId('processed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * FINALIZATION
             */
            $table->timestamp('finalized_at')
                ->nullable();

            $table->foreignId('finalized_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * ERROR INFORMATION
             *
             * Useful if payroll processing fails.
             */
            $table->text('error_message')
                ->nullable();

            /*
             * OPTIONAL REMARKS
             */
            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            /*
             * One run number per payroll period.
             */
            $table->unique(
                ['payroll_period_id', 'run_number'],
                'uq_payroll_run_period_number'
            );

            /*
             * INDEXES
             */
            $table->index(
                ['payroll_period_id', 'status'],
                'idx_payroll_runs_period_status'
            );

            $table->index(
                ['status', 'created_at'],
                'idx_payroll_runs_status_created'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};