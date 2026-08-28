<?php

namespace App\Support\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class OpsDutyPresenter
{
    /**
     * @return list<array{slug: string, name: string, short: string, tone: string, icon: string|null, href: string|null, description: string|null}>
     */
    public static function badges(User $user): array
    {
        return collect($user->assignedDuties())
            ->map(fn (array $duty) => self::badge($duty))
            ->values()
            ->all();
    }

    /**
     * @param  array{slug?: string, name?: string, icon?: string|null, description?: string|null}  $duty
     * @return array{slug: string, name: string, short: string, tone: string, icon: string|null, href: string|null, description: string|null}
     */
    public static function badge(array $duty): array
    {
        $slug = (string) ($duty['slug'] ?? '');
        $name = (string) ($duty['name'] ?? Str::headline(str_replace('_', ' ', $slug)));
        $meta = config('admin.duty_badges.'.$slug, []);
        $routeName = (string) ($meta['route'] ?? '');

        $href = null;
        if ($routeName !== '' && Route::has($routeName)) {
            try {
                $href = route($routeName);
            } catch (\Throwable) {
                $href = null;
            }
        }

        return [
            'slug' => $slug,
            'name' => $name,
            'short' => (string) ($meta['short'] ?? self::guessShort($name)),
            'tone' => (string) ($meta['tone'] ?? 'default'),
            'icon' => $duty['icon'] ?? null,
            'href' => $href,
            'description' => $duty['description'] ?? null,
        ];
    }

    public static function guessShort(string $name): string
    {
        $name = trim($name);

        if ($name === '') {
            return 'Staff';
        }

        if (str_contains($name, '/')) {
            return trim(Str::before($name, '/'));
        }

        $parts = preg_split('/\s+/', $name) ?: [];

        return $parts[0] ?? $name;
    }

    /**
     * @param  list<array{slug: string, name: string, short: string, tone: string, icon: string|null}>  $badges
     */
    public static function summary(array $badges): string
    {
        $labels = collect($badges)->pluck('short')->filter()->values();

        if ($labels->isEmpty()) {
            return 'Operations';
        }

        return $labels->join(' · ');
    }
}
