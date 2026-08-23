<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    public const STATUS_SIGNED_UP = 'signed_up';

    public const STATUS_REWARDED = 'rewarded';

    protected $fillable = [
        'referrer_user_id',
        'referred_user_id',
        'code_used',
        'status',
        'reward_tokens',
        'token_transaction_id',
        'qualified_at',
        'rewarded_at',
    ];

    protected function casts(): array
    {
        return [
            'reward_tokens' => 'integer',
            'qualified_at' => 'datetime',
            'rewarded_at' => 'datetime',
        ];
    }

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function tokenTransaction(): BelongsTo
    {
        return $this->belongsTo(TokenTransaction::class);
    }
}
