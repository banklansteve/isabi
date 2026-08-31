<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'uid',
    'user_id',
    'assigned_to_user_id',
    'assigned_at',
    'subject',
    'topic_key',
    'tags',
    'status',
    'last_reply_at',
    'first_response_at',
    'last_customer_message_at',
    'last_staff_message_at',
    'customer_last_read_at',
    'staff_last_read_at',
    'resolved_at',
    'csat_score',
    'csat_comment',
    'csat_dismissed_at',
])]
class SupportTicket extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_OPEN = 'open';

    public const STATUS_PENDING = 'pending';

    public const STATUS_RESOLVED = 'resolved';

    protected static function booted(): void
    {
        static::creating(function (self $ticket) {
            if (! filled($ticket->uid)) {
                $ticket->uid = \App\Support\SupportChat\SupportTicketUid::unique();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'assigned_at' => 'datetime',
            'last_reply_at' => 'datetime',
            'first_response_at' => 'datetime',
            'last_customer_message_at' => 'datetime',
            'last_staff_message_at' => 'datetime',
            'customer_last_read_at' => 'datetime',
            'staff_last_read_at' => 'datetime',
            'resolved_at' => 'datetime',
            'csat_dismissed_at' => 'datetime',
            'csat_score' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_NEW, self::STATUS_OPEN, self::STATUS_PENDING], true);
    }

    public function adminShowUrl(): string
    {
        if (! filled($this->uid)) {
            return route('admin.support.index');
        }

        return route('admin.support.show', ['ticket' => $this->uid]);
    }
}
