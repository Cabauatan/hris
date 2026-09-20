<?php

namespace App\Services\Notification;

use App\Models\Notification;
use App\Models\NotificationDelivery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class NotificationDeliveryService
{
    private const CHANNELS = [
        'email',
        'telegram',
        'viber',
    ];

    public function createDelivery(
        Notification $notification,
        string $channel,
        string $recipient
    ): NotificationDelivery {
        return DB::transaction(function () use (
            $notification,
            $channel,
            $recipient
        ) {
            $this->validateChannel($channel);

            if (trim($recipient) === '') {
                throw ValidationException::withMessages([
                    'recipient' =>
                        'Notification delivery recipient is required.',
                ]);
            }

            /*
             * Recipient is a snapshot.
             *
             * If employee changes email/Telegram/Viber later,
             * historical delivery still shows where this attempt
             * was actually sent.
             */
            return NotificationDelivery::create([
                'notification_id' => $notification->id,

                'channel' => $channel,
                'recipient' => $recipient,

                'status' => 'pending',

                'attempt_count' => 0,

                'last_attempt_at' => null,
                'sent_at' => null,
                'next_retry_at' => null,

                'provider_reference' => null,
                'error_message' => null,
            ]);
        });
    }

    public function beginAttempt(
        NotificationDelivery $delivery
    ): NotificationDelivery {
        return DB::transaction(function () use ($delivery) {
            $delivery = NotificationDelivery::query()
                ->lockForUpdate()
                ->findOrFail($delivery->id);

            /*
             * Idempotency:
             * never resend a delivery already confirmed sent.
             */
            if ($delivery->status === 'sent') {
                return $delivery;
            }

            $delivery->update([
                'status' => 'processing',

                'attempt_count' =>
                    $delivery->attempt_count + 1,

                'last_attempt_at' => now(),

                'error_message' => null,
            ]);

            return $delivery->refresh();
        });
    }

    public function markSent(
        NotificationDelivery $delivery,
        ?string $providerReference = null
    ): NotificationDelivery {
        return DB::transaction(function () use (
            $delivery,
            $providerReference
        ) {
            $delivery = NotificationDelivery::query()
                ->lockForUpdate()
                ->findOrFail($delivery->id);

            if ($delivery->status === 'sent') {
                return $delivery;
            }

            $delivery->update([
                'status' => 'sent',

                'sent_at' => now(),

                'provider_reference' =>
                    $providerReference,

                'next_retry_at' => null,
                'error_message' => null,
            ]);

            return $delivery->refresh();
        });
    }

    public function markFailed(
        NotificationDelivery $delivery,
        Throwable $exception,
        ?Carbon $nextRetryAt = null
    ): NotificationDelivery {
        return DB::transaction(function () use (
            $delivery,
            $exception,
            $nextRetryAt
        ) {
            $delivery = NotificationDelivery::query()
                ->lockForUpdate()
                ->findOrFail($delivery->id);

            /*
             * A stale/duplicate job must never downgrade a
             * successfully sent delivery.
             */
            if ($delivery->status === 'sent') {
                return $delivery;
            }

            $delivery->update([
                'status' => 'failed',

                'next_retry_at' =>
                    $nextRetryAt,

                'error_message' =>
                    $this->safeErrorMessage($exception),
            ]);

            return $delivery->refresh();
        });
    }

    public function eligibleForRetry(
        int $limit = 100
    ) {
        return NotificationDelivery::query()
            ->where('status', 'failed')
            ->whereNotNull('next_retry_at')
            ->where(
                'next_retry_at',
                '<=',
                now()
            )
            ->orderBy('next_retry_at')
            ->limit($limit)
            ->get();
    }

    private function validateChannel(
        string $channel
    ): void {
        if (
            ! in_array(
                $channel,
                self::CHANNELS,
                true
            )
        ) {
            throw ValidationException::withMessages([
                'channel' =>
                    'Unsupported notification delivery channel.',
            ]);
        }
    }

    private function safeErrorMessage(
        Throwable $exception
    ): string {
        /*
         * Don't persist raw provider responses containing
         * credentials, tokens or sensitive request data.
         */
        return 'Notification delivery failed.';
    }
}