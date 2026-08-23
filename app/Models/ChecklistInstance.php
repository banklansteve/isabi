<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistInstance extends Model
{
    protected $fillable = [
        'user_id',
        'checklist_template_id',
        'kind',
        'created_by',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChecklistInstanceItem::class)->orderBy('sort_order');
    }

    public function progress(): int
    {
        $total = $this->items->count();

        if ($total === 0) {
            return 0;
        }

        return (int) round(($this->items->where('is_done', true)->count() / $total) * 100);
    }
}
