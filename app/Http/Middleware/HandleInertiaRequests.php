<?php

namespace App\Http\Middleware;

use App\Support\CookieConsent;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user
                    ? [
                        'id' => $user->id,
                        'name' => $user->name,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'trade' => $user->trade,
                        'state' => $user->state,
                        'lga' => $user->lga,
                        'whatsapp' => $user->whatsapp,
                        'business_name' => $user->business_name,
                        'slug' => $user->slug,
                        'public_url' => $user->publicUrl(),
                        'avatar_url' => $user->avatar_url,
                        'profile_completion' => (int) ($user->profile_completion ?? 0),
                        'initials' => $this->initials($user->name, $user->first_name, $user->last_name),
                        'role' => $user->role?->value,
                        'role_label' => $user->role?->label(),
                        'is_staff' => $user->isStaff(),
                        'is_super_admin' => $user->isSuperAdmin(),
                    ]
                    : null,
            ],
            'cookieConsent' => CookieConsent::state($request),
            'flash' => [
                'toast' => fn () => $request->session()->get('toast'),
            ],
            'notifications' => [
                'unread_count' => 0,
                'items' => [],
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }

    private function initials(?string $name, ?string $firstName, ?string $lastName): string
    {
        if ($firstName || $lastName) {
            return strtoupper(
                mb_substr((string) $firstName, 0, 1).mb_substr((string) $lastName, 0, 1)
            ) ?: 'I';
        }

        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];

        if (count($parts) >= 2) {
            return strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[1], 0, 1));
        }

        return strtoupper(mb_substr((string) ($parts[0] ?? 'I'), 0, 1));
    }
}
