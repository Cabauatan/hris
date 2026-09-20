<?php

namespace App\Services\Cto;

use App\Models\CtoTransaction;
use App\Models\Employee;
use App\Models\EmployeeCtoBalance;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CtoBalanceService
{
    /**
     * Apply a signed CTO movement.
     *
     * Positive minutes = credit
     * Negative minutes = usage
     */
    public function transact(
        Employee $employee,
        int $minutes,
        string $transactionType,
        array $data = []
    ): CtoTransaction {
        return DB::transaction(function () use (
            $employee,
            $minutes,
            $transactionType,
            $data
        ) {
            if ($minutes === 0) {
                throw ValidationException::withMessages([
                    'minutes' =>
                        'CTO transaction minutes cannot be zero.',
                ]);
            }

            $balance = EmployeeCtoBalance::query()
                ->where('employee_id', $employee->id)
                ->lockForUpdate()
                ->first();

            if (! $balance) {
                $balance = EmployeeCtoBalance::create([
                    'employee_id' => $employee->id,
                    'earned_minutes' => 0,
                    'used_minutes' => 0,
                    'adjustment_minutes' => 0,
                ]);
            }

            $before = $this->availableMinutes($balance);
            $after = $before + $minutes;

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'cto_balance' =>
                        'The employee does not have enough CTO balance.',
                ]);
            }

            $transaction = CtoTransaction::create([
                'employee_id' => $employee->id,
                'transaction_type' => $transactionType,

                'minutes' => $minutes,

                'balance_before' => $before,
                'balance_after' => $after,

                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,

                'remarks' => $data['remarks'] ?? null,
            ]);

            $this->applyAggregateMovement(
                $balance,
                $minutes,
                $transactionType
            );

            return $transaction;
        });
    }

    public function availableMinutes(
        EmployeeCtoBalance $balance
    ): int {
        return (int) $balance->earned_minutes
            + (int) $balance->adjustment_minutes
            - (int) $balance->used_minutes;
    }

    private function applyAggregateMovement(
        EmployeeCtoBalance $balance,
        int $minutes,
        string $transactionType
    ): void {
        switch ($transactionType) {
            case 'earning':
                if ($minutes < 0) {
                    $this->invalidSign($transactionType);
                }

                $balance->earned_minutes += $minutes;
                break;

            case 'usage':
                if ($minutes > 0) {
                    $this->invalidSign($transactionType);
                }

                $balance->used_minutes += abs($minutes);
                break;

            case 'adjustment':
                $balance->adjustment_minutes += $minutes;
                break;

            case 'usage_reversal':
                if ($minutes < 0) {
                    $this->invalidSign($transactionType);
                }

                if ($minutes > $balance->used_minutes) {
                    throw ValidationException::withMessages([
                        'minutes' =>
                            'CTO usage reversal exceeds recorded usage.',
                    ]);
                }

                $balance->used_minutes -= $minutes;
                break;

            default:
                throw ValidationException::withMessages([
                    'transaction_type' =>
                        'Unsupported CTO transaction type.',
                ]);
        }

        $balance->save();
    }

    private function invalidSign(
        string $transactionType
    ): never {
        throw ValidationException::withMessages([
            'minutes' =>
                "Invalid minute sign for {$transactionType}.",
        ]);
    }
}