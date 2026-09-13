<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CareerVacancy extends Model
{
    public const STATUSES = ['draft', 'open', 'paused', 'closed', 'filled'];

    public const EMPLOYMENT_TYPES = ['full-time', 'part-time', 'contract', 'internship'];

    public const WORK_MODES = ['remote', 'hybrid', 'onsite'];

    protected $fillable = [
        'public_uid',
        'title',
        'department',
        'location',
        'employment_type',
        'work_mode',
        'status',
        'openings',
        'summary',
        'description',
        'requirements',
        'salary_min',
        'salary_max',
        'salary_currency',
        'salary_is_public',
        'is_public',
        'internal_notes',
        'apply_email',
        'apply_url',
        'sort_order',
        'is_published',
        'published_at',
        'closes_at',
        'hiring_manager_id',
        'staff_role_id',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_public' => 'boolean',
            'salary_is_public' => 'boolean',
            'sort_order' => 'integer',
            'openings' => 'integer',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
            'published_at' => 'datetime',
            'closes_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $vacancy): void {
            if (! $vacancy->public_uid) {
                $vacancy->public_uid = Str::lower(Str::random(12));
            }
        });
    }

    public function hiringManager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hiring_manager_id');
    }

    public function staffRole(): BelongsTo
    {
        return $this->belongsTo(StaffRole::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CareerApplication::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_public', true)
            ->where('status', 'open')
            ->where(function (Builder $q): void {
                $q->whereNull('closes_at')->orWhereDate('closes_at', '>=', now()->toDateString());
            })
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    public function isAcceptingApplications(): bool
    {
        if (! $this->is_public || $this->status !== 'open') {
            return false;
        }

        if ($this->closes_at && $this->closes_at->lt(now()->startOfDay())) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        $salary = null;
        if ($this->salary_is_public && ($this->salary_min || $this->salary_max)) {
            $salary = [
                'min' => $this->salary_min,
                'max' => $this->salary_max,
                'currency' => $this->salary_currency ?: 'NGN',
            ];
        }

        return [
            'id' => $this->id,
            'public_uid' => $this->public_uid,
            'title' => $this->title,
            'department' => $this->department,
            'location' => $this->location,
            'employment_type' => $this->employment_type,
            'work_mode' => $this->work_mode,
            'openings' => $this->openings,
            'summary' => $this->summary,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'salary' => $salary,
            'closes_at' => optional($this->closes_at)?->toDateString(),
            'published_at' => optional($this->published_at)?->toDateString(),
            'apply_email' => $this->apply_email ?: 'hello@kraftrack.com',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'public_uid' => $this->public_uid,
            'title' => $this->title,
            'department' => $this->department,
            'location' => $this->location,
            'employment_type' => $this->employment_type,
            'work_mode' => $this->work_mode,
            'status' => $this->status,
            'openings' => $this->openings,
            'applicants_count' => (int) ($this->applications_count ?? $this->applications()->count()),
            'summary' => $this->summary,
            'description' => $this->description,
            'requirements' => $this->requirements,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'salary_currency' => $this->salary_currency ?: 'NGN',
            'salary_is_public' => (bool) $this->salary_is_public,
            'is_public' => (bool) $this->is_public,
            'internal_notes' => $this->internal_notes,
            'apply_email' => $this->apply_email,
            'apply_url' => $this->apply_url,
            'sort_order' => $this->sort_order,
            'is_published' => (bool) $this->is_published,
            'published_at' => optional($this->published_at)?->toDateString(),
            'closes_at' => optional($this->closes_at)?->toDateString(),
            'hiring_manager_id' => $this->hiring_manager_id,
            'hiring_manager_name' => $this->hiringManager?->name,
            'staff_role_id' => $this->staff_role_id,
            'staff_role_name' => $this->staffRole?->name,
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
