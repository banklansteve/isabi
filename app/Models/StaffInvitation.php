<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'email',
    'token_hash',
    'code_hash',
    'attempts',
    'expires_at',
    'last_sent_at',
    'verified_at',
    'consumed_at',
    'revoked_at',
    'suggested_staff_role_id',
    'invited_by_user_id',
])]
#[Hidden(['code_hash', 'token_hash'])]
class StaffInvitation extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'verified_at' => 'datetime',
            'consumed_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function suggestedRole(): BelongsTo
    {
        return $this->belongsTo(StaffRole::class, 'suggested_staff_role_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_user_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at === null || $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isPending(): bool
    {
        return ! $this->isConsumed() && ! $this->isRevoked();
    }

    public function isLocked(): bool
    {
        $max = (int) config('admin.invite.max_attempts', 5);

        return $this->attempts >= $max;
    }

    public function canResend(): bool
    {
        if ($this->isConsumed() || $this->isRevoked()) {
            return false;
        }

        $delay = (int) config('admin.invite.resend_delay_seconds', 60);

        if ($this->last_sent_at === null) {
            return true;
        }

        return $this->last_sent_at->copy()->addSeconds($delay)->isPast();
    }

    public function secondsUntilResend(): int
    {
        if ($this->canResend()) {
            return 0;
        }

        $delay = (int) config('admin.invite.resend_delay_seconds', 60);

        return max(0, $this->last_sent_at->copy()->addSeconds($delay)->diffInSeconds(now()));
    }
}
