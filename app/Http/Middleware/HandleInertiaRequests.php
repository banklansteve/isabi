<?php

namespace App\Http\Middleware;

use App\Models\AnnouncementDelivery;
use App\Support\Admin\AnnouncementService;
use App\Support\Admin\ApprovalService;
use App\Support\Admin\OpsAttentionFeed;
use App\Support\Admin\OpsDutyPresenter;
use App\Support\CookieConsent;
use App\Support\Identity\UserUid;
use App\Support\Seo;
use App\Support\StaffChat\StaffChatService;
use App\Support\Staff\StaffPresence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
            app(StaffPresence::class)->touch($user);
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user
                    ? [
                        'id' => $user->id,
                        'uid' => $user->uid,
                        'uid_kind' => UserUid::isStaffUid($user->uid) ? 'staff' : 'user',
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
                        'duties' => $user->isStaff() ? OpsDutyPresenter::badges($user) : [],
                        'pending_notices' => $user->isStaff() && Schema::hasTable('disciplinary_actions')
                            ? $user->pendingDisciplinaryNoticeCount()
                            : 0,
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
            'ops_inbox' => function () use ($user) {
                if (! $user?->isOperationsAdmin() || $user->isRestrictedStaff()) {
                    return [
                        'greeting' => 'Hello',
                        'open_count' => 0,
                        'unread_count' => 0,
                        'items' => [],
                        'tasks' => [],
                        'attention_items' => [],
                        'priority_groups' => [],
                        'shortcuts' => [],
                        'roles' => [],
                        'role_summary' => 'Operations',
                    ];
                }

                return app(OpsAttentionFeed::class)->inbox($user);
            },
            'asap_unread' => function () use ($user) {
                if (! $user?->isStaff() || $user->isRestrictedStaff() || ! Schema::hasTable('staff_conversations')) {
                    return 0;
                }

                return app(StaffChatService::class)->unreadCount($user);
            },
            'notifications' => function () use ($user) {
                if (! $user || ! Schema::hasTable('announcement_deliveries')) {
                    return [
                        'unread_count' => 0,
                        'items' => [],
                    ];
                }

                return app(AnnouncementService::class)->inboxPayloadFor($user);
            },
            'admin_inbox' => function () use ($user) {
                if (! $user?->isSuperAdmin()) {
                    return null;
                }

                return app(OpsAttentionFeed::class)->superAdminInbox($user);
            },
            'my_approvals_pending' => function () use ($user) {
                if (! $user?->isStaff() || $user->isRestrictedStaff() || $user->isSuperAdmin()) {
                    return 0;
                }

                return app(ApprovalService::class)->pendingCountFor($user);
            },
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'reverb' => [
                'key' => (string) config('broadcasting.connections.reverb.key'),
                'port' => (int) (config('broadcasting.connections.reverb.options.port') ?: 8080),
                'scheme' => (string) (config('broadcasting.connections.reverb.options.scheme') ?: 'http'),
                'enabled' => config('broadcasting.default') === 'reverb'
                    && filled(config('broadcasting.connections.reverb.key')),
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
