<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'support_ticket_id',
    'user_id',
    'is_staff',
    'kind',
    'body',
    'attachment_disk',
    'attachment_path',
    'attachment_url',
    'attachment_name',
    'attachment_mime',
    'attachment_size',
])]
class SupportTicketMessage extends Model
{
    public const KIND_MESSAGE = 'message';

    public const KIND_NOTE = 'note';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_staff' => 'boolean',
            'attachment_size' => 'integer',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reactions(): MorphMany
    {
        return $this->morphMany(ChatMessageReaction::class, 'message');
    }

    public function isNote(): bool
    {
        return $this->kind === self::KIND_NOTE;
    }
}
