<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Admin\AdminNavigation;
use App\Support\Admin\OpsAttentionFeed;
use App\Support\Identity\UserUid;
use App\Support\Patrol\LifecyclePatrolReport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReengagementController extends Controller
{
    /** Inactivity windows in days. */
    private const WINDOWS = [
        '30' => 30,
        '60' => 60,
        '90' => 90,
    ];

    public function index(Request $request, LifecyclePatrolReport $lifecycle): Response
    {
        abort_unless($request->user()?->canDo('ops.reengagement.manage'), 403);

        $kind = (string) $request->query('kind', 'login');
        $kind = in_array($kind, ['login', 'dormant'], true) ? $kind : 'login';

        if (
            AdminNavigation::shouldMutateAttention($request)
            && $request->user()?->isStaff()
            && ! $request->user()->isRestrictedStaff()
        ) {
            app(OpsAttentionFeed::class)->markGroupOpened(
                $request->user(),
                $kind === 'dormant' ? 'dormant' : 'reengagement',
            );
        }

        $window = (string) $request->query('window', '30');
        $window = array_key_exists($window, self::WINDOWS) ? $window : '30';

        if ($kind === 'dormant') {
            $payload = $lifecycle->dormant($window, 100, null);
            $rows = collect($payload['people']->items())
                ->map(fn (array $person) => $this->dormantRow($person))
                ->values()
                ->all();

            return Inertia::render('Admin/Ops/Reengagement', [
                'kind' => 'dormant',
                'rows' => $rows,
                'counts' => $payload['counts'],
                'window' => $payload['window'],
                'windows' => $payload['windows'],
                'login_counts' => $this->loginCounts(),
                'dormant_counts' => $payload['counts'],
            ]);
        }

        $counts = $this->loginCounts();
        $days = self::WINDOWS[$window];

        $rows = $this->windowQuery($days)
            ->withCount('workLogs')
            ->orderByRaw('COALESCE(last_login_at, created_at) asc')
            ->limit(100)
            ->get()
            ->map(fn (User $user) => $this->row($user))
            ->values();

        $dormantCounts = [];
        foreach (self::WINDOWS as $key => $quietDays) {
            $dormantCounts[$key] = $lifecycle->dormantQueryPublic($quietDays)->count();
        }

        return Inertia::render('Admin/Ops/Reengagement', [
            'kind' => 'login',
            'rows' => $rows,
            'counts' => $counts,
            'window' => $window,
            'windows' => array_keys(self::WINDOWS),
            'login_counts' => $counts,
            'dormant_counts' => $dormantCounts,
        ]);
    }

    /**
     * @return array<string, int>
     */
    private function loginCounts(): array
    {
        $counts = [];
        foreach (self::WINDOWS as $key => $days) {
            $counts[$key] = $this->windowQuery($days)->count();
        }

        return $counts;
    }

    /**
     * Artisans whose last sign-in (or sign-up, if they never returned) is
     * older than the given window and who are not suspended.
     */
    private function windowQuery(int $days): Builder
    {
        $cutoff = now()->subDays($days);

        return User::query()
            ->artisans()
            ->whereNull('suspended_at')
            ->where(function (Builder $query) use ($cutoff) {
                $query->where('last_login_at', '<', $cutoff)
                    ->orWhere(fn (Builder $inner) => $inner
                        ->whereNull('last_login_at')
                        ->where('created_at', '<', $cutoff));
            });
    }

    /**
     * @param  array<string, mixed>  $person
     * @return array<string, mixed>
     */
    private function dormantRow(array $person): array
    {
        return [
            'id' => $person['id'],
            'uid' => null,
            'name' => $person['business_name'] ?: $person['name'],
            'person' => $person['name'],
            'email' => $person['email'] ?? null,
            'trade' => $person['trade'] ?? null,
            'whatsapp' => $person['whatsapp'] ?? null,
            'state' => $person['state'] ?? null,
            'avatar_url' => null,
            'jobs' => $person['jobs'] ?? 0,
            'reviews' => $person['reviews'] ?? 0,
            'never_returned' => ($person['risk']['label'] ?? '') === 'Never returned',
            'risk' => $person['risk'] ?? null,
            'last_seen' => $person['last_login_label'] ?? null,
            'days_inactive' => $person['days_inactive'] ?? null,
            'detail' => $person['risk']['detail'] ?? null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function row(User $user): array
    {
        $tz = config('app.display_timezone');
        $whatsapp = trim((string) $user->whatsapp);
        $lastSeen = $user->last_login_at ?? $user->created_at;

        return [
            'id' => $user->id,
            'uid' => $user->uid,
            'uid_kind' => UserUid::isStaffUid($user->uid) ? 'staff' : 'user',
            'name' => $user->displayBusinessName(),
            'person' => trim($user->first_name.' '.$user->last_name) ?: $user->name,
            'email' => $user->email,
            'trade' => trim((string) $user->trade) ?: null,
            'whatsapp' => $whatsapp ?: null,
            'state' => $user->state,
            'avatar_url' => $user->avatar_url,
            'public_url' => $user->publicUrl(),
            'jobs' => (int) ($user->work_logs_count ?? 0),
            'never_returned' => $user->last_login_at === null,
            'last_seen' => $lastSeen?->timezone($tz)->format('j M Y'),
            'last_seen_iso' => $lastSeen?->toIso8601String(),
            'days_inactive' => $lastSeen ? (int) $lastSeen->diffInDays(now()) : null,
        ];
    }
}
