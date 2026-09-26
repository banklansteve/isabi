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
        'subject',
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

    public const SUBJECT_MAX = 80;

    /** Max length for the “what was done” description. */
    public const DESCRIPTION_MAX = 2000;

    /** Prefix for public review invite tokens (`rvw_` + random body). */
    public const REVIEW_TOKEN_PREFIX = 'rvw_';

    /** Random body length for review tokens (crypto-secure). Total = prefix + this. */
    public const REVIEW_TOKEN_BODY_LENGTH = 26;

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
                $log->reference = JobReference::unique();
            }

            if (blank($log->slug)) {
                $log->slug = JobSlug::seoPrefix($log->job_category, $log->job_subcategory);
            }
        });

        // Category/subcategory may refresh the SEO prefix. The opaque reference
        // never changes. Old full paths are remembered so shared links 301.
        static::updating(function (WorkLog $log): void {
            if (
                ! $log->isDirty('job_category')
                && ! $log->isDirty('job_subcategory')
            ) {
                return;
            }

            if ($log->review_requested_at || $log->review()->exists()) {
                return;
            }

            $oldPath = $log->getOriginal('slug') && $log->getOriginal('reference')
                ? JobSlug::compose(
                    (string) $log->getOriginal('slug'),
                    (string) $log->getOriginal('reference'),
                )
                : null;

            $log->slug = JobSlug::seoPrefix($log->job_category, $log->job_subcategory);

            if ($oldPath) {
                $log->pendingSlugRedirect = $oldPath;
            }
        });

        static::updated(function (WorkLog $log): void {
            if (! empty($log->pendingSlugRedirect)) {
                JobSlugRedirect::remember((string) $log->pendingSlugRedirect, $log);
                $log->pendingSlugRedirect = null;
            }
        });
    }

    /** @internal */
    public ?string $pendingSlugRedirect = null;

    /** Short title for lists and page headings (not used in public URLs). */
    public function displayTitle(): string
    {
        if (filled($this->subject)) {
            return (string) $this->subject;
        }

        return filled($this->description)
            ? (string) $this->description
            : 'Completed job';
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
        $params = $this->publicRouteParams();

        return $params ? route('public.job', $params) : null;
    }

    /**
     * Route params for the canonical public job URL:
     * /p/{artisan-slug}/{seo-context}/{opaque-reference}
     *
     * @return list<string>|null
     */
    public function publicRouteParams(): ?array
    {
        $user = $this->relationLoaded('user') ? $this->user : $this->user()->first();
        $context = $this->publicContext();

        if (blank($user?->slug) || blank($context) || blank($this->reference)) {
            return null;
        }

        return [(string) $user->slug, $context, (string) $this->reference];
    }

    /**
     * SEO context segment only (catalog-derived, never free text).
     */
    public function publicContext(): ?string
    {
        if (filled($this->slug)) {
            return (string) $this->slug;
        }

        return JobSlug::seoPrefix($this->job_category, $this->job_subcategory);
    }

    /**
     * Relative path after artisan slug: {seo-context}/{opaque-reference}
     * Used for redirects and embed lookups.
     */
    public function publicPathSegment(): ?string
    {
        if (blank($this->reference)) {
            return null;
        }

        $context = $this->publicContext();

        return $context
            ? JobSlug::compose($context, (string) $this->reference)
            : null;
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
