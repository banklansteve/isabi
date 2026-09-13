<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Identity\UserUid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    public function index(Request $request): Response
    {
        $filter = (string) $request->query('filter', 'all');

        $base = fn (): Builder => User::query()
            ->artisans()
            ->whereNull('email_verified_at')
            ->whereNull('suspended_at');

        $counts = [
            'all' => (clone $base())->count(),
            'missing_whatsapp' => (clone $base())
                ->where(fn ($q) => $q->whereNull('whatsapp')->orWhere('whatsapp', ''))
                ->count(),
            'missing_trade' => (clone $base())
                ->where(fn ($q) => $q->whereNull('trade')->orWhere('trade', ''))
                ->count(),
            'active' => (clone $base())
                ->whereHas('workLogs')
                ->count(),
        ];

        $query = $base();

        if ($filter === 'missing_whatsapp') {
            $query->where(fn ($q) => $q->whereNull('whatsapp')->orWhere('whatsapp', ''));
        } elseif ($filter === 'missing_trade') {
            $query->where(fn ($q) => $q->whereNull('trade')->orWhere('trade', ''));
        } elseif ($filter === 'active') {
            $query->whereHas('workLogs');
        }

        $rows = $query
            ->withCount('workLogs')
            ->latest('created_at')
            ->limit(300)
            ->get()
            ->map(fn (User $user) => $this->row($user))
            ->values();

        return Inertia::render('Admin/Ops/Verification', [
            'rows' => $rows,
            'counts' => $counts,
            'filter' => in_array($filter, ['all', 'missing_whatsapp', 'missing_trade', 'active'], true) ? $filter : 'all',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(User $user): array
    {
        $tz = config('app.display_timezone');
        $whatsapp = trim((string) $user->whatsapp);
        $trade = trim((string) $user->trade);

        return [
            'id' => $user->id,
            'uid' => $user->uid,
            'uid_kind' => UserUid::isStaffUid($user->uid) ? 'staff' : 'user',
            'name' => $user->displayBusinessName(),
            'person' => trim($user->first_name.' '.$user->last_name) ?: $user->name,
            'email' => $user->email,
            'trade' => $trade ?: null,
            'whatsapp' => $whatsapp ?: null,
            'state' => $user->state,
            'lga' => $user->lga,
            'avatar_url' => $user->avatar_url,
            'public_url' => $user->publicUrl(),
            'jobs' => (int) ($user->work_logs_count ?? 0),
            'profile_completion' => (int) ($user->profile_completion ?? 0),
            'missing_whatsapp' => $whatsapp === '',
            'missing_trade' => $trade === '',
            'joined' => $user->created_at?->timezone($tz)->format('j M Y'),
            'joined_iso' => $user->created_at?->toIso8601String(),
        ];
    }
}
