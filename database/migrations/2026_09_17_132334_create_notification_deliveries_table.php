<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_deliveries', function (Blueprint $table) {
            $table->id();

            /*
             * SOURCE NOTIFICATION
             */
            $table->foreignId('notification_id')
                ->constrained('notifications')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * DELIVERY CHANNEL
             *
             * Suggested values:
             *
             * email
             * telegram
             * viber
             */
            $table->string('channel', 30);

            /*
             * DESTINATION SNAPSHOT
             *
             * Examples:
             *
             * email:
             * juan@example.com
             *
             * telegram:
             * chat/user identifier
             *
             * viber:
             * recipient identifier
             *
             * Snapshot intentionally stored so
             * delivery history remains traceable
             * even if employee contact info changes.
             */
            $table->string('recipient', 255);

            /*
             * DELIVERY STATUS
             *
             * Suggested values:
             *
             * pending
             * processing
             * sent
             * failed
             * cancelled
             */
            $table->string('status', 30)
                ->default('pending');

            /*
             * NUMBER OF DELIVERY ATTEMPTS
             */
            $table->unsignedInteger('attempt_count')
                ->default(0);

            /*
             * LAST ATTEMPT
             */
            $table->timestamp('last_attempt_at')
                ->nullable();

            /*
             * SUCCESSFUL DELIVERY TIME
             */
            $table->timestamp('sent_at')
                ->nullable();

            /*
             * NEXT RETRY TIME
             *
             * Useful for retry/backoff logic.
             */
            $table->timestamp('next_retry_at')
                ->nullable();

            /*
             * EXTERNAL PROVIDER REFERENCE
             *
             * Example:
             * message ID returned by email,
             * Telegram, or Viber provider.
             */
            $table->string('provider_reference', 255)
                ->nullable();

            /*
             * LAST ERROR
             *
             * Keep error details for debugging.
             * Do not store credentials/tokens here.
             */
            $table->text('error_message')
                ->nullable();

            $table->timestamps();

            /*
             * ONE DELIVERY RECORD PER
             * NOTIFICATION + CHANNEL
             *
             * For V1, one recipient destination
             * per channel per notification.
             */
            $table->unique(
                ['notification_id', 'channel'],
                'uq_notification_delivery_channel'
            );

            /*
             * INDEXES
             */
            $table->index(
                ['status', 'next_retry_at'],
                'idx_notification_deliveries_retry'
            );

            $table->index(
                ['channel', 'status'],
                'idx_notification_deliveries_channel_status'
            );

            $table->index(
                ['notification_id', 'status'],
                'idx_notification_deliveries_notification_status'
            );

            $table->index(
                ['recipient', 'channel'],
                'idx_notification_deliveries_recipient'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
    }
};