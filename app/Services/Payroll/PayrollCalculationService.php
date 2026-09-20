<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\EmployeeCompensation;
use App\Models\EmployeeRecurringDeduction;
use App\Models\EmployeeRecurringEarning;
use App\Models\OvertimeRequest;
use App\Models\PayrollDeduction;
use App\Models\PayrollEarning;
use App\Models\PayrollEmployeeResult;
use App\Models\PayrollRun;
use App\Services\Payroll\GovernmentContributionService;
use App\Services\Payroll\WithholdingTaxService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollCalculationService
{
    public function __construct(
        private readonly GovernmentContributionService $governmentContributions,
        private readonly WithholdingTaxService $withholdingTax,
    ) {
    }

    public function calculateEmployee(
        PayrollRun $run,
        Employee $employee
    ): PayrollEmployeeResult {
        return DB::transaction(function () use ($run, $employee) {
            $run = PayrollRun::query()
                ->with('period')
                ->lockForUpdate()
                ->findOrFail($run->id);

            if ($run->status !== 'processing') {
                throw ValidationException::withMessages([
                    'payroll_run' =>
                        'Payroll calculations require a processing payroll run.',
                ]);
            }

            $employee = Employee::query()
                ->findOrFail($employee->id);

            $period = $run->period;

            /*
             * Recalculation is allowed before finalization.
             * Existing employee result is rebuilt.
             */
            $existing = PayrollEmployeeResult::query()
                ->where('payroll_run_id', $run->id)
                ->where('employee_id', $employee->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->status === 'finalized') {
                    throw ValidationException::withMessages([
                        'payroll_result' =>
                            'A finalized payroll result cannot be recalculated.',
                    ]);
                }

                /*
                 * Child detail rows should cascade according to
                 * our existing schema.
                 */
                $existing->delete();
            }

            $compensation = $this->resolveCompensation(
                $employee,
                Carbon::parse($period->period_end)
            );

            $result = PayrollEmployeeResult::create([
                'payroll_run_id' => $run->id,
                'employee_id' => $employee->id,

                /*
                 * Historical snapshots.
                 */
                'employee_number' => $employee->employee_number,
                'employee_name' => $this->employeeName($employee),

                'pay_type' => $compensation->pay_type,
                'basic_rate' => $compensation->basic_rate,

                'basic_pay' => 0,
                'total_earnings' => 0,
                'gross_pay' => 0,

                'total_deductions' => 0,
                'net_pay' => 0,

                'sss_employee' => 0,
                'philhealth_employee' => 0,
                'pagibig_employee' => 0,
                'withholding_tax' => 0,

                'status' => 'draft',
            ]);

            /*
             * 1. Basic pay
             */
            $basicPay = $this->calculateBasicPay(
                $employee,
                $compensation,
                $run
            );

            $result->basic_pay = $basicPay;

            /*
             * Optional BASIC_PAY detail for payslip presentation.
             *
             * It will NOT be included in total_earnings.
             */
            PayrollEarning::create([
                'payroll_employee_result_id' => $result->id,

                'code' => 'BASIC_PAY',
                'name' => 'Basic Pay',
                'category' => 'basic',

                'amount' => $basicPay,

                'is_taxable' => true,
                'is_contribution_basis' => true,
            ]);

            /*
             * 2. Other earnings
             */
            $this->addRecurringEarnings(
                $result,
                $employee,
                $run
            );

            $this->addApprovedOvertime(
                $result,
                $employee,
                $run
            );

            /*
             * BASIC_PAY must be excluded here.
             */
            $totalEarnings = PayrollEarning::query()
                ->where(
                    'payroll_employee_result_id',
                    $result->id
                )
                ->where('code', '!=', 'BASIC_PAY')
                ->sum('amount');

            $grossPay = bcadd(
                (string) $basicPay,
                (string) $totalEarnings,
                2
            );

            $result->total_earnings = $totalEarnings;
            $result->gross_pay = $grossPay;
            $result->save();

            /*
             * 3. Government contributions
             */
            $government = $this->governmentContributions
                ->calculateForResult($result);

            /*
             * Employee share only goes into deductions.
             */
            $result->sss_employee =
                $government['SSS']['employee_share'] ?? 0;

            $result->philhealth_employee =
                $government['PHILHEALTH']['employee_share'] ?? 0;

            $result->pagibig_employee =
                $government['PAGIBIG']['employee_share'] ?? 0;

            /*
             * 4. Recurring deductions
             */
            $this->addRecurringDeductions(
                $result,
                $employee,
                $run
            );

            /*
             * 5. Withholding tax
             *
             * Exact taxable basis is resolved by the tax service,
             * not by blindly using gross pay.
             */
            $tax = $this->withholdingTax
                ->calculateForResult($result);

            $result->withholding_tax = $tax;

            /*
             * Government contributions and tax can also have
             * PayrollDeduction detail rows for payslip presentation.
             *
             * total_deductions is calculated from detail rows ONCE.
             */
            $this->createStatutoryDeductionDetails(
                $result
            );

            $totalDeductions = PayrollDeduction::query()
                ->where(
                    'payroll_employee_result_id',
                    $result->id
                )
                ->sum('amount');

            $netPay = bcsub(
                (string) $grossPay,
                (string) $totalDeductions,
                2
            );

            $result->total_deductions = $totalDeductions;
            $result->net_pay = $netPay;
            $result->status = 'calculated';
            $result->save();

            return $result->refresh();
        });
    }

    private function resolveCompensation(
        Employee $employee,
        Carbon $date
    ): EmployeeCompensation {
        $compensation = EmployeeCompensation::query()
            ->where('employee_id', $employee->id)
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query
                    ->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $date);
            })
            ->orderByDesc('effective_from')
            ->first();

        if (! $compensation) {
            throw ValidationException::withMessages([
                'compensation' =>
                    'No applicable employee compensation was found for the payroll period.',
            ]);
        }

        return $compensation;
    }

    private function calculateBasicPay(
        Employee $employee,
        EmployeeCompensation $compensation,
        PayrollRun $run
    ): string {
        /*
         * IMPORTANT:
         *
         * This is intentionally a policy boundary.
         *
         * Monthly / daily / hourly computation depends on the
         * company's payroll rules and attendance treatment.
         *
         * Do not simply assume:
         *
         * monthly salary / 2
         *
         * for every semi-monthly payroll.
         */

        if ($compensation->pay_type === 'monthly') {
            return $this->calculateMonthlyBasicPay(
                $employee,
                $compensation,
                $run
            );
        }

        if ($compensation->pay_type === 'daily') {
            return $this->calculateDailyBasicPay(
                $employee,
                $compensation,
                $run
            );
        }

        if ($compensation->pay_type === 'hourly') {
            return $this->calculateHourlyBasicPay(
                $employee,
                $compensation,
                $run
            );
        }

        throw ValidationException::withMessages([
            'pay_type' =>
                'Unsupported compensation pay type.',
        ]);
    }

    private function calculateMonthlyBasicPay(
        Employee $employee,
        EmployeeCompensation $compensation,
        PayrollRun $run
    ): string {
        /*
         * Placeholder policy boundary.
         *
         * Exact semi-monthly/monthly proration must come from the
         * company's payroll policy.
         */
        throw ValidationException::withMessages([
            'payroll_policy' =>
                'Monthly basic-pay policy has not yet been configured.',
        ]);
    }

    private function calculateDailyBasicPay(
        Employee $employee,
        EmployeeCompensation $compensation,
        PayrollRun $run
    ): string {
        /*
         * Later:
         * payable days × daily rate,
         * using attendance/leave/holiday policy.
         */
        throw ValidationException::withMessages([
            'payroll_policy' =>
                'Daily basic-pay policy has not yet been configured.',
        ]);
    }

    private function calculateHourlyBasicPay(
        Employee $employee,
        EmployeeCompensation $compensation,
        PayrollRun $run
    ): string {
        /*
         * Later:
         * payable hours × hourly rate.
         */
        throw ValidationException::withMessages([
            'payroll_policy' =>
                'Hourly basic-pay policy has not yet been configured.',
        ]);
    }

    private function addRecurringEarnings(
        PayrollEmployeeResult $result,
        Employee $employee,
        PayrollRun $run
    ): void {
        $period = $run->period;

        $earnings = EmployeeRecurringEarning::query()
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->whereDate(
                'effective_from',
                '<=',
                $period->period_end
            )
            ->where(function ($query) use ($period) {
                $query
                    ->whereNull('effective_to')
                    ->orWhereDate(
                        'effective_to',
                        '>=',
                        $period->period_start
                    );
            })
            ->get();

        foreach ($earnings as $earning) {
            $amount = $this->recurringAmountForRun(
                $earning->amount,
                $earning->frequency,
                $run
            );

            if (bccomp((string) $amount, '0.00', 2) <= 0) {
                continue;
            }

            PayrollEarning::create([
                'payroll_employee_result_id' => $result->id,

                'code' => $earning->code,
                'name' => $earning->name,
                'category' => $earning->category,

                'amount' => $amount,

                'is_taxable' => $earning->is_taxable,
                'is_contribution_basis' =>
                    $earning->is_contribution_basis,

                'source_type' => 'recurring_earning',
                'source_id' => $earning->id,
            ]);
        }
    }

    private function addApprovedOvertime(
        PayrollEmployeeResult $result,
        Employee $employee,
        PayrollRun $run
    ): void {
        $period = $run->period;

        $overtimes = OvertimeRequest::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereBetween('overtime_date', [
                $period->attendance_cutoff_start,
                $period->attendance_cutoff_end,
            ])
            ->get();

        foreach ($overtimes as $overtime) {
            /*
             * IMPORTANT:
             *
             * approved_minutes alone does not tell us the peso amount.
             *
             * OT rate depends on:
             * - employee rate
             * - ordinary/rest/holiday day
             * - applicable multiplier
             * - company/statutory policy
             *
             * That calculation will be centralized instead of
             * guessed here.
             */
        }
    }

    private function addRecurringDeductions(
        PayrollEmployeeResult $result,
        Employee $employee,
        PayrollRun $run
    ): void {
        $period = $run->period;

        $deductions = EmployeeRecurringDeduction::query()
            ->where('employee_id', $employee->id)
            ->where('is_active', true)
            ->whereDate(
                'effective_from',
                '<=',
                $period->period_end
            )
            ->where(function ($query) use ($period) {
                $query
                    ->whereNull('effective_to')
                    ->orWhereDate(
                        'effective_to',
                        '>=',
                        $period->period_start
                    );
            })
            ->get();

        foreach ($deductions as $deduction) {
            $amount = $this->recurringAmountForRun(
                $deduction->amount,
                $deduction->frequency,
                $run
            );

            /*
             * Balance-based deduction:
             * final installment cannot exceed remaining balance.
             */
            if ($deduction->remaining_balance !== null) {
                if (
                    bccomp(
                        (string) $deduction->remaining_balance,
                        '0.00',
                        2
                    ) <= 0
                ) {
                    continue;
                }

                if (
                    bccomp(
                        (string) $amount,
                        (string) $deduction->remaining_balance,
                        2
                    ) > 0
                ) {
                    $amount = $deduction->remaining_balance;
                }
            }

            if (bccomp((string) $amount, '0.00', 2) <= 0) {
                continue;
            }

            PayrollDeduction::create([
                'payroll_employee_result_id' => $result->id,

                'code' => $deduction->code,
                'name' => $deduction->name,
                'category' => $deduction->category,

                'amount' => $amount,

                'source_type' => 'recurring_deduction',
                'source_id' => $deduction->id,
            ]);

            /*
             * DO NOT decrement remaining_balance here.
             *
             * This payroll result may still be recalculated,
             * cancelled or fail.
             */
        }
    }

    private function recurringAmountForRun(
        string $amount,
        string $frequency,
        PayrollRun $run
    ): string {
        if ($frequency === 'per_payroll') {
            return $amount;
        }

        if ($frequency === 'monthly') {
            /*
             * Company-specific allocation policy.
             *
             * Never silently assume amount / 2.
             */
            throw ValidationException::withMessages([
                'frequency' =>
                    'Monthly recurring-item allocation policy has not yet been configured.',
            ]);
        }

        throw ValidationException::withMessages([
            'frequency' =>
                'Unsupported recurring payroll frequency.',
        ]);
    }

    private function createStatutoryDeductionDetails(
        PayrollEmployeeResult $result
    ): void {
        $items = [
            [
                'code' => 'SSS',
                'name' => 'SSS',
                'amount' => $result->sss_employee,
            ],
            [
                'code' => 'PHILHEALTH',
                'name' => 'PhilHealth',
                'amount' => $result->philhealth_employee,
            ],
            [
                'code' => 'PAGIBIG',
                'name' => 'Pag-IBIG',
                'amount' => $result->pagibig_employee,
            ],
            [
                'code' => 'WITHHOLDING_TAX',
                'name' => 'Withholding Tax',
                'amount' => $result->withholding_tax,
            ],
        ];

        foreach ($items as $item) {
            if (
                bccomp(
                    (string) $item['amount'],
                    '0.00',
                    2
                ) <= 0
            ) {
                continue;
            }

            PayrollDeduction::create([
                'payroll_employee_result_id' => $result->id,

                'code' => $item['code'],
                'name' => $item['name'],
                'category' => 'statutory',

                'amount' => $item['amount'],
            ]);
        }
    }

    private function employeeName(
        Employee $employee
    ): string {
        return trim(
            $employee->first_name
            . ' '
            . ($employee->middle_name
                ? $employee->middle_name . ' '
                : '')
            . $employee->last_name
        );
    }
}