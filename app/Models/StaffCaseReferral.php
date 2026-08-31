<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffCaseReferral extends Model
{
    public const SUBJECT_JOB = 'job';

    public const SUBJECT_REVIEW = 'review';

    public const SUBJECT_SUPPORT = 'support';

    public const SUBJECT_PATROL = 'patrol';

    public const SUBJECT_USER = 'user';

    public const QUEUE_MODERATION = 'moderation';

    public const QUEUE_SUPPORT = 'support';

    public const QUEUE_PATROL = 'patrol';

    public const QUEUE_GENERAL = 'general';

    public const QUEUE_ESCALATION = 'escalation';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_RETURNED = 'returned';

    public const STATUS_COMPLETED = 'completed';

    /**
     * @var list<string>
     */
    public const SUBJECT_TYPES = [
        self::SUBJECT_JOB,
        self::SUBJECT_REVIEW,
        self::SUBJECT_SUPPORT,
        self::SUBJECT_PATROL,
        self::SUBJECT_USER,
    ];

    /**
     * @var list<string>
     */
    public const QUEUES = [
        self::QUEUE_MODERATION,
        self::QUEUE_SUPPORT,
        self::QUEUE_PATROL,
        self::QUEUE_GENERAL,
        self::QUEUE_ESCALATION,
    ];

    protected $fillable = [
        'subject_type',
        'subject_id',
        'assignee_user_id',
        'referred_by_user_id',
        'note',
        'queue',
        'status',
        'referred_at',
        'acknowledged_at',
        'acknowledged_by_user_id',
        'returned_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'referred_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'returned_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_user_id');
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_by_user_id');
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by_user_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeSuperEscalations(Builder $query): Builder
    {
        return $query->where('queue', self::QUEUE_ESCALATION);
    }

    public function isSuperEscalation(): bool
    {
        return $this->queue === self::QUEUE_ESCALATION;
    }

    public function scopeForAssignee(Builder $query, User|int $user): Builder
    {
        $id = $user instanceof User ? $user->id : $user;

        return $query->where('assignee_user_id', $id);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
