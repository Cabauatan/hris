<?php

namespace App\Services\Payroll;

use App\Models\PayrollEmployeeResult;
use App\Models\WithholdingTaxBracket;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithholdingTaxService
{
    public function calculateForResult(
        PayrollEmployeeResult $result
    ): string {
        return DB::transaction(function () use ($result) {
            $result = PayrollEmployeeResult::query()
                ->with([
                    'run.period',
                    'earnings',
                    'deductions',
                ])
                ->lockForUpdate()
                ->findOrFail($result->id);

            if ($result->status === 'finalized') {
                throw ValidationException::withMessages([
                    'payroll_result' =>
                        'Withholding tax cannot be recalculated for a finalized payroll result.',
                ]);
            }

            $taxableIncome = $this->taxableIncome($result);

            if (bccomp($taxableIncome, '0.00', 2) <= 0) {
                return '0.00';
            }

            $bracket = $this->resolveBracket(
                $result,
                $taxableIncome
            );

            if (! $bracket) {
                throw ValidationException::withMessages([
                    'withholding_tax' =>
                        'No applicable withholding tax bracket was found.',
                ]);
            }

            return $this->calculateTax(
                $taxableIncome,
                $bracket
            );
        });
    }

    private function taxableIncome(
        PayrollEmployeeResult $result
    ): string {
        /*
         * Build taxable earnings from payroll snapshots.
         *
         * BASIC_PAY may be included because it is represented
         * as a PayrollEarning detail with is_taxable = true.
         */
        $taxableEarnings = '0.00';

        foreach (
            $result->earnings
                ->where('is_taxable', true)
            as $earning
        ) {
            $taxableEarnings = bcadd(
                $taxableEarnings,
                (string) $earning->amount,
                2
            );
        }

        /*
         * Deductible statutory contributions must be determined
         * explicitly.
         *
         * Do not subtract every PayrollDeduction because items
         * such as loans or other deductions do not automatically
         * reduce taxable income.
         */
        $statutoryDeductions = '0.00';

        $statutoryDeductions = bcadd(
            $statutoryDeductions,
            (string) $result->sss_employee,
            2
        );

        $statutoryDeductions = bcadd(
            $statutoryDeductions,
            (string) $result->philhealth_employee,
            2
        );

        $statutoryDeductions = bcadd(
            $statutoryDeductions,
            (string) $result->pagibig_employee,
            2
        );

        $taxableIncome = bcsub(
            $taxableEarnings,
            $statutoryDeductions,
            2
        );

        if (bccomp($taxableIncome, '0.00', 2) < 0) {
            return '0.00';
        }

        return $taxableIncome;
    }

    private function resolveBracket(
        PayrollEmployeeResult $result,
        string $taxableIncome
    ): ?WithholdingTaxBracket {
        $period = $result->run->period;

        /*
         * Existing schema carries its own version grouping
         * through table_name.
         *
         * We resolve an effective bracket directly; there is
         * intentionally no WithholdingTaxSchedule model.
         */
        return WithholdingTaxBracket::query()
            ->where(
                'pay_frequency',
                $period->pay_frequency
            )
            ->where('is_active', true)
            ->whereDate(
                'effective_from',
                '<=',
                $period->pay_date
            )
            ->where(function ($query) use ($period) {
                $query
                    ->whereNull('effective_to')
                    ->orWhereDate(
                        'effective_to',
                        '>=',
                        $period->pay_date
                    );
            })
            ->where(
                'taxable_from',
                '<=',
                $taxableIncome
            )
            ->where(function ($query) use ($taxableIncome) {
                $query
                    ->whereNull('taxable_to')
                    ->orWhere(
                        'taxable_to',
                        '>=',
                        $taxableIncome
                    );
            })
            ->orderByDesc('effective_from')
            ->orderBy('sort_order')
            ->first();
    }

    private function calculateTax(
        string $taxableIncome,
        WithholdingTaxBracket $bracket
    ): string {
        /*
         * Formula:
         *
         * base_tax
         * +
         * max(0, taxable_income - excess_over)
         * × excess_rate
         */

        $excess = bcsub(
            $taxableIncome,
            (string) $bracket->excess_over,
            4
        );

        if (bccomp($excess, '0.0000', 4) < 0) {
            $excess = '0.0000';
        }

        $excessTax = bcmul(
            $excess,
            (string) $bracket->excess_rate,
            4
        );

        return bcadd(
            (string) $bracket->base_tax,
            $excessTax,
            2
        );
    }
}