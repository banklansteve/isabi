<?php

namespace App\Http\Controllers;

use App\Models\Referral;
use App\Support\ActivityLogger;
use App\Support\Referrals\ReferralService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    public function index(Request $request, ReferralService $referrals): Response
    {
        $user = $request->user();

        ActivityLogger::log(
            action: 'page.referrals',
            summary: "{$user->name} opened Referrals.",
            user: $user,
        );

        $code = $referrals->ensureCode($user);
        $invitees = Referral::query()
            ->with(['referred:id,first_name,last_name,business_name,name,created_at'])
            ->where('referrer_user_id', $user->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (Referral $row) => [
                'id' => $row->id,
                'name' => $row->referred?->displayBusinessName() ?: 'Artisan',
                'status' => $row->status,
                'status_label' => $row->status === Referral::STATUS_REWARDED
                    ? 'Rewarded'
                    : 'Signed up',
                'reward_tokens' => (int) $row->reward_tokens,
                'joined_label' => $row->created_at?->timezone(config('app.display_timezone'))->format('j M Y'),
            ]);

        $earnedTokens = (int) Referral::query()
            ->where('referrer_user_id', $user->id)
            ->where('status', Referral::STATUS_REWARDED)
            ->sum('reward_tokens');

        return Inertia::render('Referrals/Index', [
            'referralLink' => $referrals->inviteUrl($user),
            'code' => $code,
            'rewardTokens' => (int) config('pricing.referral.credits_reward', 5),
            'invitedCount' => $invitees->count(),
            'earnedTokens' => $earnedTokens,
            'invitees' => $invitees,
        ]);
    }
}
