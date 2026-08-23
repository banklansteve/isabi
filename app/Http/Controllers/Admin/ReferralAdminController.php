<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\User;
use App\Support\Admin\DashboardMetrics;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ReferralAdminController extends Controller
{
    public function index(DashboardMetrics $metrics): Response
    {
        $totals = [
            'signed_up' => Referral::query()->count(),
            'rewarded' => Referral::query()->where('status', Referral::STATUS_REWARDED)->count(),
            'tokens' => (int) Referral::query()->sum('reward_tokens'),
        ];

        $topRows = Referral::query()
            ->select('referrer_user_id', DB::raw('COUNT(*) as total'), DB::raw('SUM(reward_tokens) as tokens'))
            ->groupBy('referrer_user_id')
            ->orderByDesc('total')
            ->limit(20)
            ->get();

        $topUsers = User::query()
            ->whereIn('id', $topRows->pluck('referrer_user_id'))
            ->get()
            ->keyBy('id');

        $top = $topRows->map(function ($row) use ($topUsers) {
            $user = $topUsers->get($row->referrer_user_id);

            return [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->displayBusinessName(),
                    'email' => $user->email,
                ] : null,
                'total' => (int) $row->total,
                'tokens' => (int) $row->tokens,
            ];
        });

        $signalRows = Referral::query()
            ->select('referrer_user_id', DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->groupBy('referrer_user_id', 'day')
            ->having('total', '>=', 8)
            ->orderByDesc('total')
            ->limit(30)
            ->get();

        $signalUsers = User::query()
            ->whereIn('id', $signalRows->pluck('referrer_user_id'))
            ->get()
            ->keyBy('id');

        $signals = $signalRows->map(function ($row) use ($signalUsers) {
            $user = $signalUsers->get($row->referrer_user_id);

            return [
                'day' => $row->day,
                'total' => (int) $row->total,
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->displayBusinessName(),
                    'email' => $user->email,
                ] : null,
            ];
        });

        $recent = Referral::query()
            ->with(['referrer:id,name,email,business_name', 'referred:id,name,email,business_name'])
            ->latest('id')
            ->limit(800)
            ->get()
            ->map(fn (Referral $referral) => [
                'id' => $referral->id,
                'status' => $referral->status,
                'tokens' => $referral->reward_tokens,
                'when' => $referral->created_at?->timezone(config('app.display_timezone'))->format('j M Y'),
                'created_iso' => $referral->created_at?->toIso8601String(),
                'referrer' => $referral->referrer ? [
                    'id' => $referral->referrer->id,
                    'name' => $referral->referrer->displayBusinessName(),
                    'email' => $referral->referrer->email,
                ] : null,
                'referred' => $referral->referred ? [
                    'id' => $referral->referred->id,
                    'name' => $referral->referred->displayBusinessName(),
                    'email' => $referral->referred->email,
                ] : null,
            ])
            ->values();

        return Inertia::render('Admin/Referrals/Index', [
            'totals' => $totals,
            'top' => $top,
            'signals' => $signals,
            'recent' => $recent,
            'insights' => $metrics->referralInsights(),
        ]);
    }
}
