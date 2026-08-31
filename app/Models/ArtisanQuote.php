<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ArtisanQuote extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    protected $fillable = [
        'uid',
        'quote_request_id',
        'user_id',
        'quote_number',
        'valid_until',
        'estimated_start',
        'estimated_duration_days',
        'scope_of_work',
        'line_items',
        'notes',
        'terms',
        'payment_terms',
        'subtotal_kobo',
        'vat_rate',
        'vat_kobo',
        'discount_kobo',
        'total_kobo',
        'status',
        'sent_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valid_until' => 'date',
            'estimated_start' => 'date',
            'line_items' => 'array',
            'sent_at' => 'datetime',
            'vat_rate' => 'float',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ArtisanQuote $quote): void {
            if (blank($quote->uid)) {
                $quote->uid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uid';
    }

    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function artisan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function totalInNaira(): float
    {
        return $this->total_kobo / 100;
    }
}
