<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();

            /*
             * ANNOUNCEMENT TITLE
             */
            $table->string('title', 200);

            /*
             * ANNOUNCEMENT CONTENT
             */
            $table->text('content');

            /*
             * CATEGORY
             *
             * Suggested values:
             *
             * general
             * hr
             * payroll
             * event
             * reminder
             * policy
             */
            $table->string('category', 50)
                ->default('general');

            /*
             * PRIORITY
             *
             * Suggested values:
             *
             * normal
             * important
             * urgent
             */
            $table->string('priority', 20)
                ->default('normal');

            /*
             * PUBLICATION PERIOD
             *
             * publish_at:
             * When the announcement becomes visible.
             *
             * expires_at:
             * Optional automatic expiration.
             */
            $table->timestamp('publish_at')
                ->nullable();

            $table->timestamp('expires_at')
                ->nullable();

            /*
             * STATUS
             *
             * Suggested values:
             *
             * draft
             * published
             * archived
             */
            $table->string('status', 20)
                ->default('draft');

            /*
             * PINNED ANNOUNCEMENT
             *
             * Pinned announcements can appear
             * at the top of the employee dashboard.
             */
            $table->boolean('is_pinned')
                ->default(false);

            /*
             * OPTIONAL ATTACHMENT
             *
             * Example:
             * HR memo PDF
             * company circular
             */
            $table->string('attachment_path', 500)
                ->nullable();

            /*
             * AUTHOR / CREATOR
             */
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * PUBLICATION AUDIT
             */
            $table->foreignId('published_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamp('published_at')
                ->nullable();

            $table->timestamps();

            /*
             * INDEXES
             */
            $table->index(
                ['status', 'publish_at', 'expires_at'],
                'idx_announcements_visibility'
            );

            $table->index(
                ['is_pinned', 'status'],
                'idx_announcements_pinned_status'
            );

            $table->index(
                ['category', 'status'],
                'idx_announcements_category_status'
            );

            $table->index(
                ['priority', 'status'],
                'idx_announcements_priority_status'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};