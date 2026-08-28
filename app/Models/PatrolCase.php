<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatrolCase extends Model
{
    public const STATUS_NEW = 'new';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_RESOLVED_DISMISSED = 'resolved_dismissed';

    public const STATUS_RESOLVED_ACTIONED = 'resolved_actioned';

    public const SEVERITY_LOW = 'low';

    public const SEVERITY_MEDIUM = 'medium';

    public const SEVERITY_HIGH = 'high';

    public const KIND_JOB = 'job';

    public const KIND_REVIEW = 'review';

    protected $fillable = [
        'kind',
        'work_log_id',
        'review_id',
        'user_id',
        'assigned_to',
        'status',
        'severity',
        'recommended_outcome',
        'visibility_was_public',
        'auto_hidden_at',
        'flagged_at',
        'resolved_at',
        'resolved_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'visibility_was_public' => 'boolean',
            'auto_hidden_at' => 'datetime',
            'flagged_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function workLog(): BelongsTo
    {
        return $this->belongsTo(WorkLog::class);
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function artisan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(PatrolCaseRule::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(PatrolCaseNote::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(PatrolCaseAction::class);
    }

    public function isJob(): bool
    {
        return $this->kind !== self::KIND_REVIEW;
    }

    public function isReview(): bool
    {
        return $this->kind === self::KIND_REVIEW;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, config('patrol.open_statuses', []), true);
    }

    public function isResolved(): bool
    {
        return ! $this->isOpen();
    }

    public function statusLabel(): string
    {
        return config('patrol.statuses.'.$this->status, $this->status);
    }

    public function severityLabel(): string
    {
        return config('patrol.severities.'.$this->severity, $this->severity);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', config('patrol.open_statuses', []));
    }

    public function scopeJobs(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder->where('kind', self::KIND_JOB)->orWhereNull('kind');
        });
    }

    public function scopeReviews(Builder $query): Builder
    {
        return $query->where('kind', self::KIND_REVIEW);
    }

    /**
     * @return array<string, string>
     */
    public function recommendationOptions(): array
    {
        $key = $this->isReview() ? 'patrol.review_recommendations' : 'patrol.recommendations';

        return config($key, []);
    }
}
