<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplinaryCaseEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'disciplinary_case_id',
        'actor_id',
        'type',
        'reason',
        'confidential',
        'payload',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'confidential' => 'boolean',
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryCase::class, 'disciplinary_case_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function typeLabel(): string
    {
        return (string) (config('discipline.event_types')[$this->type] ?? $this->type);
    }
}
