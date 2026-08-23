<?php

namespace App\Support\Staff;

use Illuminate\Support\Str;

class AdminPermissions
{
    public const ACCESS = 'admin.access';

    public const SUPER_KEY = 'super_admin';

    /**
     * @return list<array{id: string, label: string, items: list<array{key: string, label: string}>}>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (config('admin.permissions', []) as $id => $group) {
            $items = [];

            foreach ($group['items'] ?? [] as $key => $label) {
                $items[] = [
                    'key' => $key,
                    'label' => $label,
                ];
            }

            $groups[] = [
                'id' => (string) $id,
                'label' => $group['label'] ?? Str::headline((string) $id),
                'items' => $items,
            ];
        }

        return $groups;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        $keys = [];

        foreach (config('admin.permissions', []) as $group) {
            foreach (array_keys($group['items'] ?? []) as $key) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * @param  list<string>|null  $selected
     * @return list<string>
     */
    public static function sanitize(?array $selected): array
    {
        $allowed = array_flip(self::keys());

        return collect($selected ?? [])
            ->map(fn ($key) => (string) $key)
            ->filter(fn (string $key) => isset($allowed[$key]))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function forSystemSlug(string $slug): array
    {
        foreach (config('admin.roles', []) as $role) {
            if (($role['slug'] ?? '') === $slug) {
                return self::sanitize($role['permissions'] ?? []);
            }
        }

        return [];
    }

    public static function ttlHours(): int
    {
        return max(1, (int) config('admin.invite.ttl_hours', 48));
    }
}
