<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsUserActivityDay extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'activity_date',
        'user_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
