<?php

namespace App\Http\Middleware;

use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Support\Admin\AnnouncementService;
use App\Support\CookieConsent;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
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

        if ($user?->isStaff()) {
            $user->loadMissing('staffRoles');
        }

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
                        'completion_checklist' => $user->profileCompletionChecklist(),
                        'initials' => $this->initials($user->name, $user->first_name, $user->last_name),
                        'role' => $user->role?->value,
                        'role_label' => $user->role?->label(),
                        'is_staff' => $user->isStaff(),
                        'is_super_admin' => $user->isSuperAdmin(),
                        'staff_status' => $user->staff_status?->value,
                        'abilities' => $user->isStaff() ? $user->permissionKeys() : [],
                        'restricted' => $user->isStaff() ? $user->isRestrictedStaff() : false,
                        'duties' => $user->isStaff() ? $user->assignedDuties() : [],
                    ]
                    : null,
                'impersonating' => $request->session()->get('impersonator_id')
                    ? [
                        'as' => $user?->displayBusinessName(),
                    ]
                    : null,
            ],
            'cookieConsent' => CookieConsent::state($request),
            'flash' => [
                'toast' => fn () => $request->session()->get('toast'),
            ],
            'notifications' => function () use ($user) {
                if (! $user || ! Schema::hasTable('announcement_deliveries')) {
                    return [
                        'unread_count' => 0,
                        'items' => [],
                    ];
                }

                $service = app(AnnouncementService::class);
                $inbox = $service->inboxFor($user);

                return [
                    'unread_count' => AnnouncementDelivery::query()
                        ->where('user_id', $user->id)
                        ->where('channel', Announcement::CHANNEL_IN_APP)
                        ->where('status', AnnouncementDelivery::STATUS_SENT)
                        ->count(),
                    'items' => $inbox->map(function (AnnouncementDelivery $delivery) use ($service, $user) {
                        $message = $delivery->announcement;

                        return [
                            'id' => $delivery->id,
                            'title' => $message
                                ? $service->interpolate($message->subject ?: $message->title, $user)
                                : 'Announcement',
                            'body' => $message
                                ? Str::limit($service->interpolate($message->body, $user), 90)
                                : '',
                            'time' => ($delivery->sent_at ?? $delivery->created_at)?->diffForHumans() ?? '',
                            'icon' => $message?->audience === 'staff' ? 'ti ti-shield' : 'ti ti-megaphone',
                            'unread' => $delivery->status === AnnouncementDelivery::STATUS_SENT,
                        ];
                    })->values()->all(),
                ];
            },
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'seo' => fn () => $this->seoFor($request)->toArray(),
        ];
    }

    private function seoFor(Request $request): Seo
    {
        $seo = app(Seo::class);

        if ($this->isPrivatePath($request)) {
            $seo->noindex();
        }

        return $seo;
    }

    private function isPrivatePath(Request $request): bool
    {
        return $request->is([
            'dashboard',
            'dashboard/*',
            'work-log',
            'work-log/*',
            'tokens',
            'tokens/*',
            'credits',
            'credits/*',
            'referrals',
            'referrals/*',
            'help',
            'help/*',
            'profile',
            'profile/*',
            'my-page',
            'admin',
            'admin/*',
            'internal',
            'internal/*',
            'login',
            'register',
            'forgot-password',
            'reset-password',
            'reset-password/*',
            'verify-email',
            'verify-email/*',
            'confirm-password',
            'r/*',
        ]);
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
