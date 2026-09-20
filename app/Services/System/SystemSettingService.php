<?php

namespace App\Services\System;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SystemSettingService
{
    private const CACHE_PREFIX = 'system_setting:';

    private const TYPES = [
        'string',
        'integer',
        'decimal',
        'boolean',
        'json',
    ];

    public function __construct(
        private AuditService $audit
    ) {}

    public function all(?string $group = null): Collection
    {
        return SystemSetting::query()
            ->when(
                $group !== null,
                fn ($query) => $query->where('group', $group)
            )
            ->orderBy('group')
            ->orderBy('key')
            ->get();
    }

    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        return Cache::rememberForever(
            $this->cacheKey($key),
            function () use ($key, $default) {
                $setting = SystemSetting::query()
                    ->where('key', $key)
                    ->first();

                if (! $setting) {
                    return $default;
                }

                return $this->castValue(
                    $setting->value,
                    $setting->type
                );
            }
        );
    }

    public function require(string $key): mixed
    {
        $setting = SystemSetting::query()
            ->where('key', $key)
            ->first();

        if (! $setting) {
            throw ValidationException::withMessages([
                'setting' =>
                    "Required system setting [{$key}] is not configured.",
            ]);
        }

        return $this->castValue(
            $setting->value,
            $setting->type
        );
    }

    public function set(
        string $key,
        mixed $value,
        User $actor
    ): SystemSetting {
        return DB::transaction(function () use (
            $key,
            $value,
            $actor
        ) {
            $setting = SystemSetting::query()
                ->where('key', $key)
                ->lockForUpdate()
                ->first();

            if (! $setting) {
                throw ValidationException::withMessages([
                    'setting' =>
                        "Unknown system setting [{$key}].",
                ]);
            }

            $normalized = $this->normalizeValue(
                $value,
                $setting->type
            );

            /*
             * No actual change.
             */
            if ($setting->value === $normalized) {
                return $setting;
            }

            $oldValue = $setting->value;

            $setting->update([
                'value' => $normalized,
                'updated_by_user_id' => $actor->id,
            ]);

            /*
             * Audit is written inside the same transaction.
             */
            $this->audit->logModel(
                module: 'settings',
                action: 'updated',
                auditableType: 'system_setting',
                model: $setting,
                actor: $actor,
                description:
                    "Updated system setting [{$setting->key}].",
                oldValues: [
                    'value' => $oldValue,
                ],
                newValues: [
                    'value' => $setting->value,
                ],
            );

            /*
             * Cache must only be cleared after successful commit.
             */
            DB::afterCommit(function () use ($key) {
                Cache::forget(
                    $this->cacheKey($key)
                );
            });

            return $setting->refresh();
        });
    }

    public function setMany(
        array $values,
        User $actor
    ): Collection {
        $updatedKeys = [];

        $settings = DB::transaction(function () use (
            $values,
            $actor,
            &$updatedKeys
        ) {
            $results = collect();

            foreach ($values as $key => $value) {
                $setting = SystemSetting::query()
                    ->where('key', $key)
                    ->lockForUpdate()
                    ->first();

                if (! $setting) {
                    throw ValidationException::withMessages([
                        $key =>
                            "Unknown system setting [{$key}].",
                    ]);
                }

                $normalized = $this->normalizeValue(
                    $value,
                    $setting->type
                );

                /*
                 * Skip unchanged values.
                 */
                if ($setting->value === $normalized) {
                    $results->push($setting);

                    continue;
                }

                $oldValue = $setting->value;

                $setting->update([
                    'value' => $normalized,
                    'updated_by_user_id' => $actor->id,
                ]);

                $this->audit->logModel(
                    module: 'settings',
                    action: 'updated',
                    auditableType: 'system_setting',
                    model: $setting,
                    actor: $actor,
                    description:
                        "Updated system setting [{$setting->key}].",
                    oldValues: [
                        'value' => $oldValue,
                    ],
                    newValues: [
                        'value' => $setting->value,
                    ],
                );

                $updatedKeys[] = $key;

                $results->push(
                    $setting->refresh()
                );
            }

            return $results;
        });

        /*
         * DB::transaction() has successfully committed here.
         */
        foreach ($updatedKeys as $key) {
            Cache::forget(
                $this->cacheKey($key)
            );
        }

        return $settings;
    }

    public function valueExists(string $key): bool
    {
        return SystemSetting::query()
            ->where('key', $key)
            ->exists();
    }

    private function castValue(
        ?string $value,
        string $type
    ): mixed {
        return match ($type) {
            'string' => $value,

            'integer' =>
                $value === null
                    ? null
                    : (int) $value,

            /*
             * Decimal intentionally remains a string.
             */
            'decimal' => $value,

            'boolean' =>
                $this->castBoolean($value),

            'json' =>
                $value === null
                    ? null
                    : json_decode(
                        $value,
                        true,
                        512,
                        JSON_THROW_ON_ERROR
                    ),

            default =>
                throw ValidationException::withMessages([
                    'type' =>
                        "Unsupported system setting type [{$type}].",
                ]),
        };
    }

    private function normalizeValue(
        mixed $value,
        string $type
    ): ?string {
        if (
            ! in_array(
                $type,
                self::TYPES,
                true
            )
        ) {
            throw ValidationException::withMessages([
                'type' =>
                    "Unsupported system setting type [{$type}].",
            ]);
        }

        if ($value === null) {
            return null;
        }

        return match ($type) {
            'string' =>
                (string) $value,

            'integer' =>
                $this->normalizeInteger($value),

            'decimal' =>
                $this->normalizeDecimal($value),

            'boolean' =>
                $this->normalizeBoolean($value),

            'json' =>
                json_encode(
                    $value,
                    JSON_THROW_ON_ERROR
                ),
        };
    }

    private function normalizeInteger(
        mixed $value
    ): string {
        if (
            filter_var(
                $value,
                FILTER_VALIDATE_INT
            ) === false
        ) {
            throw ValidationException::withMessages([
                'value' =>
                    'System setting value must be an integer.',
            ]);
        }

        return (string) (int) $value;
    }

    private function normalizeDecimal(
        mixed $value
    ): string {
        if (! is_numeric($value)) {
            throw ValidationException::withMessages([
                'value' =>
                    'System setting value must be numeric.',
            ]);
        }

        /*
         * Keep decimal as a string.
         * Do not use float for monetary/rate settings.
         */
        return (string) $value;
    }

    private function normalizeBoolean(
        mixed $value
    ): string {
        $boolean = filter_var(
            $value,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );

        if ($boolean === null) {
            throw ValidationException::withMessages([
                'value' =>
                    'System setting value must be boolean.',
            ]);
        }

        return $boolean ? '1' : '0';
    }

    private function castBoolean(
        ?string $value
    ): ?bool {
        if ($value === null) {
            return null;
        }

        return match ($value) {
            '1' => true,
            '0' => false,

            default =>
                throw ValidationException::withMessages([
                    'value' =>
                        'Stored boolean system setting is invalid.',
                ]),
        };
    }

    private function cacheKey(
        string $key
    ): string {
        return self::CACHE_PREFIX . $key;
    }
}