<?php

namespace App\Services\Notification;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NotificationService
{
    private const SOURCE_TYPES = [
        'announcement',
        'leave_request',
        'overtime_request',
        'attendance_correction_request',
        'cto_request',
        'payslip',
        'profile_change_request',
        'system',
    ];

    public function paginateForUser(
        User $user,
        int $perPage = 20,
        ?bool $unreadOnly = null
    ): LengthAwarePaginator {
        return Notification::query()
            ->where('user_id', $user->id)
            ->when(
                $unreadOnly === true,
                fn ($query) => $query->whereNull('read_at')
            )
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function create(
        User $user,
        array $data,
        ?User $createdBy = null
    ): Notification {
        return DB::transaction(function () use (
            $user,
            $data,
            $createdBy
        ) {
            $sourceType = $data['source_type'] ?? null;

            if (
                $sourceType !== null
                && ! in_array(
                    $sourceType,
                    self::SOURCE_TYPES,
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'source_type' =>
                        'Unsupported notification source type.',
                ]);
            }

            $actionUrl = $this->validateActionUrl(
                $data['action_url'] ?? null
            );

            return Notification::create([
                'user_id' => $user->id,

                'title' => $data['title'],
                'message' => $data['message'],

                'source_type' => $sourceType,
                'source_id' => $data['source_id'] ?? null,

                'action_url' => $actionUrl,

                'read_at' => null,

                'created_by_user_id' => $createdBy?->id,
            ]);
        });
    }

    public function markAsRead(
        Notification $notification,
        User $user
    ): Notification {
        return DB::transaction(function () use (
            $notification,
            $user
        ) {
            $notification = Notification::query()
                ->lockForUpdate()
                ->findOrFail($notification->id);

            $this->ensureOwnership(
                $notification,
                $user
            );

            if ($notification->read_at !== null) {
                return $notification;
            }

            $notification->update([
                'read_at' => now(),
            ]);

            return $notification->refresh();
        });
    }

    public function markAllAsRead(
        User $user
    ): int {
        return Notification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);
    }

    public function unreadCount(
        User $user
    ): int {
        return Notification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    private function ensureOwnership(
        Notification $notification,
        User $user
    ): void {
        if ($notification->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'notification' =>
                    'The notification does not belong to this user.',
            ]);
        }
    }

    private function validateActionUrl(
        ?string $actionUrl
    ): ?string {
        if ($actionUrl === null || trim($actionUrl) === '') {
            return null;
        }

        /*
         * Notification action links should be internal HRIS paths.
         *
         * Examples:
         * /ess/leave/123
         * /ess/payslips/55
         *
         * Do not allow arbitrary external URLs.
         */
        if (
            ! str_starts_with($actionUrl, '/')
            || str_starts_with($actionUrl, '//')
        ) {
            throw ValidationException::withMessages([
                'action_url' =>
                    'Notification action URL must be an internal application path.',
            ]);
        }

        return $actionUrl;
    }
}