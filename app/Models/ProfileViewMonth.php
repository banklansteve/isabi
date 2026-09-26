<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ProfileViewMonth extends Model
{
    protected $fillable = [
        'user_id',
        'month',
        'views',
        'search_views',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'date',
            'views' => 'integer',
            'search_views' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record one unique session view for the current calendar month.
     */
    public static function record(int $userId, bool $fromSearch): void
    {
        $month = now()->timezone(config('app.display_timezone'))->startOfMonth()->toDateString();

        $row = static::query()->firstOrCreate(
            ['user_id' => $userId, 'month' => $month],
            ['views' => 0, 'search_views' => 0],
        );

        $updates = ['views' => DB::raw('views + 1')];

        if ($fromSearch) {
            $updates['search_views'] = DB::raw('search_views + 1');
        }

        static::query()->whereKey($row->id)->update($updates);
    }

    /**
     * @return array{views: int, search_views: int, month_label: string}
     */
    public static function forUserThisMonth(int $userId): array
    {
        $month = now()->timezone(config('app.display_timezone'))->startOfMonth()->toDateString();

        $row = static::query()
            ->where('user_id', $userId)
            ->where('month', $month)
            ->first();

        return [
            'views' => (int) ($row?->views ?? 0),
            'search_views' => (int) ($row?->search_views ?? 0),
            'month_label' => now()
                ->timezone(config('app.display_timezone'))
                ->format('F'),
        ];
    }
}
