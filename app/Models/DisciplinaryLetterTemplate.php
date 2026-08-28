<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplinaryLetterTemplate extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'outcome_type',
        'body',
        'is_active',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function outcomeLabel(): string
    {
        return (string) (config('discipline.outcomes')[$this->outcome_type] ?? $this->outcome_type);
    }
}
