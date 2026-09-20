<?php

namespace App\Services\Payroll;

use App\Models\GovernmentContributionBracket;
use App\Models\GovernmentContributionSchedule;
use App\Models\PayrollEmployeeResult;
use App\Models\PayrollGovernmentContribution;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class GovernmentContributionService
{
    private const AGENCIES = [
        'SSS',
        'PHILHEALTH',
        'PAGIBIG',
    ];

    /**
     * Calculate and snapshot all government contributions
     * applicable to one payroll result.
     */
    public function calculateForResult(
        PayrollEmployeeResult $result
    ): array {
        return DB::transaction(function () use ($result) {
            $result = PayrollEmployeeResult::query()
                ->with([
                    'run.period',
                    'employee',
                ])
                ->lockForUpdate()
                ->findOrFail($result->id);

            if ($result->status === 'finalized') {
                throw ValidationException::withMessages([
                    'payroll_result' =>
                        'Finalized payroll contributions cannot be recalculated.',
                ]);
            }

            /*
             * Recalculation before finalization:
             * rebuild statutory contribution snapshots.
             */
            PayrollGovernmentContribution::query()
                ->where(
                    'payroll_employee_result_id',
                    $result->id
                )
                ->delete();

            $output = [];

            foreach (self::AGENCIES as $agency) {
                $output[$agency] =
                    $this->calculateAgency(
                        $result,
                        $agency
                    );
            }

            return $output;
        });
    }

    private function calculateAgency(
        PayrollEmployeeResult $result,
        string $agency
    ): array {
        $period = $result->run->period;

        /*
         * Pay date is a good effective-date anchor for statutory
         * schedule selection in this architecture.
         *
         * If company policy later defines another anchor, centralize
         * that decision here.
         */
        $effectiveDate = $period->pay_date;

        $schedule = GovernmentContributionSchedule::query()
            ->where('agency', $agency)
            ->where('is_active', true)
            ->whereDate(
                'effective_from',
                '<=',
                $effectiveDate
            )
            ->where(function ($query) use ($effectiveDate) {
                $query
                    ->whereNull('effective_to')
                    ->orWhereDate(
                        'effective_to',
                        '>=',
                        $effectiveDate
                    );
            })
            ->orderByDesc('effective_from')
            ->first();

        if (! $schedule) {
            throw ValidationException::withMessages([
                'government_contribution' =>
                    "No active {$agency} contribution schedule applies to this payroll.",
            ]);
        }

        $basis = $this->contributionBasis(
            $result,
            $agency
        );

        $calculation = match ($schedule->method) {
            'bracket' =>
                $this->calculateBracket(
                    $schedule,
                    $basis
                ),

            'percentage' =>
                $this->calculatePercentage(
                    $schedule,
                    $basis
                ),

            default =>
                throw ValidationException::withMessages([
                    'government_contribution' =>
                        "Unsupported {$agency} contribution method.",
                ]),
        };

        $memberNumber =
            $this->memberNumber(
                $result,
                $agency
            );

        $snapshot = PayrollGovernmentContribution::create([
            'payroll_employee_result_id' => $result->id,

            'government_contribution_schedule_id' =>
                $schedule->id,

            'government_contribution_bracket_id' =>
                $calculation['bracket_id'],

            'agency' => $agency,

            /*
             * Sensitive historical snapshot.
             */
            'member_number' => $memberNumber,

            'contribution_basis' => $basis,

            'employee_share' =>
                $calculation['employee_share'],

            'employer_share' =>
                $calculation['employer_share'],

            'total_contribution' => bcadd(
                (string) $calculation['employee_share'],
                (string) $calculation['employer_share'],
                2
            ),

            'employee_rate' =>
                $calculation['employee_rate'],

            'employer_rate' =>
                $calculation['employer_rate'],

            'status' => 'calculated',
        ]);

        return [
            'id' => $snapshot->id,

            'employee_share' =>
                $snapshot->employee_share,

            'employer_share' =>
                $snapshot->employer_share,

            'total_contribution' =>
                $snapshot->total_contribution,
        ];
    }

    private function contributionBasis(
        PayrollEmployeeResult $result,
        string $agency
    ): string {
        /*
         * V1 boundary:
         *
         * Do NOT automatically assume gross_pay is the statutory
         * contribution basis for every agency.
         *
         * PayrollEarning already carries
         * is_contribution_basis, allowing the basis to be built
         * from eligible earnings.
         */

        $earnings = $result->earnings()
            ->where('is_contribution_basis', true)
            ->get();

        $basis = '0.00';

        foreach ($earnings as $earning) {
            $basis = bcadd(
                $basis,
                (string) $earning->amount,
                2
            );
        }

        return $basis;
    }

    private function calculateBracket(
        GovernmentContributionSchedule $schedule,
        string $basis
    ): array {
        $bracket = GovernmentContributionBracket::query()
            ->where(
                'government_contribution_schedule_id',
                $schedule->id
            )
            ->where('is_active', true)
            ->where(
                'range_from',
                '<=',
                $basis
            )
            ->where(function ($query) use ($basis) {
                $query
                    ->whereNull('range_to')
                    ->orWhere(
                        'range_to',
                        '>=',
                        $basis
                    );
            })
            ->orderBy('sort_order')
            ->first();

        if (! $bracket) {
            throw ValidationException::withMessages([
                'government_contribution' =>
                    "No contribution bracket applies for {$schedule->agency}.",
            ]);
        }

        /*
         * Rates are stored as decimal fractions.
         *
         * Example:
         * 0.045 = 4.5%
         */
        $employeeVariable = bcmul(
            (string) $basis,
            (string) ($bracket->employee_rate ?? 0),
            4
        );

        $employerVariable = bcmul(
            (string) $basis,
            (string) ($bracket->employer_rate ?? 0),
            4
        );

        $employeeShare = bcadd(
            (string) ($bracket->employee_fixed ?? 0),
            $employeeVariable,
            2
        );

        $employerShare = bcadd(
            (string) ($bracket->employer_fixed ?? 0),
            $employerVariable,
            2
        );

        $employeeShare = $this->applyLimits(
            $employeeShare,
            $bracket->employee_min ?? null,
            $bracket->employee_max ?? null
        );

        $employerShare = $this->applyLimits(
            $employerShare,
            $bracket->employer_min ?? null,
            $bracket->employer_max ?? null
        );

        return [
            'bracket_id' => $bracket->id,

            'employee_share' => $employeeShare,
            'employer_share' => $employerShare,

            'employee_rate' =>
                $bracket->employee_rate ?? null,

            'employer_rate' =>
                $bracket->employer_rate ?? null,
        ];
    }

    private function calculatePercentage(
        GovernmentContributionSchedule $schedule,
        string $basis
    ): array {
        /*
         * Exact percentage fields must come from the audited
         * schedule schema.
         *
         * Do not invent them here if the existing migration stores
         * percentage configuration differently.
         */
        $employeeRate =
            $schedule->employee_rate ?? null;

        $employerRate =
            $schedule->employer_rate ?? null;

        if (
            $employeeRate === null
            || $employerRate === null
        ) {
            throw ValidationException::withMessages([
                'government_contribution' =>
                    "Percentage rates are incomplete for {$schedule->agency}.",
            ]);
        }

        $employeeShare = bcmul(
            $basis,
            (string) $employeeRate,
            2
        );

        $employerShare = bcmul(
            $basis,
            (string) $employerRate,
            2
        );

        return [
            'bracket_id' => null,

            'employee_share' => $employeeShare,
            'employer_share' => $employerShare,

            'employee_rate' => $employeeRate,
            'employer_rate' => $employerRate,
        ];
    }

    private function applyLimits(
        string $amount,
        mixed $minimum,
        mixed $maximum
    ): string {
        if (
            $minimum !== null
            && bccomp(
                $amount,
                (string) $minimum,
                2
            ) < 0
        ) {
            $amount = (string) $minimum;
        }

        if (
            $maximum !== null
            && bccomp(
                $amount,
                (string) $maximum,
                2
            ) > 0
        ) {
            $amount = (string) $maximum;
        }

        return $amount;
    }

    private function memberNumber(
        PayrollEmployeeResult $result,
        string $agency
    ): ?string {
        /*
         * Adapt this query to the exact audited
         * EmployeeGovernmentId columns.
         */
        $governmentId = $result->employee
            ->governmentIds()
            ->where('id_type', $agency)
            ->first();

        return $governmentId?->id_number;
    }
}