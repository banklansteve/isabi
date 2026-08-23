<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TokenTransaction extends Model
{
    public const TYPE_CREDIT = 'credit';

    public const TYPE_DEBIT = 'debit';

    public const ACTION_PURCHASE = 'purchase';

    public const ACTION_REVIEW_REQUEST = 'review_request';

    public const ACTION_REFUND = 'refund';

    public const ACTION_ADMIN_ADJUST = 'admin_adjust';

    public const ACTION_REFERRAL = 'referral';

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_after',
        'action',
        'description',
        'meta',
        'related_type',
        'related_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'balance_after' => 'integer',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }
}
