<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditService
{
    private const REDACTED_KEYS = [
        'password',
        'password_confirmation',
        'remember_token',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'secret',
        'authorization',
    ];

    private const MASKED_KEYS = [
        'account_number',
        'bank_account_number',
        'member_number',
        'government_id_number',
        'id_number',
        'sss_number',
        'philhealth_number',
        'pagibig_number',
        'tin',
    ];

    public function log(
        string $module,
        string $action,
        string $auditableType,
        ?int $auditableId = null,
        ?User $actor = null,
        ?string $description = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $requestId = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $actor?->id,

            'action' => $action,

            'module' => $module,

            'auditable_type' => $auditableType,

            'auditable_id' => $auditableId,

            'description' => $description,

            'old_values' =>
                $oldValues === []
                    ? null
                    : $this->sanitize($oldValues),

            'new_values' =>
                $newValues === []
                    ? null
                    : $this->sanitize($newValues),

            'ip_address' =>
                $this->ipAddress(),

            'user_agent' =>
                $this->userAgent(),

            'request_id' =>
                $requestId ?? $this->requestId(),

            'occurred_at' => now(),
        ]);
    }

    public function logModel(
        string $module,
        string $action,
        string $auditableType,
        Model $model,
        ?User $actor = null,
        ?string $description = null,
        array $oldValues = [],
        array $newValues = [],
        ?string $requestId = null
    ): AuditLog {
        return $this->log(
            module: $module,
            action: $action,
            auditableType: $auditableType,
            auditableId: (int) $model->getKey(),
            actor: $actor,
            description: $description,
            oldValues: $oldValues,
            newValues: $newValues,
            requestId: $requestId,
        );
    }

    private function sanitize(array $values): array
    {
        $sanitized = [];

        foreach ($values as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if ($this->shouldRedact($normalizedKey)) {
                $sanitized[$key] = '[REDACTED]';

                continue;
            }

            if ($this->shouldMask($normalizedKey)) {
                $sanitized[$key] = $this->mask($value);

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value);

                continue;
            }

            if (is_object($value)) {
                $sanitized[$key] = '[' . $value::class . ']';

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    private function shouldRedact(string $key): bool
    {
        foreach (self::REDACTED_KEYS as $sensitiveKey) {
            if (
                $key === $sensitiveKey
                || str_ends_with(
                    $key,
                    '_' . $sensitiveKey
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function shouldMask(string $key): bool
    {
        return in_array(
            $key,
            self::MASKED_KEYS,
            true
        );
    }

    private function mask(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        if ($value === '') {
            return '';
        }

        $length = strlen($value);

        if ($length <= 4) {
            return str_repeat('*', $length);
        }

        return str_repeat(
            '*',
            $length - 4
        ) . substr($value, -4);
    }

    private function ipAddress(): ?string
    {
        if (! app()->runningInConsole()) {
            return request()->ip();
        }

        return null;
    }

    private function userAgent(): ?string
    {
        if (! app()->runningInConsole()) {
            return request()->userAgent();
        }

        return null;
    }

    private function requestId(): string
    {
        if (
            ! app()->runningInConsole()
            && request()->headers->has('X-Request-ID')
        ) {
            return (string) request()->header(
                'X-Request-ID'
            );
        }

        return (string) Str::uuid();
    }
}