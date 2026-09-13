<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Identity\UserUid;
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

    public function index(Request $request): Response
    {
        $window = (string) $request->query('window', '30');
        $window = array_key_exists($window, self::WINDOWS) ? $window : '30';

        $counts = [];
        foreach (self::WINDOWS as $key => $days) {
            $counts[$key] = $this->windowQuery($days)->count();
        }

        $days = self::WINDOWS[$window];

        $rows = $this->windowQuery($days)
            ->withCount('workLogs')
            ->orderByRaw('COALESCE(last_login_at, created_at) asc')
            ->limit(300)
            ->get()
            ->map(fn (User $user) => $this->row($user))
            ->values();

        return Inertia::render('Admin/Ops/Reengagement', [
            'rows' => $rows,
            'counts' => $counts,
            'window' => $window,
            'windows' => array_keys(self::WINDOWS),
        ]);
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
