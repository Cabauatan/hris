<?php

namespace App\Services\Attendance;

use App\Models\Employee;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimeLogService
{
    public function paginate(
        int $perPage = 50,
        ?int $employeeId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?bool $isValid = null,
        ?string $source = null
    ): LengthAwarePaginator {
        return TimeLog::query()
            ->with([
                'employee',
                'createdBy',
            ])
            ->when(
                $employeeId !== null,
                fn ($query) => $query->where(
                    'employee_id',
                    $employeeId
                )
            )
            ->when(
                $dateFrom !== null,
                fn ($query) => $query->where(
                    'logged_at',
                    '>=',
                    Carbon::parse($dateFrom)->startOfDay()
                )
            )
            ->when(
                $dateTo !== null,
                fn ($query) => $query->where(
                    'logged_at',
                    '<=',
                    Carbon::parse($dateTo)->endOfDay()
                )
            )
            ->when(
                $isValid !== null,
                fn ($query) => $query->where(
                    'is_valid',
                    $isValid
                )
            )
            ->when(
                $source !== null,
                fn ($query) => $query->where(
                    'source',
                    $source
                )
            )
            ->orderByDesc('logged_at')
            ->paginate($perPage);
    }

    /**
     * Create a manually encoded time log.
     *
     * Authorization for who may manually create logs will be
     * handled by Policies later.
     */
    public function createManual(
        Employee $employee,
        array $data,
        User $createdBy
    ): TimeLog {
        return DB::transaction(function () use (
            $employee,
            $data,
            $createdBy
        ) {
            $employee = Employee::query()
                ->lockForUpdate()
                ->findOrFail($employee->id);

            if (! $employee->is_active) {
                throw ValidationException::withMessages([
                    'employee_id' =>
                        'Time logs cannot be added to an inactive employee.',
                ]);
            }

            return TimeLog::create([
                'employee_id' => $employee->id,
                'logged_at' => Carbon::parse($data['logged_at']),
                'log_type' => $data['log_type'] ?? null,

                'source' => 'manual',

                'device_code' => null,
                'external_reference' => null,

                'reason' => $data['reason'] ?? null,
                'remarks' => $data['remarks'] ?? null,

                'created_by_user_id' => $createdBy->id,
                'is_valid' => true,
            ]);
        });
    }

    /**
     * Store a time log coming from an external device/provider.
     *
     * Do not blindly assume external_reference is globally unique.
     */
    public function createImported(
        Employee $employee,
        array $data
    ): TimeLog {
        return DB::transaction(function () use ($employee, $data) {
            $employee = Employee::query()
                ->findOrFail($employee->id);

            return TimeLog::create([
                'employee_id' => $employee->id,
                'logged_at' => Carbon::parse($data['logged_at']),
                'log_type' => $data['log_type'] ?? null,

                'source' => $data['source'],

                'device_code' => $data['device_code'] ?? null,
                'external_reference' =>
                    $data['external_reference'] ?? null,

                'reason' => null,
                'remarks' => $data['remarks'] ?? null,

                'created_by_user_id' => null,
                'is_valid' => true,
            ]);
        });
    }

    /**
     * Mark a raw log invalid instead of deleting it.
     */
    public function invalidate(
        TimeLog $timeLog,
        string $reason,
        ?string $remarks = null
    ): TimeLog {
        return DB::transaction(function () use (
            $timeLog,
            $reason,
            $remarks
        ) {
            $timeLog = TimeLog::query()
                ->lockForUpdate()
                ->findOrFail($timeLog->id);

            if (! $timeLog->is_valid) {
                return $timeLog;
            }

            $timeLog->update([
                'is_valid' => false,
                'reason' => $reason,
                'remarks' => $remarks ?? $timeLog->remarks,
            ]);

            return $timeLog->refresh();
        });
    }

    /**
     * Restore an invalidated raw log.
     *
     * This should later require a strong permission.
     */
    public function restore(
        TimeLog $timeLog,
        ?string $remarks = null
    ): TimeLog {
        return DB::transaction(function () use (
            $timeLog,
            $remarks
        ) {
            $timeLog = TimeLog::query()
                ->lockForUpdate()
                ->findOrFail($timeLog->id);

            if ($timeLog->is_valid) {
                return $timeLog;
            }

            $timeLog->update([
                'is_valid' => true,
                'remarks' => $remarks ?? $timeLog->remarks,
            ]);

            return $timeLog->refresh();
        });
    }

    /**
     * Return valid raw logs for an employee within an exact
     * datetime window.
     *
     * Important for overnight shifts.
     */
    public function validLogsBetween(
        Employee $employee,
        Carbon $from,
        Carbon $to
    ) {
        if ($to->lt($from)) {
            throw ValidationException::withMessages([
                'to' =>
                    'The end of the time-log window cannot be earlier than the start.',
            ]);
        }

        return TimeLog::query()
            ->where('employee_id', $employee->id)
            ->where('is_valid', true)
            ->whereBetween('logged_at', [
                $from,
                $to,
            ])
            ->orderBy('logged_at')
            ->get();
    }
}