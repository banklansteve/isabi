<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'uid',
    'type',
    'name',
    'last_message_at',
])]
class StaffConversation extends Model
{
    public const TYPE_ASAP = 'asap';

    public const TYPE_DIRECT = 'direct';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'staff_conversation_participants')
            ->withTimestamps()
            ->withPivot(['last_read_at']);
    }

    public function participantRows(): HasMany
    {
        return $this->hasMany(StaffConversationParticipant::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(StaffMessage::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(StaffMessage::class)->latestOfMany();
    }

    public function isAsap(): bool
    {
        return $this->type === self::TYPE_ASAP;
    }

    public function isDirect(): bool
    {
        return $this->type === self::TYPE_DIRECT;
    }

    public function adminShowUrl(): string
    {
        if (! filled($this->uid)) {
            return route('admin.asap.index');
        }

        return route('admin.asap.show', ['conversation' => $this->uid]);
    }
}
