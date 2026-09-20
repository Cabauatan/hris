<?php

namespace App\Services\Scheduling;

use App\Models\Employee;
use App\Models\EmployeeScheduleAssignment;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\Audit\AuditService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeScheduleAssignmentService
{
    public function __construct(
        private AuditService $audit
    ) {}

    public function assign(
        Employee $employee,
        WorkSchedule $schedule,
        array $data,
        User $actor
    ): EmployeeScheduleAssignment {
        return DB::transaction(function () use (
            $employee,
            $schedule,
            $data,
            $actor
        ) {
            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($employee->id);

            $schedule = WorkSchedule::query()
                ->findOrFail($schedule->id);

            if (! $employee->is_active) {
                throw ValidationException::withMessages([
                    'employee_id' =>
                        'The employee is inactive.',
                ]);
            }

            if (! $schedule->is_active) {
                throw ValidationException::withMessages([
                    'work_schedule_id' =>
                        'The selected work schedule is inactive.',
                ]);
            }

            $effectiveFrom = Carbon::parse(
                $data['effective_from']
            )->toDateString();

            $effectiveTo = isset($data['effective_to'])
                && $data['effective_to'] !== null
                    ? Carbon::parse(
                        $data['effective_to']
                    )->toDateString()
                    : null;

            if (
                $effectiveTo !== null
                && $effectiveTo < $effectiveFrom
            ) {
                throw ValidationException::withMessages([
                    'effective_to' =>
                        'The effective end date cannot be earlier than the start date.',
                ]);
            }

            $this->ensureNoOverlap(
                $employee,
                $effectiveFrom,
                $effectiveTo
            );

            $assignment =
                EmployeeScheduleAssignment::create([
                    'employee_id' =>
                        $employee->id,

                    'work_schedule_id' =>
                        $schedule->id,

                    'effective_from' =>
                        $effectiveFrom,

                    'effective_to' =>
                        $effectiveTo,
                ]);

            /*
             * Audit is part of the same DB transaction.
             */
            $this->audit->logModel(
                module: 'scheduling',
                action: 'schedule_assigned',
                auditableType:
                    'employee_schedule_assignment',
                model: $assignment,
                actor: $actor,
                description:
                    "Assigned work schedule [{$schedule->id}] to employee [{$employee->employee_number}].",
                newValues: [
                    'employee_id' =>
                        $assignment->employee_id,

                    'work_schedule_id' =>
                        $assignment->work_schedule_id,

                    'effective_from' =>
                        $assignment->effective_from,

                    'effective_to' =>
                        $assignment->effective_to,
                ],
            );

            return $assignment;
        });
    }

    public function applicableOn(
        Employee $employee,
        string $date
    ): ?EmployeeScheduleAssignment {
        $date = Carbon::parse($date)
            ->toDateString();

        return EmployeeScheduleAssignment::query()
            ->with('workSchedule.days.shift')
            ->where(
                'employee_id',
                $employee->id
            )
            ->whereDate(
                'effective_from',
                '<=',
                $date
            )
            ->where(function ($query) use ($date) {
                $query
                    ->whereNull('effective_to')
                    ->orWhereDate(
                        'effective_to',
                        '>=',
                        $date
                    );
            })
            ->orderByDesc('effective_from')
            ->first();
    }

    public function history(Employee $employee)
    {
        return EmployeeScheduleAssignment::query()
            ->with('workSchedule')
            ->where(
                'employee_id',
                $employee->id
            )
            ->orderByDesc('effective_from')
            ->get();
    }

    private function ensureNoOverlap(
        Employee $employee,
        string $effectiveFrom,
        ?string $effectiveTo
    ): void {
        $overlap = EmployeeScheduleAssignment::query()
            ->where(
                'employee_id',
                $employee->id
            )
            ->where(function ($query) use (
                $effectiveFrom,
                $effectiveTo
            ) {
                /*
                 * Existing range overlaps the requested
                 * range when:
                 *
                 * existing.start <= new.end
                 * AND
                 * existing.end >= new.start
                 *
                 * NULL end means open-ended.
                 */
                if ($effectiveTo !== null) {
                    $query->whereDate(
                        'effective_from',
                        '<=',
                        $effectiveTo
                    );
                }

                $query->where(
                    function ($query) use (
                        $effectiveFrom
                    ) {
                        $query
                            ->whereNull('effective_to')
                            ->orWhereDate(
                                'effective_to',
                                '>=',
                                $effectiveFrom
                            );
                    }
                );
            })
            ->lockForUpdate()
            ->exists();

        if ($overlap) {
            throw ValidationException::withMessages([
                'effective_from' =>
                    'The schedule assignment overlaps an existing employee schedule assignment.',
            ]);
        }
    }
}