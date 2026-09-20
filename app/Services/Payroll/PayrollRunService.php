<?php

namespace App\Services\Payroll;

use App\Models\PayrollPeriod;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PayrollRunService
{
    public function create(
        PayrollPeriod $period
    ): PayrollRun {
        return DB::transaction(function () use ($period) {
            $period = PayrollPeriod::query()
                ->lockForUpdate()
                ->findOrFail($period->id);

            if (
                ! in_array(
                    $period->status,
                    ['open', 'processing'],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'period' =>
                        'Payroll runs may only be created for an open or processing period.',
                ]);
            }

            /*
             * Period row is locked, so run_number assignment
             * is serialized per payroll period.
             */
            $lastRunNumber = PayrollRun::query()
                ->where('payroll_period_id', $period->id)
                ->max('run_number');

            $runNumber = ((int) $lastRunNumber) + 1;

            return PayrollRun::create([
                'payroll_period_id' => $period->id,
                'run_number' => $runNumber,
                'status' => 'draft',

                'employee_count' => 0,

                'total_gross' => 0,
                'total_deductions' => 0,
                'total_net' => 0,
            ]);
        });
    }

    public function start(
        PayrollRun $run,
        User $actor
    ): PayrollRun {
        return DB::transaction(function () use ($run, $actor) {
            $run = PayrollRun::query()
                ->with('period')
                ->lockForUpdate()
                ->findOrFail($run->id);

            if ($run->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only draft payroll runs may start processing.',
                ]);
            }

            if (
                ! in_array(
                    $run->period->status,
                    ['open', 'processing'],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'period' =>
                        'The payroll period is not available for processing.',
                ]);
            }

            $run->update([
                'status' => 'processing',
                'processed_by_user_id' => $actor->id,
                'processed_at' => now(),
                'error_message' => null,
            ]);

            return $run->refresh();
        });
    }

    public function complete(
        PayrollRun $run,
        array $totals
    ): PayrollRun {
        return DB::transaction(function () use ($run, $totals) {
            $run = PayrollRun::query()
                ->lockForUpdate()
                ->findOrFail($run->id);

            if ($run->status !== 'processing') {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only a processing payroll run may be completed.',
                ]);
            }

            $run->update([
                'status' => 'completed',

                'employee_count' =>
                    (int) $totals['employee_count'],

                'total_gross' =>
                    $totals['total_gross'],

                'total_deductions' =>
                    $totals['total_deductions'],

                'total_net' =>
                    $totals['total_net'],

                'error_message' => null,
            ]);

            return $run->refresh();
        });
    }

    public function fail(
        PayrollRun $run,
        Throwable $exception
    ): PayrollRun {
        return DB::transaction(function () use (
            $run,
            $exception
        ) {
            $run = PayrollRun::query()
                ->lockForUpdate()
                ->findOrFail($run->id);

            if ($run->status !== 'processing') {
                return $run;
            }

            $run->update([
                'status' => 'failed',

                /*
                 * Do not persist stack traces, SQL credentials,
                 * connection strings, etc.
                 */
                'error_message' =>
                    $this->safeErrorMessage($exception),
            ]);

            return $run->refresh();
        });
    }

    public function finalize(
        PayrollRun $run,
        User $actor
    ): PayrollRun {
        return DB::transaction(function () use ($run, $actor) {
            $run = PayrollRun::query()
                ->lockForUpdate()
                ->findOrFail($run->id);

            if ($run->status !== 'completed') {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only a completed payroll run may be finalized.',
                ]);
            }

            $run->update([
                'status' => 'finalized',
                'finalized_at' => now(),
                'finalized_by_user_id' => $actor->id,
            ]);

            return $run->refresh();
        });
    }

    public function cancel(
        PayrollRun $run
    ): PayrollRun {
        return DB::transaction(function () use ($run) {
            $run = PayrollRun::query()
                ->lockForUpdate()
                ->findOrFail($run->id);

            if (
                ! in_array(
                    $run->status,
                    ['draft', 'failed'],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only draft or failed payroll runs may be cancelled.',
                ]);
            }

            $run->update([
                'status' => 'cancelled',
            ]);

            return $run->refresh();
        });
    }

    private function safeErrorMessage(
        Throwable $exception
    ): string {
        /*
         * V1 generic message.
         * Detailed exception remains in Laravel logs.
         */
        return 'Payroll processing failed. See application logs for details.';
    }
}