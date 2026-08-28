<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisciplinaryAppeal extends Model
{
    public const OUTCOME_UPHELD = 'upheld';

    public const OUTCOME_OVERTURNED = 'overturned';

    public const OUTCOME_MODIFIED = 'modified';

    protected $fillable = [
        'disciplinary_case_id',
        'disciplinary_action_id',
        'grounds',
        'raised_by',
        'raised_at',
        'outcome',
        'outcome_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raised_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryCase::class, 'disciplinary_case_id');
    }

    public function action(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryAction::class, 'disciplinary_action_id');
    }

    public function raiser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'raised_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isReviewed(): bool
    {
        return $this->reviewed_at !== null;
    }

    public function outcomeLabel(): ?string
    {
        if (! $this->outcome) {
            return null;
        }

        return (string) (config('discipline.appeal_outcomes')[$this->outcome] ?? $this->outcome);
    }
}
