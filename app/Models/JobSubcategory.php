<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobSubcategory extends Model
{
    protected $fillable = [
        'job_category_id',
        'name',
        'review_phrase',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(JobCategory::class, 'job_category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'job_category_id' => $this->job_category_id,
            'name' => $this->name,
            'review_phrase' => $this->review_phrase,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];
    }
}
