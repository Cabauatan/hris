<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();

            /*
             * PERIOD CODE
             *
             * Example:
             * 2026-09-A
             * 2026-09-B
             */
            $table->string('code', 50)
                ->unique();

            /*
             * DISPLAY NAME
             *
             * Example:
             * September 1-15, 2026
             */
            $table->string('name', 150);

            /*
             * PAYROLL PERIOD
             *
             * Used as the actual payroll coverage.
             */
            $table->date('period_start');

            $table->date('period_end');

            /*
             * ATTENDANCE CUTOFF
             *
             * Allows attendance coverage to differ
             * from the payroll period if needed.
             *
             * Example:
             *
             * Payroll Period:
             * Sep 1 - Sep 15
             *
             * Attendance Cutoff:
             * Aug 26 - Sep 10
             */
            $table->date('attendance_cutoff_start');

            $table->date('attendance_cutoff_end');

            /*
             * PAY DATE
             *
             * Actual intended salary release date.
             */
            $table->date('pay_date');

            /*
             * PAY FREQUENCY
             *
             * Suggested values:
             *
             * semi_monthly
             * monthly
             * weekly
             * biweekly
             */
            $table->string('pay_frequency', 30)
                ->default('semi_monthly');

            /*
             * STATUS
             *
             * Suggested values:
             *
             * draft
             * open
             * processing
             * finalized
             * locked
             */
            $table->string('status', 30)
                ->default('draft');

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
             * LOCKING
             *
             * Once locked, payroll data belonging
             * to this period should no longer be
             * editable through normal operations.
             */
            $table->timestamp('locked_at')
                ->nullable();

            $table->foreignId('locked_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * OPTIONAL NOTES
             */
            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            /*
             * INDEXES
             */
            $table->index(
                ['period_start', 'period_end'],
                'idx_payroll_periods_dates'
            );

            $table->index(
                ['attendance_cutoff_start', 'attendance_cutoff_end'],
                'idx_payroll_periods_attendance_cutoff'
            );

            $table->index(
                ['status', 'pay_date'],
                'idx_payroll_periods_status_pay_date'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};