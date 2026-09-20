<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcement_audiences', function (Blueprint $table) {
            $table->id();

            /*
             * ANNOUNCEMENT
             */
            $table->foreignId('announcement_id')
                ->constrained('announcements')
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
             * TARGET ID
             *
             * Meaning depends on audience_type:
             *
             * all        => NULL
             * division   => divisions.id
             * department => departments.id
             * employee   => employees.id
             *
             * No database FK because this can
             * reference different target tables.
             */
            $table->unsignedBigInteger('audience_id')
                ->nullable();

            $table->timestamps();

            /*
             * Prevent duplicate audience targets.
             *
             * Note:
             * For audience_type = all, application
             * validation must ensure only one
             * all-audience row per announcement,
             * since MySQL UNIQUE allows multiple
             * NULL values.
             */
            $table->unique(
                [
                    'announcement_id',
                    'audience_type',
                    'audience_id'
                ],
                'uq_announcement_audience'
            );

            /*
             * INDEXES
             */
            $table->index(
                ['audience_type', 'audience_id'],
                'idx_announcement_audiences_target'
            );

            $table->index(
                ['announcement_id', 'audience_type'],
                'idx_announcement_audiences_announcement_type'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_audiences');
    }
};