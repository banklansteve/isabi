<?php

namespace App\Support\Staff;

use App\Models\AppSetting;
use App\Models\User;
use App\Support\Auth\SessionLifetime;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AppSettingsService
{
    public const CACHE_KEY = 'isabi.app_settings';

    /**
     * @return list<array<string, mixed>>
     */
    public function allForAdmin(): array
    {
        $stored = AppSetting::query()->get()->keyBy('key');
        $rows = [];

        foreach (config('admin.settings', []) as $definition) {
            $setting = $stored->get($definition['key']);

            $rows[] = [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'group' => $definition['group'],
                'type' => $definition['type'],
                'description' => $definition['description'] ?? null,
                'config_key' => $definition['config'] ?? null,
                'is_custom' => false,
                'value' => $setting
                    ? $setting->typedValue()
                    : $this->currentConfigValue($definition),
            ];
        }

        foreach ($stored->where('is_custom', true) as $custom) {
            $rows[] = $custom->toAdminArray();
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function updateMany(array $values, User $updatedBy): void
    {
        $definitions = collect(config('admin.settings', []))->keyBy('key');

        foreach ($values as $key => $value) {
            $this->put((string) $key, $value, $updatedBy, $definitions->get($key));
        }

        $this->forgetCache();
        $this->applyOverrides();
    }

    public function put(string $key, mixed $value, User $updatedBy, ?array $definition = null): AppSetting
    {
        $definition ??= collect(config('admin.settings', []))->firstWhere('key', $key);

        $type = $definition['type'] ?? (is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string'));
        $stored = $this->normalizeForStorage($value, $type);

        $setting = AppSetting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $stored,
                'type' => $type,
                'group' => $definition['group'] ?? 'custom',
                'label' => $definition['label'] ?? $key,
                'description' => $definition['description'] ?? null,
                'config_key' => $definition['config'] ?? null,
                'is_custom' => $definition === null,
                'updated_by_user_id' => $updatedBy->id,
            ],
        );

        return $setting;
    }

    public function applyOverrides(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        foreach ($this->cached() as $setting) {
            $configKey = $setting->config_key ?: $setting->key;

            if (! is_string($configKey) || $configKey === '') {
                continue;
            }

            config([$configKey => $setting->typedValue()]);
        }

        app(SessionLifetime::class)->primeHandlerLifetime();
    }

    /**
     * @return Collection<int, AppSetting>
     */
    public function cached()
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return AppSetting::query()->get();
        });
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function currentConfigValue(array $definition): mixed
    {
        $configKey = $definition['config'] ?? $definition['key'];

        return config($configKey);
    }

    private function normalizeForStorage(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0',
            'integer' => (string) (int) $value,
            'json' => is_string($value) ? $value : json_encode($value),
            default => (string) $value,
        };
    }

    public function assertKnownOrCustom(string $key): void
    {
        $known = collect(config('admin.settings', []))->contains(fn (array $row) => $row['key'] === $key);

        if (! $known && ! AppSetting::query()->where('key', $key)->where('is_custom', true)->exists()) {
            throw ValidationException::withMessages([
                'settings' => "Unknown setting [{$key}].",
            ]);
        }
    }
}
