<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $fillable = [
        'question',
        'answer',
        'category',
        'sort_order',
        'is_published',
        'is_featured',
        'featured_sort',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'featured_sort' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true)
            ->where('is_published', true)
            ->orderByRaw('featured_sort is null')
            ->orderBy('featured_sort')
            ->orderBy('sort_order');
    }

    /**
     * @return array<string, mixed>
     */
    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'answer' => $this->answer,
            'category' => $this->category,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'answer' => $this->answer,
            'category' => $this->category,
            'sort_order' => $this->sort_order,
            'is_published' => $this->is_published,
            'is_featured' => $this->is_featured,
            'featured_sort' => $this->featured_sort,
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
