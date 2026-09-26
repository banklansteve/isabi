<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobSlugRedirect extends Model
{
    protected $fillable = [
        'from_path',
        'work_log_id',
        'user_id',
    ];

    public function workLog(): BelongsTo
    {
        return $this->belongsTo(WorkLog::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function remember(string $fromPath, WorkLog $log): void
    {
        $fromPath = strtolower(trim($fromPath));
        $canonical = $log->publicPathSegment();

        if ($fromPath === '' || $fromPath === $canonical || blank($log->id)) {
            return;
        }

        static::query()->updateOrCreate(
            ['from_path' => $fromPath],
            [
                'work_log_id' => $log->id,
                'user_id' => $log->user_id,
            ],
        );
    }
}
