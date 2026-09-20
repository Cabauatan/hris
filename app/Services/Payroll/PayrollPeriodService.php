<?php

namespace App\Services\Payroll;

use App\Models\PayrollPeriod;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollPeriodService
{
    public function paginate(
        int $perPage = 15,
        ?string $status = null,
        ?string $search = null
    ): LengthAwarePaginator {
        return PayrollPeriod::query()
            ->when(
                $status !== null,
                fn ($query) => $query->where('status', $status)
            )
            ->when(
                $search !== null && trim($search) !== '',
                fn ($query) => $query->where(function ($query) use ($search) {
                    $query
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                })
            )
            ->orderByDesc('period_start')
            ->paginate($perPage);
    }

    public function create(array $data): PayrollPeriod
    {
        return DB::transaction(function () use ($data) {
            $this->validateDates($data);

            return PayrollPeriod::create([
                'code' => $data['code'],
                'name' => $data['name'],

                'period_start' =>
                    Carbon::parse($data['period_start'])->toDateString(),

                'period_end' =>
                    Carbon::parse($data['period_end'])->toDateString(),

                'attendance_cutoff_start' =>
                    Carbon::parse(
                        $data['attendance_cutoff_start']
                    )->toDateString(),

                'attendance_cutoff_end' =>
                    Carbon::parse(
                        $data['attendance_cutoff_end']
                    )->toDateString(),

                'pay_date' =>
                    Carbon::parse($data['pay_date'])->toDateString(),

                'pay_frequency' =>
                    $data['pay_frequency'] ?? 'semi_monthly',

                'status' => 'draft',

                'remarks' => $data['remarks'] ?? null,
            ]);
        });
    }

    public function update(
        PayrollPeriod $period,
        array $data
    ): PayrollPeriod {
        return DB::transaction(function () use ($period, $data) {
            $period = PayrollPeriod::query()
                ->lockForUpdate()
                ->findOrFail($period->id);

            $this->ensureEditable($period);

            $values = [
                'code' => $data['code'] ?? $period->code,
                'name' => $data['name'] ?? $period->name,

                'period_start' =>
                    $data['period_start'] ?? $period->period_start,

                'period_end' =>
                    $data['period_end'] ?? $period->period_end,

                'attendance_cutoff_start' =>
                    $data['attendance_cutoff_start']
                    ?? $period->attendance_cutoff_start,

                'attendance_cutoff_end' =>
                    $data['attendance_cutoff_end']
                    ?? $period->attendance_cutoff_end,

                'pay_date' =>
                    $data['pay_date'] ?? $period->pay_date,

                'pay_frequency' =>
                    $data['pay_frequency'] ?? $period->pay_frequency,

                'remarks' =>
                    array_key_exists('remarks', $data)
                        ? $data['remarks']
                        : $period->remarks,
            ];

            $this->validateDates($values);

            $period->update($values);

            return $period->refresh();
        });
    }

    public function open(
        PayrollPeriod $period
    ): PayrollPeriod {
        return $this->transition(
            $period,
            ['draft'],
            'open'
        );
    }

    public function markProcessing(
        PayrollPeriod $period
    ): PayrollPeriod {
        return $this->transition(
            $period,
            ['open'],
            'processing'
        );
    }

    public function finalize(
        PayrollPeriod $period,
        User $actor
    ): PayrollPeriod {
        return DB::transaction(function () use ($period, $actor) {
            $period = PayrollPeriod::query()
                ->lockForUpdate()
                ->findOrFail($period->id);

            if ($period->status !== 'processing') {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only a processing payroll period may be finalized.',
                ]);
            }

            $period->update([
                'status' => 'finalized',
                'finalized_at' => now(),
                'finalized_by_user_id' => $actor->id,
            ]);

            return $period->refresh();
        });
    }

    public function lock(
        PayrollPeriod $period,
        User $actor
    ): PayrollPeriod {
        return DB::transaction(function () use ($period, $actor) {
            $period = PayrollPeriod::query()
                ->lockForUpdate()
                ->findOrFail($period->id);

            if ($period->status !== 'finalized') {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only a finalized payroll period may be locked.',
                ]);
            }

            $period->update([
                'status' => 'locked',
                'locked_at' => now(),
                'locked_by_user_id' => $actor->id,
            ]);

            return $period->refresh();
        });
    }

    private function transition(
        PayrollPeriod $period,
        array $allowedFrom,
        string $to
    ): PayrollPeriod {
        return DB::transaction(function () use (
            $period,
            $allowedFrom,
            $to
        ) {
            $period = PayrollPeriod::query()
                ->lockForUpdate()
                ->findOrFail($period->id);

            if (! in_array($period->status, $allowedFrom, true)) {
                throw ValidationException::withMessages([
                    'status' =>
                        "Payroll period cannot transition from {$period->status} to {$to}.",
                ]);
            }

            $period->update([
                'status' => $to,
            ]);

            return $period->refresh();
        });
    }

    private function ensureEditable(
        PayrollPeriod $period
    ): void {
        if ($period->status !== 'draft') {
            throw ValidationException::withMessages([
                'status' =>
                    'Only draft payroll periods may be edited.',
            ]);
        }
    }

    private function validateDates(array $data): void
    {
        $periodStart = Carbon::parse($data['period_start']);
        $periodEnd = Carbon::parse($data['period_end']);

        $cutoffStart = Carbon::parse(
            $data['attendance_cutoff_start']
        );

        $cutoffEnd = Carbon::parse(
            $data['attendance_cutoff_end']
        );

        if ($periodEnd->lt($periodStart)) {
            throw ValidationException::withMessages([
                'period_end' =>
                    'Payroll period end cannot be earlier than its start.',
            ]);
        }

        if ($cutoffEnd->lt($cutoffStart)) {
            throw ValidationException::withMessages([
                'attendance_cutoff_end' =>
                    'Attendance cutoff end cannot be earlier than its start.',
            ]);
        }
    }
}