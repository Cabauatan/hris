<?php

namespace App\Services\Approval;

use App\Models\ApprovalDelegation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalDelegationService
{
    public function create(
        User $delegator,
        User $delegate,
        User $createdBy,
        array $data
    ): ApprovalDelegation {
        return DB::transaction(function () use (
            $delegator,
            $delegate,
            $createdBy,
            $data
        ) {
            if ($delegator->id === $delegate->id) {
                throw ValidationException::withMessages([
                    'delegate_user_id' =>
                        'A user cannot delegate approval authority to themselves.',
                ]);
            }

            if (! $delegator->is_active || ! $delegate->is_active) {
                throw ValidationException::withMessages([
                    'delegate_user_id' =>
                        'Both delegator and delegate must be active users.',
                ]);
            }

            $startsAt = Carbon::parse($data['starts_at']);
            $endsAt = Carbon::parse($data['ends_at']);

            if ($endsAt->lt($startsAt)) {
                throw ValidationException::withMessages([
                    'ends_at' =>
                        'Delegation end must not be earlier than its start.',
                ]);
            }

            $this->ensureNoOverlap(
                $delegator,
                $delegate,
                $startsAt,
                $endsAt,
                $data['request_type'] ?? null
            );

            return ApprovalDelegation::create([
                'delegator_user_id' => $delegator->id,
                'delegate_user_id' => $delegate->id,

                'starts_at' => $startsAt,
                'ends_at' => $endsAt,

                'request_type' =>
                    $data['request_type'] ?? null,

                'is_active' => true,

                'remarks' => $data['remarks'] ?? null,

                'created_by_user_id' => $createdBy->id,
            ]);
        });
    }

    public function deactivate(
        ApprovalDelegation $delegation
    ): ApprovalDelegation {
        return DB::transaction(function () use ($delegation) {
            $delegation = ApprovalDelegation::query()
                ->lockForUpdate()
                ->findOrFail($delegation->id);

            if (! $delegation->is_active) {
                return $delegation;
            }

            $delegation->update([
                'is_active' => false,
            ]);

            return $delegation->refresh();
        });
    }

    public function activeDelegation(
        User $delegator,
        User $delegate,
        string $requestType,
        ?Carbon $at = null
    ): ?ApprovalDelegation {
        $at ??= now();

        return ApprovalDelegation::query()
            ->where('delegator_user_id', $delegator->id)
            ->where('delegate_user_id', $delegate->id)
            ->where('is_active', true)
            ->where('starts_at', '<=', $at)
            ->where('ends_at', '>=', $at)
            ->where(function ($query) use ($requestType) {
                $query
                    ->whereNull('request_type')
                    ->orWhere('request_type', $requestType);
            })
            ->first();
    }

    private function ensureNoOverlap(
        User $delegator,
        User $delegate,
        Carbon $startsAt,
        Carbon $endsAt,
        ?string $requestType
    ): void {
        $exists = ApprovalDelegation::query()
            ->where('delegator_user_id', $delegator->id)
            ->where('delegate_user_id', $delegate->id)
            ->where('is_active', true)
            ->where('starts_at', '<=', $endsAt)
            ->where('ends_at', '>=', $startsAt)
            ->when(
                $requestType !== null,
                fn ($query) => $query->where(function ($query) use (
                    $requestType
                ) {
                    $query
                        ->whereNull('request_type')
                        ->orWhere('request_type', $requestType);
                })
            )
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'delegation' =>
                    'An overlapping approval delegation already exists.',
            ]);
        }
    }
}