<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Identity\UserUid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    /**
     * Funnel drop-off segments the ops team should chase.
     */
    private const SEGMENTS = ['no_job', 'no_review', 'no_profile'];

    public function index(Request $request): Response
    {
        $segment = (string) $request->query('segment', 'no_job');
        $segment = in_array($segment, self::SEGMENTS, true) ? $segment : 'no_job';

        $counts = [
            'no_job' => $this->noJobQuery()->count(),
            'no_review' => $this->noReviewQuery()->count(),
            'no_profile' => $this->noProfileQuery()->count(),
        ];

        $query = match ($segment) {
            'no_review' => $this->noReviewQuery(),
            'no_profile' => $this->noProfileQuery(),
            default => $this->noJobQuery(),
        };

        $rows = $query
            ->withCount('workLogs')
            ->latest('created_at')
            ->limit(300)
            ->get()
            ->map(fn (User $user) => $this->row($user))
            ->values();

        return Inertia::render('Admin/Ops/Onboarding', [
            'rows' => $rows,
            'counts' => $counts,
            'segment' => $segment,
        ]);
    }

    private function baseQuery(): Builder
    {
        return User::query()
            ->artisans()
            ->whereNull('suspended_at')
            ->whereNotNull('email_verified_at');
    }

    /** Verified, but never logged a first job. */
    private function noJobQuery(): Builder
    {
        return $this->baseQuery()->whereDoesntHave('workLogs');
    }

    /** Logged at least one job, but never requested a review. */
    private function noReviewQuery(): Builder
    {
        return $this->baseQuery()
            ->whereHas('workLogs')
            ->whereDoesntHave('workLogs', fn ($q) => $q->whereNotNull('review_requested_at'));
    }

    /** Verified with a barely-started profile. */
    private function noProfileQuery(): Builder
    {
        return $this->baseQuery()->where('profile_completion', '<', 50);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(User $user): array
    {
        $tz = config('app.display_timezone');
        $whatsapp = trim((string) $user->whatsapp);
        $daysSince = $user->created_at ? (int) $user->created_at->diffInDays(now()) : 0;

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
            'profile_completion' => (int) ($user->profile_completion ?? 0),
            'joined' => $user->created_at?->timezone($tz)->format('j M Y'),
            'joined_iso' => $user->created_at?->toIso8601String(),
            'days_since_join' => $daysSince,
        ];
    }
}
