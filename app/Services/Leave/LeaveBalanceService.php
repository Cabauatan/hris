<?php

namespace App\Services\Leave;

use App\Models\Employee;
use App\Models\EmployeeLeaveBalance;
use App\Models\LeaveCreditTransaction;
use App\Models\LeaveType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveBalanceService
{
    /**
     * Apply a signed leave-balance movement.
     *
     * Positive = increases balance
     * Negative = decreases balance
     */
    public function transact(
        Employee $employee,
        LeaveType $leaveType,
        int $year,
        string $days,
        string $transactionType,
        array $data = []
    ): LeaveCreditTransaction {
        return DB::transaction(function () use (
            $employee,
            $leaveType,
            $year,
            $days,
            $transactionType,
            $data
        ) {
            $balance = EmployeeLeaveBalance::query()
                ->where('employee_id', $employee->id)
                ->where('leave_type_id', $leaveType->id)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (!$balance) {
                $balance = EmployeeLeaveBalance::create([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $year,

                    'allocated_days' => 0,
                    'earned_days' => 0,
                    'adjustment_days' => 0,
                    'used_days' => 0,
                ]);

                /*
                 * Locking a row that didn't previously exist cannot
                 * protect the insert race by itself. The DB unique
                 * constraint on employee + leave type + year remains
                 * mandatory.
                 */
            }

            $balanceBefore = $this->availableBalance($balance);

            $balanceAfter = bcadd(
                $balanceBefore,
                $days,
                2
            );

            if (
                $leaveType->deduct_from_balance
                && bccomp($balanceAfter, '0.00', 2) < 0
            ) {
                throw ValidationException::withMessages([
                    'leave_balance' =>
                        'The employee does not have enough leave balance.',
                ]);
            }

            $transaction = LeaveCreditTransaction::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,

                'transaction_type' => $transactionType,

                /*
                 * Signed amount.
                 *
                 * +1.00 credit
                 * -1.00 usage
                 */
                'days' => $days,

                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,

                'source_type' => $data['source_type'] ?? null,
                'source_id' => $data['source_id'] ?? null,

                'remarks' => $data['remarks'] ?? null,
            ]);

            $this->applyAggregateMovement(
                $balance,
                $days,
                $transactionType
            );

            return $transaction;
        });
    }

    public function availableBalance(
        EmployeeLeaveBalance $balance
    ): string {
        /*
         * allocated + earned + adjustment - used
         */
        $value = bcadd(
            (string) $balance->allocated_days,
            (string) $balance->earned_days,
            2
        );

        $value = bcadd(
            $value,
            (string) $balance->adjustment_days,
            2
        );

        return bcsub(
            $value,
            (string) $balance->used_days,
            2
        );
    }

    private function applyAggregateMovement(
        EmployeeLeaveBalance $balance,
        string $days,
        string $transactionType
    ): void {
        switch ($transactionType) {
            case 'allocation':
                $balance->allocated_days = bcadd(
                    (string) $balance->allocated_days,
                    $days,
                    2
                );
                break;

            case 'earned':
                $balance->earned_days = bcadd(
                    (string) $balance->earned_days,
                    $days,
                    2
                );
                break;

            case 'adjustment':
                $balance->adjustment_days = bcadd(
                    (string) $balance->adjustment_days,
                    $days,
                    2
                );
                break;

            case 'usage':
                /*
                 * Ledger usage is negative, while used_days is a
                 * positive aggregate.
                 */
                $balance->used_days = bcadd(
                    (string) $balance->used_days,
                    ltrim($days, '-'),
                    2
                );
                break;

            case 'usage_reversal':
                /*
                 * Reversal restores balance and reduces used_days.
                 */
                $balance->used_days = bcsub(
                    (string) $balance->used_days,
                    $days,
                    2
                );
                break;

            default:
                throw ValidationException::withMessages([
                    'transaction_type' =>
                        'Unsupported leave credit transaction type.',
                ]);
        }

        $balance->save();
    }
}