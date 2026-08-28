<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisciplinaryCase extends Model
{
    public const STATUS_REPORTED = 'reported';

    public const STATUS_INVESTIGATING = 'investigating';

    public const STATUS_DECISION_PENDING = 'decision_pending';

    public const STATUS_ACTION_ISSUED = 'action_issued';

    public const STATUS_APPEALED = 'appealed';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'reference',
        'user_id',
        'owner_id',
        'opened_by',
        'category',
        'category_label',
        'severity',
        'incident_on',
        'description',
        'status',
        'archived_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'incident_on' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function opener(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(DisciplinaryNote::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(DisciplinaryEvidence::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(DisciplinaryAction::class);
    }

    public function appeals(): HasMany
    {
        return $this->hasMany(DisciplinaryAppeal::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(DisciplinaryCaseEvent::class);
    }

    public function latestAction(): ?DisciplinaryAction
    {
        return $this->actions()->orderByDesc('issued_at')->orderByDesc('id')->first();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query
            ->whereNull('archived_at')
            ->whereNotIn('status', [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }

    public function isOpen(): bool
    {
        return $this->archived_at === null
            && ! in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED], true);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function categoryLabel(): string
    {
        if ($this->category === 'other' && $this->category_label) {
            return $this->category_label;
        }

        return (string) (config('discipline.categories')[$this->category] ?? $this->category_label ?: $this->category);
    }

    public function severityLabel(): string
    {
        return (string) (config('discipline.severities')[$this->severity] ?? $this->severity);
    }

    public function statusLabel(): string
    {
        return (string) (config('discipline.statuses')[$this->status] ?? $this->status);
    }

    /**
     * @return list<string>
     */
    public function allowedTransitions(): array
    {
        return config('discipline.transitions')[$this->status] ?? [];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function statusOptions(): array
    {
        return self::optionList('statuses');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function severityOptions(): array
    {
        return self::optionList('severities');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function categoryOptions(): array
    {
        return self::optionList('categories');
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private static function optionList(string $key): array
    {
        return collect(config("discipline.{$key}", []))
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }
}
