<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceNote extends Model
{
    public const RATING_EXCEEDING = 'exceeding';

    public const RATING_MEETING = 'meeting';

    public const RATING_NEEDS_IMPROVEMENT = 'needs_improvement';

    protected $fillable = [
        'user_id',
        'author_id',
        'rating',
        'body',
        'noted_on',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'noted_on' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return list<string>
     */
    public static function ratings(): array
    {
        return [self::RATING_EXCEEDING, self::RATING_MEETING, self::RATING_NEEDS_IMPROVEMENT];
    }
}
