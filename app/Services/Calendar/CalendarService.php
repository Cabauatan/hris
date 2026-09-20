<?php

namespace App\Services\Calendar;

use App\Models\CalendarEvent;
use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalendarService
{
    private const AUDIENCE_TYPES = [
        'all',
        'division',
        'department',
        'employee',
    ];

    public function createEvent(
        array $data,
        User $actor
    ): CalendarEvent {
        return DB::transaction(function () use ($data, $actor) {
            $this->validateDates($data);

            $event = CalendarEvent::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,

                'start_at' => $data['start_at'],
                'end_at' => $data['end_at'] ?? null,

                'is_all_day' =>
                    (bool) ($data['is_all_day'] ?? false),

                'location' => $data['location'] ?? null,

                'status' => 'active',

                'created_by_user_id' => $actor->id,
            ]);

            $this->syncAudiences(
                $event,
                $data['audiences'] ?? []
            );

            return $event->load('audiences');
        });
    }

    public function updateEvent(
        CalendarEvent $event,
        array $data
    ): CalendarEvent {
        return DB::transaction(function () use ($event, $data) {
            $event = CalendarEvent::query()
                ->lockForUpdate()
                ->findOrFail($event->id);

            if ($event->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'event' =>
                        'Cancelled calendar events cannot be edited.',
                ]);
            }

            $values = [
                'title' =>
                    $data['title'] ?? $event->title,

                'description' =>
                    array_key_exists('description', $data)
                        ? $data['description']
                        : $event->description,

                'start_at' =>
                    $data['start_at'] ?? $event->start_at,

                'end_at' =>
                    array_key_exists('end_at', $data)
                        ? $data['end_at']
                        : $event->end_at,

                'is_all_day' =>
                    array_key_exists('is_all_day', $data)
                        ? (bool) $data['is_all_day']
                        : $event->is_all_day,

                'location' =>
                    array_key_exists('location', $data)
                        ? $data['location']
                        : $event->location,
            ];

            $this->validateDates($values);

            $event->update($values);

            if (array_key_exists('audiences', $data)) {
                $this->syncAudiences(
                    $event,
                    $data['audiences']
                );
            }

            return $event
                ->refresh()
                ->load('audiences');
        });
    }

    public function cancelEvent(
        CalendarEvent $event
    ): CalendarEvent {
        return DB::transaction(function () use ($event) {
            $event = CalendarEvent::query()
                ->lockForUpdate()
                ->findOrFail($event->id);

            if ($event->status === 'cancelled') {
                return $event;
            }

            $event->update([
                'status' => 'cancelled',
            ]);

            return $event->refresh();
        });
    }

    public function forEmployee(
        Employee $employee,
        Carbon|string $from,
        Carbon|string $to
    ): Collection {
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->endOfDay();

        if ($to->lt($from)) {
            throw ValidationException::withMessages([
                'to' =>
                    'Calendar end date must be on or after the start date.',
            ]);
        }

        $employee->loadMissing('department');

        return collect()
            ->concat(
                $this->companyEvents(
                    $employee,
                    $from,
                    $to
                )
            )
            ->concat(
                $this->holidays(
                    $from,
                    $to
                )
            )
            ->concat(
                $this->approvedLeaves(
                    $employee,
                    $from,
                    $to
                )
            )
            ->sortBy('start')
            ->values();
    }

    private function companyEvents(
        Employee $employee,
        Carbon $from,
        Carbon $to
    ): Collection {
        $divisionId =
            $employee->department?->division_id;

        return CalendarEvent::query()
            ->where('status', 'active')

            /*
             * Event overlaps requested calendar range.
             */
            ->where('start_at', '<=', $to)
            ->where(function ($query) use ($from) {
                $query
                    ->whereNull('end_at')
                    ->orWhere('end_at', '>=', $from);
            })

            ->whereHas(
                'audiences',
                function ($query) use (
                    $employee,
                    $divisionId
                ) {
                    $query->where(function ($query) use (
                        $employee,
                        $divisionId
                    ) {
                        $query->where(
                            fn ($query) =>
                                $query
                                    ->where(
                                        'audience_type',
                                        'all'
                                    )
                                    ->whereNull('audience_id')
                        );

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
                }
            )
            ->get()
            ->map(fn (CalendarEvent $event) => [
                'source' => 'calendar_event',
                'source_id' => $event->id,

                'title' => $event->title,

                'start' => $event->start_at,
                'end' => $event->end_at,

                'all_day' => (bool) $event->is_all_day,

                'location' => $event->location,
            ]);
    }

    private function holidays(
        Carbon $from,
        Carbon $to
    ): Collection {
        return Holiday::query()
            ->where('is_active', true)
            ->whereBetween(
                'holiday_date',
                [
                    $from->toDateString(),
                    $to->toDateString(),
                ]
            )
            ->get()
            ->map(fn (Holiday $holiday) => [
                'source' => 'holiday',
                'source_id' => $holiday->id,

                'title' => $holiday->name,

                'start' => $holiday->holiday_date,
                'end' => $holiday->holiday_date,

                'all_day' => true,

                'holiday_type' =>
                    $holiday->holiday_type,

                'is_working_day' =>
                    (bool) $holiday->is_working_day,
            ]);
    }

    private function approvedLeaves(
        Employee $employee,
        Carbon $from,
        Carbon $to
    ): Collection {
        /*
         * Employee ESS calendar:
         * show their own approved leave details.
         *
         * Team leave visibility should use a separate privacy-safe
         * method instead of exposing everyone here.
         */
        return LeaveRequest::query()
            ->with('leaveType')
            ->where(
                'employee_id',
                $employee->id
            )
            ->where('status', 'approved')
            ->where(
                'date_from',
                '<=',
                $to->toDateString()
            )
            ->where(
                'date_to',
                '>=',
                $from->toDateString()
            )
            ->get()
            ->map(fn (LeaveRequest $leave) => [
                'source' => 'leave',
                'source_id' => $leave->id,

                'title' =>
                    $leave->leaveType?->name
                    ?? 'Approved Leave',

                'start' => $leave->date_from,
                'end' => $leave->date_to,

                'all_day' => true,

                'day_part' => $leave->day_part ?? null,
            ]);
    }

    private function syncAudiences(
        CalendarEvent $event,
        array $audiences
    ): void {
        if ($audiences === []) {
            throw ValidationException::withMessages([
                'audiences' =>
                    'At least one calendar audience is required.',
            ]);
        }

        $normalized = [];

        foreach ($audiences as $audience) {
            $type =
                $audience['audience_type'] ?? null;

            $id =
                $audience['audience_id'] ?? null;

            if (
                ! in_array(
                    $type,
                    self::AUDIENCE_TYPES,
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'audiences' =>
                        'Unsupported calendar audience type.',
                ]);
            }

            if ($type === 'all') {
                $normalized = [[
                    'audience_type' => 'all',
                    'audience_id' => null,
                ]];

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

        $normalized = collect($normalized)
            ->unique(
                fn ($item) =>
                    $item['audience_type']
                    . ':'
                    . ($item['audience_id'] ?? 'all')
            )
            ->values()
            ->all();

        $event->audiences()->delete();

        foreach ($normalized as $audience) {
            $event->audiences()->create($audience);
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
            empty($data['start_at'])
            || empty($data['end_at'])
        ) {
            return;
        }

        $start = Carbon::parse($data['start_at']);
        $end = Carbon::parse($data['end_at']);

        if ($end->lt($start)) {
            throw ValidationException::withMessages([
                'end_at' =>
                    'Calendar event end must not be earlier than its start.',
            ]);
        }
    }
}