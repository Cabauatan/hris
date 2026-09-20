<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();

            /*
             * EVENT TITLE
             *
             * Examples:
             *
             * Company Town Hall
             * Annual Medical Examination
             * Payroll Cutoff Reminder
             * HR Orientation
             * Fire Drill
             */
            $table->string('title', 200);

            /*
             * OPTIONAL DESCRIPTION
             */
            $table->text('description')
                ->nullable();

            /*
             * EVENT TYPE
             *
             * Suggested values:
             *
             * company_event
             * meeting
             * training
             * deadline
             * reminder
             * hr_event
             * other
             */
            $table->string('event_type', 50)
                ->default('company_event');

            /*
             * EVENT DATE / TIME
             *
             * DateTime allows both:
             *
             * Sep 20, 2026
             *
             * and
             *
             * Sep 20, 2026
             * 9:00 AM - 12:00 PM
             */
            $table->dateTime('starts_at');

            $table->dateTime('ends_at')
                ->nullable();

            /*
             * ALL-DAY EVENT
             *
             * Example:
             *
             * Company Anniversary
             * HR Deadline
             */
            $table->boolean('is_all_day')
                ->default(false);

            /*
             * OPTIONAL LOCATION
             *
             * Examples:
             *
             * Conference Room A
             * Main Office
             * Online / Microsoft Teams
             */
            $table->string('location', 255)
                ->nullable();

            /*
             * STATUS
             *
             * Suggested values:
             *
             * draft
             * published
             * cancelled
             */
            $table->string('status', 30)
                ->default('draft');

            /*
             * OPTIONAL ATTACHMENT
             *
             * Example:
             *
             * event memo
             * training material
             * company circular
             *
             * Store in private storage when needed.
             */
            $table->string('attachment_path', 500)
                ->nullable();

            /*
             * CREATOR
             */
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * PUBLICATION
             */
            $table->timestamp('published_at')
                ->nullable();

            $table->foreignId('published_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * CANCELLATION
             */
            $table->timestamp('cancelled_at')
                ->nullable();

            $table->foreignId('cancelled_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('cancellation_reason', 255)
                ->nullable();

            $table->timestamps();

            /*
             * INDEXES
             */
            $table->index(
                ['starts_at', 'ends_at'],
                'idx_calendar_events_schedule'
            );

            $table->index(
                ['status', 'starts_at'],
                'idx_calendar_events_status_start'
            );

            $table->index(
                ['event_type', 'starts_at'],
                'idx_calendar_events_type_start'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};