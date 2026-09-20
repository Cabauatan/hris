<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            /*
             * RECIPIENT
             *
             * user_id is the actual authenticated
             * HRIS account receiving the notification.
             */
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * NOTIFICATION TYPE
             *
             * Examples:
             *
             * leave_submitted
             * leave_approved
             * leave_rejected
             * overtime_submitted
             * overtime_approved
             * cto_approved
             * attendance_correction_approved
             * profile_change_approved
             * payslip_published
             * announcement_published
             */
            $table->string('type', 100);

            /*
             * DISPLAY CONTENT
             */
            $table->string('title', 200);

            $table->text('message');

            /*
             * SOURCE / RELATED RECORD
             *
             * Examples:
             *
             * source_type = leave_request
             * source_id   = 123
             *
             * source_type = payslip
             * source_id   = 55
             *
             * source_type = announcement
             * source_id   = 10
             *
             * No FK because source_id can point
             * to different domain tables.
             */
            $table->string('source_type', 50)
                ->nullable();

            $table->unsignedBigInteger('source_id')
                ->nullable();

            /*
             * OPTIONAL ACTION URL
             *
             * Internal frontend route only.
             *
             * Examples:
             *
             * /ess/leaves/123
             * /ess/payslips/55
             * /announcements/10
             */
            $table->string('action_url', 500)
                ->nullable();

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
             * READ STATUS
             *
             * NULL = unread
             * timestamp = read
             */
            $table->timestamp('read_at')
                ->nullable();

            /*
             * CREATOR
             *
             * Usually NULL for system-generated
             * notifications.
             *
             * Can contain a user when notification
             * was manually initiated.
             */
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
                ['user_id', 'read_at', 'created_at'],
                'idx_notifications_user_read'
            );

            $table->index(
                ['user_id', 'created_at'],
                'idx_notifications_user_created'
            );

            $table->index(
                ['source_type', 'source_id'],
                'idx_notifications_source'
            );

            $table->index(
                ['type', 'created_at'],
                'idx_notifications_type_created'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};