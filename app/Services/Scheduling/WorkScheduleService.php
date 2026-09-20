<?php

namespace App\Services\Scheduling;

use App\Models\Shift;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkScheduleService
{
    public function __construct(
        private AuditService $audit
    ) {}

    public function paginate(
        int $perPage = 15,
        ?string $search = null,
        ?bool $isActive = null
    ): LengthAwarePaginator {
        return WorkSchedule::query()
            ->with([
                'days.shift',
            ])
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when(
                $isActive !== null,
                fn ($query) =>
                    $query->where('is_active', $isActive)
            )
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(
        array $data,
        User $actor
    ): WorkSchedule {
        return DB::transaction(function () use (
            $data,
            $actor
        ) {
            $schedule = WorkSchedule::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'description' =>
                    $data['description'] ?? null,
                'is_active' =>
                    $data['is_active'] ?? true,
            ]);

            if (array_key_exists('days', $data)) {
                $this->syncDays(
                    $schedule,
                    $data['days']
                );
            }

            $schedule = $schedule
                ->refresh()
                ->load('days.shift');

            $this->audit->logModel(
                module: 'scheduling',
                action: 'created',
                auditableType: 'work_schedule',
                model: $schedule,
                actor: $actor,
                description:
                    "Created work schedule [{$schedule->code}].",
                newValues:
                    $this->auditValues($schedule),
            );

            return $schedule;
        });
    }

    public function update(
        WorkSchedule $schedule,
        array $data,
        User $actor
    ): WorkSchedule {
        return DB::transaction(function () use (
            $schedule,
            $data,
            $actor
        ) {
            $schedule = WorkSchedule::query()
                ->with('days')
                ->lockForUpdate()
                ->findOrFail($schedule->id);

            $oldValues =
                $this->auditValues($schedule);

            $updates = [];

            foreach ([
                'code',
                'name',
                'description',
                'is_active',
            ] as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            if ($updates !== []) {
                $schedule->update($updates);
            }

            if (array_key_exists('days', $data)) {
                $this->syncDays(
                    $schedule,
                    $data['days']
                );
            }

            $schedule = $schedule
                ->refresh()
                ->load('days.shift');

            $newValues =
                $this->auditValues($schedule);

            /*
             * Do not create audit noise if the resulting
             * schedule definition did not actually change.
             */
            if ($oldValues !== $newValues) {
                $this->audit->logModel(
                    module: 'scheduling',
                    action: 'updated',
                    auditableType: 'work_schedule',
                    model: $schedule,
                    actor: $actor,
                    description:
                        "Updated work schedule [{$schedule->code}].",
                    oldValues: $oldValues,
                    newValues: $newValues,
                );
            }

            return $schedule;
        });
    }

    public function activate(
        WorkSchedule $schedule,
        User $actor
    ): WorkSchedule {
        return DB::transaction(function () use (
            $schedule,
            $actor
        ) {
            $schedule = WorkSchedule::query()
                ->lockForUpdate()
                ->findOrFail($schedule->id);

            if ($schedule->is_active) {
                return $schedule->refresh();
            }

            $schedule->update([
                'is_active' => true,
            ]);

            $schedule->refresh();

            $this->audit->logModel(
                module: 'scheduling',
                action: 'activated',
                auditableType: 'work_schedule',
                model: $schedule,
                actor: $actor,
                description:
                    "Activated work schedule [{$schedule->code}].",
                oldValues: [
                    'is_active' => false,
                ],
                newValues: [
                    'is_active' => true,
                ],
            );

            return $schedule;
        });
    }

    public function deactivate(
        WorkSchedule $schedule,
        User $actor
    ): WorkSchedule {
        return DB::transaction(function () use (
            $schedule,
            $actor
        ) {
            $schedule = WorkSchedule::query()
                ->lockForUpdate()
                ->findOrFail($schedule->id);

            if (! $schedule->is_active) {
                return $schedule->refresh();
            }

            $schedule->update([
                'is_active' => false,
            ]);

            $schedule->refresh();

            $this->audit->logModel(
                module: 'scheduling',
                action: 'deactivated',
                auditableType: 'work_schedule',
                model: $schedule,
                actor: $actor,
                description:
                    "Deactivated work schedule [{$schedule->code}].",
                oldValues: [
                    'is_active' => true,
                ],
                newValues: [
                    'is_active' => false,
                ],
            );

            return $schedule;
        });
    }

    private function syncDays(
        WorkSchedule $schedule,
        array $days
    ): void {
        $this->validateDays($days);

        /*
         * WorkScheduleDay represents the current definition
         * of a schedule template, so replacing its seven
         * day definitions is acceptable.
         */
        $schedule->days()->delete();

        foreach ($days as $day) {
            $schedule->days()->create([
                'day_of_week' =>
                    $day['day_of_week'],

                'shift_id' =>
                    $day['shift_id'] ?? null,

                'is_working_day' =>
                    $day['is_working_day'],
            ]);
        }
    }

    private function validateDays(array $days): void
    {
        if (count($days) !== 7) {
            throw ValidationException::withMessages([
                'days' =>
                    'A work schedule must define all seven days of the week.',
            ]);
        }

        $dayNumbers = collect($days)
            ->pluck('day_of_week')
            ->map(fn ($day) => (int) $day)
            ->sort()
            ->values()
            ->all();

        if ($dayNumbers !== [1, 2, 3, 4, 5, 6, 7]) {
            throw ValidationException::withMessages([
                'days' =>
                    'The schedule must contain each day from Monday (1) through Sunday (7) exactly once.',
            ]);
        }

        foreach ($days as $index => $day) {
            $working =
                (bool) $day['is_working_day'];

            $shiftId =
                $day['shift_id'] ?? null;

            if ($working && $shiftId === null) {
                throw ValidationException::withMessages([
                    "days.{$index}.shift_id" =>
                        'A working day must have a shift.',
                ]);
            }

            if (! $working && $shiftId !== null) {
                throw ValidationException::withMessages([
                    "days.{$index}.shift_id" =>
                        'A non-working day must not have a shift.',
                ]);
            }

            if ($shiftId !== null) {
                $shift = Shift::query()
                    ->find($shiftId);

                if (
                    ! $shift
                    || ! $shift->is_active
                ) {
                    throw ValidationException::withMessages([
                        "days.{$index}.shift_id" =>
                            'The selected shift is invalid or inactive.',
                    ]);
                }
            }
        }
    }

    /**
     * Controlled and deterministic schedule snapshot.
     *
     * Days are sorted so equivalent schedule definitions
     * do not produce false audit differences merely because
     * of database retrieval order.
     */
    private function auditValues(
        WorkSchedule $schedule
    ): array {
        if (! $schedule->relationLoaded('days')) {
            $schedule->load('days');
        }

        return [
            'code' =>
                $schedule->code,

            'name' =>
                $schedule->name,

            'description' =>
                $schedule->description,

            'is_active' =>
                (bool) $schedule->is_active,

            'days' =>
                $schedule->days
                    ->sortBy('day_of_week')
                    ->values()
                    ->map(
                        fn ($day) => [
                            'day_of_week' =>
                                (int) $day->day_of_week,

                            'shift_id' =>
                                $day->shift_id,

                            'is_working_day' =>
                                (bool) $day->is_working_day,
                        ]
                    )
                    ->all(),
        ];
    }
}