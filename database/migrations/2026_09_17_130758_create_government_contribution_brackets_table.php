<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('government_contribution_brackets', function (Blueprint $table) {
            $table->id();

            /*
             * CONTRIBUTION SCHEDULE
             *
             * Parent schedule:
             * SSS / PHILHEALTH / PAGIBIG
             */
        $table->foreignId('government_contribution_schedule_id')
            ->constrained(
                table: 'government_contribution_schedules',
                indexName: 'fk_gov_contrib_bracket_schedule'
            )
            ->cascadeOnUpdate()
            ->cascadeOnDelete();
            /*
             * COMPENSATION RANGE
             *
             * NULL maximum means no upper limit.
             */
            $table->decimal('compensation_from', 15, 2)
                ->default(0);

            $table->decimal('compensation_to', 15, 2)
                ->nullable();

            /*
             * CONTRIBUTION BASE
             *
             * Optional fixed compensation base
             * used by a particular bracket/rule.
             */
            $table->decimal('contribution_base', 15, 2)
                ->nullable();

            /*
             * EMPLOYEE SHARE
             *
             * Can be a fixed amount and/or rate,
             * depending on the applicable schedule.
             *
             * Rate is stored as decimal fraction:
             *
             * 0.050000 = 5%
             */
            $table->decimal('employee_fixed_amount', 15, 2)
                ->nullable();

            $table->decimal('employee_rate', 10, 6)
                ->nullable();

            /*
             * EMPLOYER SHARE
             */
            $table->decimal('employer_fixed_amount', 15, 2)
                ->nullable();

            $table->decimal('employer_rate', 10, 6)
                ->nullable();

            /*
             * OPTIONAL MIN/MAX CONTRIBUTION
             *
             * Useful for percentage-based rules
             * that have contribution floors/caps.
             */
            $table->decimal('employee_min_amount', 15, 2)
                ->nullable();

            $table->decimal('employee_max_amount', 15, 2)
                ->nullable();

            $table->decimal('employer_min_amount', 15, 2)
                ->nullable();

            $table->decimal('employer_max_amount', 15, 2)
                ->nullable();

            /*
             * SORT ORDER
             *
             * Keeps bracket rows in the same
             * logical order as the official table.
             */
            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->boolean('is_active')
                ->default(true);

            $table->text('remarks')
                ->nullable();

            $table->timestamps();

            /*
             * INDEXES
             */
            $table->index(
                [
                    'government_contribution_schedule_id',
                    'compensation_from',
                    'compensation_to'
                ],
                'idx_gov_contrib_brackets_range'
            );

            $table->index(
                [
                    'government_contribution_schedule_id',
                    'is_active',
                    'sort_order'
                ],
                'idx_gov_contrib_brackets_active_sort'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('government_contribution_brackets');
    }
};