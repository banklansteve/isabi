<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CareerVacancy extends Model
{
    protected $fillable = [
        'title',
        'department',
        'location',
        'employment_type',
        'summary',
        'description',
        'apply_email',
        'apply_url',
        'sort_order',
        'is_published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'sort_order' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'department' => $this->department,
            'location' => $this->location,
            'employment_type' => $this->employment_type,
            'summary' => $this->summary,
            'description' => $this->description,
            'apply_email' => $this->apply_email ?: 'hello@kraftrack.com',
            'apply_url' => $this->apply_url,
            'published_at' => optional($this->published_at)?->toDateString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'department' => $this->department,
            'location' => $this->location,
            'employment_type' => $this->employment_type,
            'summary' => $this->summary,
            'description' => $this->description,
            'apply_email' => $this->apply_email,
            'apply_url' => $this->apply_url,
            'sort_order' => $this->sort_order,
            'is_published' => $this->is_published,
            'published_at' => optional($this->published_at)?->toIso8601String(),
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
