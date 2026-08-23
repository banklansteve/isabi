<?php

namespace App\Support\Tokens;

use App\Exceptions\InsufficientTokensException;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Models\WorkLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReviewLinkGate
{
    public function __construct(private readonly TokenWallet $wallet) {}

    /**
     * @return array{
     *     plan: string,
     *     has_annual: bool,
     *     annual_expires_at: ?string,
     *     token_balance: int,
     *     free_limit: int,
     *     free_used: int,
     *     free_remaining: int,
     *     token_cost: int,
     *     period_label: string,
     *     can_send_new: bool,
     *     next_send_uses_token: bool,
     *     next_send_is_free: bool,
     * }
     */
    public function status(User $user): array
    {
        $freeLimit = (int) config('pricing.free.monthly_review_links', 5);
        $tokenCost = (int) config('pricing.credits.actions.review_link', 1);
        $freeUsed = $this->freeUsedThisPeriod($user);
        $hasAnnual = $this->hasActiveAnnual($user);
        $freeRemaining = $hasAnnual ? $freeLimit : max(0, $freeLimit - $freeUsed);
        $balance = (int) $user->token_balance;
        $nextUsesToken = ! $hasAnnual && $freeRemaining < 1;
        $canSend = $hasAnnual || $freeRemaining > 0 || $balance >= $tokenCost;

        return [
            'plan' => $hasAnnual ? 'Annual' : 'Free',
            'has_annual' => $hasAnnual,
            'annual_expires_at' => $user->annual_expires_at?->toDateString(),
            'token_balance' => $balance,
            'free_limit' => $freeLimit,
            'free_used' => min($freeUsed, $freeLimit),
            'free_remaining' => $hasAnnual ? $freeLimit : $freeRemaining,
            'token_cost' => $tokenCost,
            'period_label' => $this->periodLabel(),
            'can_send_new' => $canSend,
            'next_send_uses_token' => $nextUsesToken,
            'next_send_is_free' => $hasAnnual || ! $nextUsesToken,
        ];
    }

    /**
     * Charge (or allow free/annual) for a brand-new review request on this job.
     * Resends on an already-requested job do not consume again.
     *
     * @throws InsufficientTokensException
     */
    public function authorizeNewRequest(User $user, WorkLog $workLog): void
    {
        if ($workLog->review_requested_at) {
            return;
        }

        DB::transaction(function () use ($user, $workLog) {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($this->hasActiveAnnual($lockedUser)) {
                return;
            }

            $freeLimit = (int) config('pricing.free.monthly_review_links', 5);
            $cost = (int) config('pricing.credits.actions.review_link', 1);
            $freeUsed = $this->freeUsedThisPeriod($lockedUser);

            if ($freeUsed < $freeLimit) {
                return;
            }

            if ((int) $lockedUser->token_balance < $cost) {
                throw new InsufficientTokensException(
                    message: "You’ve used all {$freeLimit} free review links for {$this->periodLabel()}. Buy tokens to send more.",
                    freeLimit: $freeLimit,
                    freeUsed: $freeUsed,
                    tokenBalance: (int) $lockedUser->token_balance,
                    tokensNeeded: $cost,
                );
            }

            $this->wallet->debit(
                user: $lockedUser,
                amount: $cost,
                action: TokenTransaction::ACTION_REVIEW_REQUEST,
                description: 'Review link for “'.str($workLog->description)->limit(48).'”',
                related: $workLog,
                meta: [
                    'work_log_uid' => $workLog->uid,
                    'period' => $this->periodKey(),
                ],
            );

            $user->setAttribute('token_balance', (int) $lockedUser->fresh()->token_balance);
        });
    }

    public function freeUsedThisPeriod(User $user): int
    {
        [$start, $end] = $this->periodBounds();

        return WorkLog::query()
            ->where('user_id', $user->id)
            ->whereNotNull('review_requested_at')
            ->whereBetween('review_requested_at', [$start, $end])
            ->count();
    }

    public function hasActiveAnnual(User $user): bool
    {
        if (($user->plan ?? 'free') !== 'annual') {
            return false;
        }

        if (! $user->annual_expires_at) {
            return true;
        }

        return $user->annual_expires_at->isFuture();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function periodBounds(): array
    {
        $mode = (string) config('pricing.free.review_link_reset', 'calendar_month');

        if ($mode === 'rolling_30_days') {
            return [now()->subDays(30), now()];
        }

        return [now()->startOfMonth(), now()->endOfMonth()];
    }

    public function periodLabel(): string
    {
        $mode = (string) config('pricing.free.review_link_reset', 'calendar_month');

        if ($mode === 'rolling_30_days') {
            return 'the last 30 days';
        }

        return now()->format('F Y');
    }

    public function periodKey(): string
    {
        $mode = (string) config('pricing.free.review_link_reset', 'calendar_month');

        if ($mode === 'rolling_30_days') {
            return 'rolling:'.now()->toDateString();
        }

        return now()->format('Y-m');
    }
}
