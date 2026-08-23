<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChecklistInstanceItem extends Model
{
    protected $fillable = [
        'checklist_instance_id',
        'label',
        'is_done',
        'done_by',
        'done_at',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
            'done_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(ChecklistInstance::class, 'checklist_instance_id');
    }

    public function doneBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'done_by');
    }
}
