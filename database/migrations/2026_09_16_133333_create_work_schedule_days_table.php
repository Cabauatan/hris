<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedule_days', function (Blueprint $table) {
            $table->id();

            /*
             * WORK SCHEDULE
             */
            $table->foreignId('work_schedule_id')
                ->constrained('work_schedules')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * DAY OF WEEK
             *
             * ISO-style numbering:
             *
             * 1 = Monday
             * 2 = Tuesday
             * 3 = Wednesday
             * 4 = Thursday
             * 5 = Friday
             * 6 = Saturday
             * 7 = Sunday
             */
            $table->unsignedTinyInteger('day_of_week');

            /*
             * SHIFT
             *
             * NULL is allowed for rest days.
             */
            $table->foreignId('shift_id')
                ->nullable()
                ->constrained('shifts')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * REST DAY
             */
            $table->boolean('is_rest_day')
                ->default(false);

            $table->timestamps();

            /*
             * Only one configuration per weekday
             * inside a work schedule.
             */
            $table->unique(
                ['work_schedule_id', 'day_of_week'],
                'uq_work_schedule_day'
            );

            $table->index(
                ['work_schedule_id', 'is_rest_day'],
                'idx_work_schedule_days_rest'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedule_days');
    }
};