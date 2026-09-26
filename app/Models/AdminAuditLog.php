<?php

namespace App\Models;

use App\Enums\ActorKind;
use App\Enums\RetentionTier;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'actor_id',
    'actor_kind',
    'action',
    'retention_tier',
    'summary',
    'subject_type',
    'subject_id',
    'old_values',
    'new_values',
    'ip_address',
    'user_agent',
    'created_at',
])]
class AdminAuditLog extends Model
{
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actor_kind' => ActorKind::class,
            'retention_tier' => RetentionTier::class,
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
