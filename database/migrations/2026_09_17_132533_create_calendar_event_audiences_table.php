<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_event_audiences', function (Blueprint $table) {
            $table->id();

            /*
             * CALENDAR EVENT
             */
            $table->foreignId('calendar_event_id')
                ->constrained('calendar_events')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * AUDIENCE TYPE
             *
             * Suggested values:
             *
             * all
             * division
             * department
             * employee
             */
            $table->string('audience_type', 30);

            /*
             * AUDIENCE TARGET
             *
             * all        => NULL
             * division   => divisions.id
             * department => departments.id
             * employee   => employees.id
             *
             * No FK because audience_id may point
             * to different tables depending on
             * audience_type.
             */
            $table->unsignedBigInteger('audience_id')
                ->nullable();

            $table->timestamps();

            /*
             * PREVENT DUPLICATE TARGETS
             *
             * Note:
             * MySQL allows multiple NULL values
             * in a UNIQUE index, so the application
             * must ensure only one "all" row exists
             * per calendar event.
             */
            $table->unique(
                [
                    'calendar_event_id',
                    'audience_type',
                    'audience_id'
                ],
                'uq_calendar_event_audience'
            );

            /*
             * INDEXES
             */
            $table->index(
                ['audience_type', 'audience_id'],
                'idx_calendar_event_audiences_target'
            );

            $table->index(
                ['calendar_event_id', 'audience_type'],
                'idx_calendar_event_audiences_event_type'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_audiences');
    }
};