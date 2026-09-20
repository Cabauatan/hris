<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withholding_tax_brackets', function (Blueprint $table) {
            $table->id();

            /*
             * TAX TABLE / VERSION NAME
             *
             * Groups brackets belonging to the
             * same withholding tax schedule.
             *
             * Example:
             * "Applicable Withholding Tax Table"
             *
             * Actual official schedule data will
             * be seeded separately.
             */
            $table->string('table_name', 150);

            /*
             * PAY FREQUENCY
             *
             * Suggested values:
             *
             * monthly
             * semi_monthly
             * biweekly
             * weekly
             * daily
             *
             * Payroll Service must use the table
             * applicable to the payroll frequency.
             */
            $table->string('pay_frequency', 30);

            /*
             * EFFECTIVITY
             *
             * Allows future tax schedules without
             * overwriting historical schedules.
             */
            $table->date('effective_from');

            $table->date('effective_to')
                ->nullable();

            /*
             * TAXABLE COMPENSATION RANGE
             *
             * NULL maximum means no upper limit.
             */
            $table->decimal('taxable_income_from', 15, 2);

            $table->decimal('taxable_income_to', 15, 2)
                ->nullable();

            /*
             * BASE TAX
             *
             * Fixed amount applicable when entering
             * this bracket.
             */
            $table->decimal('base_tax', 15, 2)
                ->default(0);

            /*
             * EXCESS THRESHOLD
             *
             * Amount above which excess_rate
             * is applied.
             */
            $table->decimal('excess_over', 15, 2)
                ->default(0);

            /*
             * EXCESS RATE
             *
             * Stored as decimal fraction.
             *
             * Example:
             * 0.150000 = 15%
             *
             * Example only — actual values must
             * come from the applicable official
             * withholding tax table.
             */
            $table->decimal('excess_rate', 10, 6)
                ->default(0);

            /*
             * SORT ORDER
             *
             * Preserves logical bracket order.
             */
            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->boolean('is_active')
                ->default(true);

            /*
             * OFFICIAL REFERENCE
             *
             * Store identifying reference such as
             * regulation/circular/table description.
             */
            $table->string('reference', 255)
                ->nullable();

            $table->text('remarks')
                ->nullable();

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
                    'pay_frequency',
                    'effective_from',
                    'effective_to'
                ],
                'idx_tax_brackets_frequency_effectivity'
            );

            $table->index(
                [
                    'pay_frequency',
                    'taxable_income_from',
                    'taxable_income_to'
                ],
                'idx_tax_brackets_income_range'
            );

            $table->index(
                ['is_active', 'sort_order'],
                'idx_tax_brackets_active_sort'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withholding_tax_brackets');
    }
};