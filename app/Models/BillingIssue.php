<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'uid',
    'user_id',
    'token_purchase_id',
    'type',
    'status',
    'title',
    'details',
    'amount_kobo',
    'currency',
    'reference',
    'assigned_to_user_id',
    'resolved_by_user_id',
    'resolved_at',
    'resolution_note',
])]
class BillingIssue extends Model
{
    public const TYPE_PAYMENT_FAILED = 'payment_failed';

    public const TYPE_MULTI_CHARGE = 'multi_charge';

    public const TYPE_CHARGEBACK = 'chargeback';

    public const TYPE_OTHER = 'other';

    public const STATUS_OPEN = 'open';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_DISMISSED = 'dismissed';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount_kobo' => 'integer',
            'resolved_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(TokenPurchase::class, 'token_purchase_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_ASSIGNED], true);
    }
}
