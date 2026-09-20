<?php

namespace App\Services\Announcement;

use App\Models\Announcement;
use App\Models\AnnouncementAudience;
use App\Models\AnnouncementRead;
use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnnouncementService
{
    private const AUDIENCE_TYPES = [
        'all',
        'division',
        'department',
        'employee',
    ];

    public function paginateForAdmin(
        int $perPage = 15,
        ?string $status = null,
        ?string $search = null
    ): LengthAwarePaginator {
        return Announcement::query()
            ->with([
                'audiences',
                'createdBy',
                'publishedBy',
            ])
            ->when(
                $status !== null,
                fn ($query) => $query->where('status', $status)
            )
            ->when(
                $search !== null && trim($search) !== '',
                fn ($query) => $query->where(function ($query) use ($search) {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                })
            )
            ->orderByDesc('is_pinned')
            ->orderByDesc('publish_at')
            ->paginate($perPage);
    }

    public function create(
        array $data,
        User $actor
    ): Announcement {
        return DB::transaction(function () use ($data, $actor) {
            $this->validateDates($data);

            $announcement = Announcement::create([
                'title' => $data['title'],
                'content' => $data['content'],

                'category' => $data['category'] ?? null,
                'priority' => $data['priority'] ?? null,

                'publish_at' => $data['publish_at'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,

                'status' => 'draft',

                'is_pinned' =>
                    (bool) ($data['is_pinned'] ?? false),

                'attachment_path' =>
                    $data['attachment_path'] ?? null,

                'created_by_user_id' => $actor->id,
            ]);

            $this->syncAudiences(
                $announcement,
                $data['audiences'] ?? []
            );

            return $announcement->load('audiences');
        });
    }

    public function update(
        Announcement $announcement,
        array $data
    ): Announcement {
        return DB::transaction(function () use (
            $announcement,
            $data
        ) {
            $announcement = Announcement::query()
                ->lockForUpdate()
                ->findOrFail($announcement->id);

            if ($announcement->status === 'published') {
                throw ValidationException::withMessages([
                    'announcement' =>
                        'Published announcements cannot be edited directly.',
                ]);
            }

            $values = [
                'title' =>
                    $data['title'] ?? $announcement->title,

                'content' =>
                    $data['content'] ?? $announcement->content,

                'category' =>
                    array_key_exists('category', $data)
                        ? $data['category']
                        : $announcement->category,

                'priority' =>
                    array_key_exists('priority', $data)
                        ? $data['priority']
                        : $announcement->priority,

                'publish_at' =>
                    array_key_exists('publish_at', $data)
                        ? $data['publish_at']
                        : $announcement->publish_at,

                'expires_at' =>
                    array_key_exists('expires_at', $data)
                        ? $data['expires_at']
                        : $announcement->expires_at,

                'is_pinned' =>
                    array_key_exists('is_pinned', $data)
                        ? (bool) $data['is_pinned']
                        : $announcement->is_pinned,
            ];

            $this->validateDates($values);

            $announcement->update($values);

            if (array_key_exists('audiences', $data)) {
                $this->syncAudiences(
                    $announcement,
                    $data['audiences']
                );
            }

            return $announcement
                ->refresh()
                ->load('audiences');
        });
    }

    public function publish(
        Announcement $announcement,
        User $actor
    ): Announcement {
        return DB::transaction(function () use (
            $announcement,
            $actor
        ) {
            $announcement = Announcement::query()
                ->with('audiences')
                ->lockForUpdate()
                ->findOrFail($announcement->id);

            if ($announcement->status === 'published') {
                return $announcement;
            }

            if ($announcement->audiences->isEmpty()) {
                throw ValidationException::withMessages([
                    'audiences' =>
                        'An announcement must have an audience before publishing.',
                ]);
            }

            /*
             * publish_at = visibility schedule
             * published_at = audit of publication action
             */
            $announcement->update([
                'status' => 'published',
                'published_at' => now(),
                'published_by_user_id' => $actor->id,
            ]);

            return $announcement->refresh();
        });
    }

    public function cancel(
        Announcement $announcement
    ): Announcement {
        return DB::transaction(function () use ($announcement) {
            $announcement = Announcement::query()
                ->lockForUpdate()
                ->findOrFail($announcement->id);

            if ($announcement->status === 'cancelled') {
                return $announcement;
            }

            $announcement->update([
                'status' => 'cancelled',
            ]);

            return $announcement->refresh();
        });
    }

    public function visibleForEmployee(
        Employee $employee,
        int $perPage = 15
    ): LengthAwarePaginator {
        $now = now();

        return Announcement::query()
            ->where('status', 'published')
            ->where(function ($query) use ($now) {
                $query
                    ->whereNull('publish_at')
                    ->orWhere('publish_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            })
            ->whereHas('audiences', function ($query) use ($employee) {
                $query->where(function ($query) use ($employee) {
                    /*
                     * Everyone
                     */
                    $query->where(
                        fn ($query) =>
                            $query
                                ->where('audience_type', 'all')
                                ->whereNull('audience_id')
                    );

                    /*
                     * Direct employee audience
                     */
                    $query->orWhere(
                        fn ($query) =>
                            $query
                                ->where(
                                    'audience_type',
                                    'employee'
                                )
                                ->where(
                                    'audience_id',
                                    $employee->id
                                )
                    );

                    /*
                     * Department audience
                     */
                    if ($employee->department_id !== null) {
                        $query->orWhere(
                            fn ($query) =>
                                $query
                                    ->where(
                                        'audience_type',
                                        'department'
                                    )
                                    ->where(
                                        'audience_id',
                                        $employee->department_id
                                    )
                        );
                    }

                    /*
                     * Division is derived through department.
                     * Employee has no division_id.
                     */
                    $divisionId =
                        $employee->department?->division_id;

                    if ($divisionId !== null) {
                        $query->orWhere(
                            fn ($query) =>
                                $query
                                    ->where(
                                        'audience_type',
                                        'division'
                                    )
                                    ->where(
                                        'audience_id',
                                        $divisionId
                                    )
                        );
                    }
                });
            })
            ->with([
                'audiences',
                'reads' => fn ($query) =>
                    $query->where(
                        'employee_id',
                        $employee->id
                    ),
            ])
            ->orderByDesc('is_pinned')
            ->orderByDesc('publish_at')
            ->paginate($perPage);
    }

    public function markAsRead(
        Announcement $announcement,
        Employee $employee
    ): AnnouncementRead {
        return DB::transaction(function () use (
            $announcement,
            $employee
        ) {
            /*
             * Employee may only mark an announcement as read
             * when it is actually visible to them.
             */
            if (! $this->isVisibleTo(
                $announcement,
                $employee
            )) {
                throw ValidationException::withMessages([
                    'announcement' =>
                        'This announcement is not available to the employee.',
                ]);
            }

            return AnnouncementRead::firstOrCreate(
                [
                    'announcement_id' =>
                        $announcement->id,

                    'employee_id' =>
                        $employee->id,
                ],
                [
                    'read_at' => now(),
                ]
            );
        });
    }

    public function isVisibleTo(
        Announcement $announcement,
        Employee $employee
    ): bool {
        $announcement->loadMissing('audiences');
        $employee->loadMissing('department');

        if ($announcement->status !== 'published') {
            return false;
        }

        $now = now();

        if (
            $announcement->publish_at !== null
            && Carbon::parse($announcement->publish_at)->gt($now)
        ) {
            return false;
        }

        if (
            $announcement->expires_at !== null
            && Carbon::parse($announcement->expires_at)->lte($now)
        ) {
            return false;
        }

        foreach ($announcement->audiences as $audience) {
            if (
                $audience->audience_type === 'all'
                && $audience->audience_id === null
            ) {
                return true;
            }

            if (
                $audience->audience_type === 'employee'
                && (int) $audience->audience_id === $employee->id
            ) {
                return true;
            }

            if (
                $audience->audience_type === 'department'
                && (int) $audience->audience_id
                    === $employee->department_id
            ) {
                return true;
            }

            if (
                $audience->audience_type === 'division'
                && (int) $audience->audience_id
                    === $employee->department?->division_id
            ) {
                return true;
            }
        }

        return false;
    }

    private function syncAudiences(
        Announcement $announcement,
        array $audiences
    ): void {
        if ($audiences === []) {
            throw ValidationException::withMessages([
                'audiences' =>
                    'At least one announcement audience is required.',
            ]);
        }

        $normalized = [];

        foreach ($audiences as $audience) {
            $type = $audience['audience_type'] ?? null;
            $id = $audience['audience_id'] ?? null;

            if (
                ! in_array(
                    $type,
                    self::AUDIENCE_TYPES,
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'audiences' =>
                        'Unsupported announcement audience type.',
                ]);
            }

            if ($type === 'all') {
                $normalized = [[
                    'audience_type' => 'all',
                    'audience_id' => null,
                ]];

                /*
                 * "all" must be the sole audience.
                 */
                break;
            }

            if ($id === null) {
                throw ValidationException::withMessages([
                    'audiences' =>
                        "Audience ID is required for {$type}.",
                ]);
            }

            $this->validateAudienceTarget(
                $type,
                (int) $id
            );

            $normalized[] = [
                'audience_type' => $type,
                'audience_id' => (int) $id,
            ];
        }

        /*
         * Avoid duplicate audience rows.
         */
        $normalized = collect($normalized)
            ->unique(
                fn ($item) =>
                    $item['audience_type']
                    . ':'
                    . ($item['audience_id'] ?? 'all')
            )
            ->values()
            ->all();

        $announcement->audiences()->delete();

        foreach ($normalized as $audience) {
            $announcement->audiences()->create($audience);
        }
    }

    private function validateAudienceTarget(
        string $type,
        int $id
    ): void {
        $exists = match ($type) {
            'division' =>
                Division::query()
                    ->whereKey($id)
                    ->exists(),

            'department' =>
                Department::query()
                    ->whereKey($id)
                    ->exists(),

            'employee' =>
                Employee::query()
                    ->whereKey($id)
                    ->exists(),

            default => false,
        };

        if (! $exists) {
            throw ValidationException::withMessages([
                'audiences' =>
                    "The selected {$type} audience does not exist.",
            ]);
        }
    }

    private function validateDates(
        array $data
    ): void {
        if (
            empty($data['publish_at'])
            || empty($data['expires_at'])
        ) {
            return;
        }

        $publishAt =
            Carbon::parse($data['publish_at']);

        $expiresAt =
            Carbon::parse($data['expires_at']);

        if ($expiresAt->lte($publishAt)) {
            throw ValidationException::withMessages([
                'expires_at' =>
                    'Announcement expiry must be later than its publication time.',
            ]);
        }
    }
}