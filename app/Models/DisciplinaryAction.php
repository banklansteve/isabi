<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DisciplinaryAction extends Model
{
    public const TYPE_VERBAL = 'verbal_warning';

    public const TYPE_WRITTEN = 'written_warning';

    public const TYPE_FINAL = 'final_written_warning';

    public const TYPE_SUSPENSION = 'suspension';

    public const TYPE_TERMINATION = 'termination';

    public const TYPE_NO_ACTION = 'no_action';

    protected $fillable = [
        'disciplinary_case_id',
        'type',
        'justification',
        'suspension_starts_on',
        'suspension_ends_on',
        'letter_subject',
        'letter_body',
        'template_id',
        'issued_by',
        'issued_at',
        'acknowledged_at',
        'response_body',
        'response_submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'suspension_starts_on' => 'date',
            'suspension_ends_on' => 'date',
            'issued_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'response_submitted_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryCase::class, 'disciplinary_case_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DisciplinaryLetterTemplate::class, 'template_id');
    }

    public function appeals(): HasMany
    {
        return $this->hasMany(DisciplinaryAppeal::class);
    }

    public function typeLabel(): string
    {
        return (string) (config('discipline.outcomes')[$this->type] ?? $this->type);
    }

    public function isSerious(): bool
    {
        return in_array($this->type, config('discipline.serious_outcomes', []), true);
    }

    public function isAcknowledged(): bool
    {
        return $this->acknowledged_at !== null;
    }

    public function hasResponse(): bool
    {
        return $this->response_submitted_at !== null;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function typeOptions(): array
    {
        return collect(config('discipline.outcomes', []))
            ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }
}
