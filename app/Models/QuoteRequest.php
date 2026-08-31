<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class QuoteRequest extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_AWAITING_CLIENT = 'awaiting_client';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'uid',
        'work_log_id',
        'user_id',
        'name',
        'phone',
        'email',
        'subject',
        'message',
        'status',
        'client_token',
        'client_token_expires_at',
        'client_response',
        'client_responded_at',
        'accepted_at',
        'logged_work_log_id',
        'created_ip',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'client_token_expires_at' => 'datetime',
            'client_responded_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (QuoteRequest $request): void {
            if (blank($request->uid)) {
                $request->uid = (string) Str::uuid();
            }
        });
    }

    public function workLog(): BelongsTo
    {
        return $this->belongsTo(WorkLog::class);
    }

    public function loggedWorkLog(): BelongsTo
    {
        return $this->belongsTo(WorkLog::class, 'logged_work_log_id');
    }

    public function artisan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function artisanQuote(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ArtisanQuote::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    public function displayTitle(): string
    {
        return filled($this->subject)
            ? (string) $this->subject
            : (filled($this->message)
                ? Str::limit($this->message, 80)
                : 'Quote request');
    }

    public function isEditableByArtisan(): bool
    {
        return in_array($this->status, [
            self::STATUS_NEW,
            self::STATUS_DRAFT,
        ], true);
    }

    public function shouldPromptLogJob(): bool
    {
        if ($this->status !== self::STATUS_ACCEPTED || $this->logged_work_log_id) {
            return false;
        }

        $quote = $this->artisanQuote;
        $deliveryPassed = $quote?->estimated_start?->isPast() ?? false;
        $acceptedAwhile = $this->accepted_at?->lte(now()->subDays(3)) ?? false;

        return $deliveryPassed || $acceptedAwhile;
    }

    public function refreshExpiry(): void
    {
        if ($this->status !== self::STATUS_AWAITING_CLIENT) {
            return;
        }

        $validUntil = $this->artisanQuote?->valid_until;
        if ($validUntil && $validUntil->isPast()) {
            $this->forceFill(['status' => self::STATUS_EXPIRED])->save();
        }
    }
}
