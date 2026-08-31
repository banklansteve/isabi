<?php

namespace App\Models;

use App\Support\JobReference;
use App\Support\JobSlug;
use App\Support\WorkLogEditPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class WorkLog extends Model
{
    protected $fillable = [
        'user_id',
        'uid',
        'reference',
        'slug',
        'description',
        'worked_on',
        'client_name',
        'job_category',
        'job_subcategory',
        'job_review_phrase',
        'service_state',
        'service_lga',
        'service_city',
        'client_whatsapp',
        'amount_charged',
        'review_requested_at',
        'review_token',
        'review_token_expires_at',
        'review_reminder_sent_at',
        'flagged_at',
        'flag_reason',
        'hidden_at',
        'hidden_reason',
        'removed_at',
        'referred_to_user_id',
        'referred_by_user_id',
        'referred_note',
        'referred_at',
        'created_ip',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'worked_on' => 'date',
            'amount_charged' => 'integer',
            'review_requested_at' => 'datetime',
            'review_token_expires_at' => 'datetime',
            'review_reminder_sent_at' => 'datetime',
            'flagged_at' => 'datetime',
            'hidden_at' => 'datetime',
            'removed_at' => 'datetime',
            'referred_at' => 'datetime',
        ];
    }

    public function isPubliclyVisible(): bool
    {
        return $this->hidden_at === null && $this->removed_at === null;
    }

    public function scopePubliclyVisible($query)
    {
        return $query->whereNull('hidden_at')->whereNull('removed_at');
    }

    public function reminderDue(): bool
    {
        if ($this->review_reminder_sent_at || $this->review || ! $this->review_requested_at) {
            return false;
        }

        $user = $this->relationLoaded('user') ? $this->user : $this->user()->first();
        if (! $user?->wantsReviewReminders()) {
            return false;
        }

        $days = $user->reviewReminderDays();
        if ($days === null) {
            return false;
        }

        return $this->review_requested_at->copy()->addDays($days)->isPast();
    }

    protected static function booted(): void
    {
        static::creating(function (WorkLog $log): void {
            if (blank($log->uid)) {
                $log->uid = (string) Str::uuid();
            }

            if (blank($log->reference)) {
                $log->reference = JobReference::unique(null, $log->description);
            }

            if (blank($log->slug) && filled($log->user_id)) {
                $log->slug = JobSlug::uniqueFor($log->user_id, $log->description);
            }
        });

        // A corrected description should fix the URL with it, but only while
        // nothing has been shared publicly — once a review link is out or a
        // review has landed, the slug is frozen so the link can't rot.
        static::updating(function (WorkLog $log): void {
            if (! $log->isDirty('description') || blank($log->user_id)) {
                return;
            }

            if ($log->review_requested_at || $log->review()->exists()) {
                return;
            }

            $log->slug = JobSlug::uniqueFor($log->user_id, $log->description, $log->id);
        });
    }

    /** Public job pages only earn indexing once there's something to show. */
    public function isPubliclySubstantial(): bool
    {
        $hasReview = $this->relationLoaded('review')
            ? $this->review !== null
            : $this->review()->exists();

        if ($hasReview) {
            return true;
        }

        return $this->relationLoaded('media')
            ? $this->media->isNotEmpty()
            : $this->media()->exists();
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    public function publicUrl(): ?string
    {
        $user = $this->relationLoaded('user') ? $this->user : $this->user()->first();

        if (blank($user?->slug) || blank($this->reference)) {
            return null;
        }

        return route('public.job', [$user->slug, $this->reference]);
    }

    public function quoteRequests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(WorkLogMedia::class)->orderBy('sort_order');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function patrolCase(): HasOne
    {
        return $this->hasOne(PatrolCase::class);
    }

    public function referredTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_to_user_id');
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    public function amountInNaira(): ?float
    {
        if ($this->amount_charged === null) {
            return null;
        }

        return $this->amount_charged / 100;
    }

    /**
     * @return array<string, mixed>
     */
    public function editFlags(): array
    {
        return WorkLogEditPolicy::flags($this);
    }
}
