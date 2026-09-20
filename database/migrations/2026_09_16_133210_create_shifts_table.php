<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();

            /*
             * BASIC INFORMATION
             *
             * Examples:
             * DAY
             * NIGHT
             * OFFICE
             */
            $table->string('code', 30)->unique();

            $table->string('name', 100);

            $table->text('description')->nullable();

            /*
             * =====================================================
             * WORKING HOURS
             * =====================================================
             *
             * Example:
             *
             * Day:
             * 08:00 → 17:00
             *
             * Night:
             * 22:00 → 06:00
             */
            $table->time('start_time');

            $table->time('end_time');

            /*
             * TRUE when shift ends on the following day.
             *
             * Example:
             * 10 PM → 6 AM
             */
            $table->boolean('is_overnight')
                ->default(false);

            /*
             * =====================================================
             * BREAK
             * =====================================================
             *
             * Total unpaid break duration.
             *
             * Example:
             * 60 = one-hour lunch break
             */
            $table->unsignedInteger('break_minutes')
                ->default(60);

            /*
             * =====================================================
             * GRACE PERIOD
             * =====================================================
             *
             * Example:
             *
             * Shift starts: 08:00
             * Grace:        5 minutes
             *
             * 08:05 → not late
             * 08:06 → late
             */
            $table->unsignedInteger('grace_period_minutes')
                ->default(0);

            /*
             * =====================================================
             * EXPECTED WORK
             * =====================================================
             *
             * Expected paid working minutes.
             *
             * Example:
             *
             * 8 hours = 480 minutes
             *
             * Keeping this explicit makes attendance
             * calculations easier later.
             */
            $table->unsignedInteger('required_work_minutes')
                ->default(480);

            /*
             * =====================================================
             * STATUS
             * =====================================================
             */

            $table->boolean('is_active')
                ->default(true);

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            $table->index(
                ['is_active', 'sort_order'],
                'idx_shifts_active_sort'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};