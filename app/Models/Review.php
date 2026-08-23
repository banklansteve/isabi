<?php

namespace App\Models;

use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Review extends Model
{
    protected $fillable = [
        'uid',
        'work_log_id',
        'user_id',
        'rating',
        'would_recommend',
        'comment',
        'client_display_name',
        'referred_by',
        'photo_disk',
        'photo_path',
        'photo_url',
        'submitter_ip_hash',
        'user_agent',
        'submitted_at',
        'flagged_at',
        'flag_reason',
        'hidden_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'float',
            'would_recommend' => 'boolean',
            'submitted_at' => 'datetime',
            'flagged_at' => 'datetime',
            'hidden_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Review $review): void {
            if (blank($review->uid)) {
                $review->uid = (string) Str::uuid();
            }
            if ($review->submitted_at === null) {
                $review->submitted_at = now();
            }
        });
    }

    public function workLog(): BelongsTo
    {
        return $this->belongsTo(WorkLog::class);
    }

    public function artisan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function photoUrl(): ?string
    {
        return $this->photo_url ?: null;
    }

    public function photoThumbUrl(int $width = 900): ?string
    {
        return MediaUrl::image($this->photoUrl(), $width);
    }

    public function photoPreviewUrl(int $width = 1600): ?string
    {
        return MediaUrl::image($this->photoUrl(), $width);
    }
}
