<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatrolCaseRule extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'patrol_case_id',
        'rule_key',
        'severity',
        'evidence',
        'detected_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'evidence' => 'array',
            'detected_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(PatrolCase::class, 'patrol_case_id');
    }

    public function label(): string
    {
        return config('patrol.rules.'.$this->rule_key.'.label')
            ?? config('patrol.review_rules.'.$this->rule_key.'.label')
            ?? $this->rule_key;
    }
}
