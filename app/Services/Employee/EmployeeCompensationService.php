<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Models\EmployeeCompensation;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeCompensationService
{
    public function __construct(
        private AuditService $audit
    ) {}

    /**
     * Set a new compensation rate effective on a specific date.
     *
     * Existing historical compensation is preserved.
     */
    public function change(
        Employee $employee,
        array $data,
        User $actor
    ): EmployeeCompensation {
        return DB::transaction(function () use (
            $employee,
            $data,
            $actor
        ) {
            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($employee->id);

            $this->validateData($data);

            $effectiveFrom = Carbon::parse(
                $data['effective_from']
            )->startOfDay();

            $this->ensureNoFutureConflict(
                $employee,
                $effectiveFrom
            );

            $current = EmployeeCompensation::query()
                ->where('employee_id', $employee->id)
                ->whereNull('effective_to')
                ->lockForUpdate()
                ->latest('effective_from')
                ->first();

            /*
             * Keep only non-sensitive structural information
             * in the general audit log.
             *
             * The actual basic rate remains authoritative in
             * EmployeeCompensation history.
             */
            $oldAuditValues = $current
                ? [
                    'compensation_id' => $current->id,
                    'pay_type' => $current->pay_type,
                    'effective_from' =>
                        $current->effective_from?->toDateString(),
                ]
                : [];

            if ($current) {
                $currentFrom = Carbon::parse(
                    $current->effective_from
                )->startOfDay();

                if ($effectiveFrom->lte($currentFrom)) {
                    throw ValidationException::withMessages([
                        'effective_from' =>
                            'The new compensation must take effect after the current compensation start date.',
                    ]);
                }

                $current->update([
                    'effective_to' => $effectiveFrom
                        ->copy()
                        ->subDay()
                        ->toDateString(),
                ]);
            }

            $compensation = EmployeeCompensation::create([
                'employee_id' => $employee->id,
                'pay_type' => $data['pay_type'],
                'basic_rate' => $data['basic_rate'],
                'effective_from' =>
                    $effectiveFrom->toDateString(),
                'effective_to' => null,
                'remarks' => $data['remarks'] ?? null,
            ]);

            $this->audit->logModel(
                module: 'employee',
                action: 'compensation_changed',
                auditableType: 'employee',
                model: $employee,
                actor: $actor,
                description:
                    "Changed compensation for employee [{$employee->employee_number}].",
                oldValues: $oldAuditValues,
                newValues: [
                    'compensation_id' =>
                        $compensation->id,

                    'pay_type' =>
                        $compensation->pay_type,

                    'effective_from' =>
                        $compensation->effective_from
                            ?->toDateString(),
                ],
            );

            return $compensation;
        });
    }

    /**
     * Get compensation applicable on a particular date.
     */
    public function applicableOn(
        Employee $employee,
        string $date
    ): ?EmployeeCompensation {
        $date = Carbon::parse($date)
            ->toDateString();

        return EmployeeCompensation::query()
            ->where('employee_id', $employee->id)
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

    /**
     * Return compensation history newest first.
     */
    public function history(Employee $employee)
    {
        return EmployeeCompensation::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('effective_from')
            ->get();
    }

    private function validateData(array $data): void
    {
        $basicRate = trim(
            (string) ($data['basic_rate'] ?? '')
        );

        /*
         * Validate as a decimal string.
         * Do not convert monetary values to float.
         */
        if (
            ! preg_match(
                '/^\d+(?:\.\d+)?$/',
                $basicRate
            )
        ) {
            throw ValidationException::withMessages([
                'basic_rate' =>
                    'The basic rate must be a valid non-negative number.',
            ]);
        }
    }

    /**
     * Prevent inserting a new current compensation before an
     * already-scheduled future compensation record.
     */
    private function ensureNoFutureConflict(
        Employee $employee,
        Carbon $effectiveFrom
    ): void {
        $exists = EmployeeCompensation::query()
            ->where(
                'employee_id',
                $employee->id
            )
            ->whereDate(
                'effective_from',
                '>=',
                $effectiveFrom->toDateString()
            )
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'effective_from' =>
                    'A compensation record already exists on or after this effective date.',
            ]);
        }
    }
}