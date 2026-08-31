<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class StaffIdleEvent extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'work_date',
        'started_at',
        'ended_at',
        'duration_seconds',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function close(Carbon $endedAt): void
    {
        if ($this->ended_at !== null) {
            return;
        }

        $duration = max(0, $endedAt->getTimestamp() - $this->started_at->getTimestamp());

        $this->forceFill([
            'ended_at' => $endedAt,
            'duration_seconds' => $duration,
        ])->save();
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }
}
