<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'key',
    'value',
    'type',
    'group',
    'label',
    'description',
    'config_key',
    'is_custom',
    'updated_by_user_id',
])]
class AppSetting extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_custom' => 'boolean',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function typedValue(): mixed
    {
        $raw = $this->value;

        return match ($this->type) {
            'boolean' => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'integer' => $raw === null || $raw === '' ? null : (int) $raw,
            'json' => $raw === null || $raw === '' ? null : json_decode($raw, true),
            default => $raw,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'value' => $this->typedValue(),
            'type' => $this->type,
            'group' => $this->group,
            'label' => $this->label,
            'description' => $this->description,
            'config_key' => $this->config_key,
            'is_custom' => $this->is_custom,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
