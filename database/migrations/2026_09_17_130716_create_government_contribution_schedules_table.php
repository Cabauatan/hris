<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('government_contribution_schedules', function (Blueprint $table) {
            $table->id();

            /*
             * AGENCY
             *
             * Suggested values:
             *
             * SSS
             * PHILHEALTH
             * PAGIBIG
             */
            $table->string('agency', 30);

            /*
             * SCHEDULE / VERSION NAME
             *
             * Example:
             * SSS Contribution Schedule 2025
             *
             * This is descriptive only.
             * Actual applicability is determined
             * by effective dates.
             */
            $table->string('name', 150);

            /*
             * EFFECTIVITY
             *
             * Allows us to preserve historical
             * contribution schedules.
             */
            $table->date('effective_from');

            $table->date('effective_to')
                ->nullable();

            /*
             * CALCULATION METHOD
             *
             * Suggested values:
             *
             * bracket
             * percentage
             *
             * We intentionally avoid putting
             * agency-specific formulas here.
             */
            $table->string('calculation_method', 30);

            /*
             * ACTIVE FLAG
             *
             * Effective dates still determine
             * which schedule applies.
             */
            $table->boolean('is_active')
                ->default(true);

            /*
             * OPTIONAL REFERENCE
             *
             * Useful for identifying the official
             * circular/table/document used when
             * this schedule was encoded.
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
                ['agency', 'effective_from', 'effective_to'],
                'idx_gov_contrib_schedule_effectivity'
            );

            $table->index(
                ['agency', 'is_active'],
                'idx_gov_contrib_schedule_active'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('government_contribution_schedules');
    }
};