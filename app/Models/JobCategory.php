<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobCategory extends Model
{
    protected $fillable = [
        'name',
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

    public function subcategories(): HasMany
    {
        return $this->hasMany(JobSubcategory::class)->orderBy('sort_order')->orderBy('name');
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
            'name' => $this->name,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'subcategories_count' => $this->subcategories_count
                ?? $this->subcategories()->count(),
            'subcategories' => $this->relationLoaded('subcategories')
                ? $this->subcategories->map(fn (JobSubcategory $sub) => $sub->toAdminArray())->values()->all()
                : [],
        ];
    }
}
