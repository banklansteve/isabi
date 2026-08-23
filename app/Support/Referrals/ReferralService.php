<?php

namespace App\Support\Referrals;

use App\Models\Referral;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Models\WorkLog;
use App\Support\ActivityLogger;
use App\Support\Tokens\TokenWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferralService
{
    public function __construct(
        private readonly TokenWallet $wallet,
    ) {}

    public static function generateCode(): string
    {
        for ($i = 0; $i < 16; $i++) {
            $code = strtoupper(Str::random(8));
            if (! User::query()->where('referral_code', $code)->exists()) {
                return $code;
            }
        }

        return strtoupper(Str::random(10));
    }

    public function ensureCode(User $user): string
    {
        if (filled($user->referral_code)) {
            return (string) $user->referral_code;
        }

        $code = self::generateCode();
        $user->forceFill(['referral_code' => $code])->saveQuietly();

        return $code;
    }

    public function inviteUrl(User $user): string
    {
        $code = $this->ensureCode($user);

        return url('/register?ref='.$code);
    }

    public function resolveReferrer(?string $raw): ?User
    {
        $code = strtoupper(trim((string) $raw));
        if ($code === '') {
            return null;
        }

        $byCode = User::query()->where('referral_code', $code)->first();
        if ($byCode) {
            return $byCode;
        }

        // Fallback: older invite links used public slug.
        return User::query()
            ->whereRaw('LOWER(slug) = ?', [strtolower($code)])
            ->first();
    }

    /**
     * Attribute a newly registered user to a referrer (idempotent).
     */
    public function attributeOnSignup(User $referred, ?string $rawCode): ?Referral
    {
        $referrer = $this->resolveReferrer($rawCode);
        if (! $referrer || (int) $referrer->id === (int) $referred->id) {
            return null;
        }

        return DB::transaction(function () use ($referrer, $referred, $rawCode) {
            $existing = Referral::query()
                ->where('referred_user_id', $referred->id)
                ->first();

            if ($existing) {
                return $existing;
            }

            $referred->forceFill([
                'referred_by_user_id' => $referrer->id,
                'referred_at' => now(),
            ])->saveQuietly();

            $referral = Referral::query()->create([
                'referrer_user_id' => $referrer->id,
                'referred_user_id' => $referred->id,
                'code_used' => strtoupper(trim((string) $rawCode)) ?: $referrer->referral_code,
                'status' => Referral::STATUS_SIGNED_UP,
                'reward_tokens' => 0,
            ]);

            ActivityLogger::log(
                action: 'referral.signed_up',
                summary: "{$referred->name} joined via {$referrer->name}'s invite.",
                user: $referrer,
                properties: [
                    'referred_user_id' => $referred->id,
                    'code' => $referral->code_used,
                ],
            );

            return $referral;
        });
    }

    /**
     * Credit the referrer when the referred artisan logs their first job.
     */
    public function qualifyOnFirstJob(User $referred): ?Referral
    {
        $referral = Referral::query()
            ->where('referred_user_id', $referred->id)
            ->where('status', Referral::STATUS_SIGNED_UP)
            ->first();

        if (! $referral) {
            return null;
        }

        $jobCount = WorkLog::query()->where('user_id', $referred->id)->count();
        if ($jobCount < 1) {
            return null;
        }

        $reward = max(1, (int) config('pricing.referral.credits_reward', 5));

        return DB::transaction(function () use ($referral, $referred, $reward) {
            $locked = Referral::query()
                ->whereKey($referral->id)
                ->lockForUpdate()
                ->first();

            if (! $locked || $locked->status === Referral::STATUS_REWARDED) {
                return $locked;
            }

            $referrer = User::query()
                ->whereKey($locked->referrer_user_id)
                ->lockForUpdate()
                ->first();

            if (! $referrer) {
                return $locked;
            }

            $tx = $this->wallet->credit(
                user: $referrer,
                amount: $reward,
                action: TokenTransaction::ACTION_REFERRAL,
                description: "Referral reward — {$referred->displayBusinessName()} logged their first job",
                related: $locked,
                meta: [
                    'referred_user_id' => $referred->id,
                    'reward_tokens' => $reward,
                ],
            );

            $locked->forceFill([
                'status' => Referral::STATUS_REWARDED,
                'reward_tokens' => $reward,
                'token_transaction_id' => $tx->id,
                'qualified_at' => now(),
                'rewarded_at' => now(),
            ])->save();

            ActivityLogger::log(
                action: 'referral.rewarded',
                summary: "{$referrer->name} earned {$reward} tokens from a referral.",
                user: $referrer,
                properties: [
                    'referred_user_id' => $referred->id,
                    'tokens' => $reward,
                    'transaction_id' => $tx->id,
                ],
            );

            return $locked->fresh();
        });
    }
}
