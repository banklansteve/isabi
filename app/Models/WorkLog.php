<?php

namespace App\Models;

use App\Support\WorkLogEditPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class WorkLog extends Model
{
    protected $fillable = [
        'user_id',
        'uid',
        'description',
        'worked_on',
        'client_name',
        'job_category',
        'job_subcategory',
        'job_review_phrase',
        'service_state',
        'service_lga',
        'service_city',
        'client_whatsapp',
        'amount_charged',
        'review_requested_at',
        'review_token',
        'review_token_expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'worked_on' => 'date',
            'amount_charged' => 'integer',
            'review_requested_at' => 'datetime',
            'review_token_expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (WorkLog $log): void {
            if (blank($log->uid)) {
                $log->uid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(WorkLogMedia::class)->orderBy('sort_order');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function amountInNaira(): ?float
    {
        if ($this->amount_charged === null) {
            return null;
        }

        return $this->amount_charged / 100;
    }

    /**
     * @return array<string, mixed>
     */
    public function editFlags(): array
    {
        return WorkLogEditPolicy::flags($this);
    }
}
