<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();

            /*
             * HOLIDAY NAME
             *
             * Examples:
             * New Year's Day
             * Independence Day
             * Company Anniversary
             */
            $table->string('name', 150);

            /*
             * HOLIDAY DATE
             */
            $table->date('holiday_date');

            /*
             * HOLIDAY TYPE
             *
             * Suggested values:
             *
             * regular
             * special_non_working
             * special_working
             * local
             * company
             */
            $table->string('holiday_type', 30);

            /*
             * WORKING / NON-WORKING
             *
             * Usually:
             *
             * regular             → false
             * special_non_working → false
             * special_working     → true
             *
             * Explicit field keeps attendance logic
             * simple and allows company-specific dates.
             */
            $table->boolean('is_working_day')
                ->default(false);

            /*
             * DESCRIPTION / NOTES
             */
            $table->text('description')
                ->nullable();

            /*
             * STATUS
             *
             * Allows HR to disable an incorrectly
             * configured holiday without deleting it.
             */
            $table->boolean('is_active')
                ->default(true);

            $table->timestamps();

            /*
             * Prevent duplicate holiday names on
             * the exact same date.
             */
            $table->unique(
                ['holiday_date', 'name'],
                'uq_holidays_date_name'
            );

            /*
             * INDEXES
             */
            $table->index(
                ['holiday_date', 'is_active'],
                'idx_holidays_date_active'
            );

            $table->index(
                ['holiday_type', 'holiday_date'],
                'idx_holidays_type_date'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};