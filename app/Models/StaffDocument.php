<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class StaffDocument extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
        'expiry_date',
        'expiry_reminded_at',
        'uploaded_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'expiry_reminded_at' => 'datetime',
            'size' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Days until expiry, negative when already expired, null when no expiry set.
     */
    public function daysUntilExpiry(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return (int) round(Carbon::now()->startOfDay()->diffInDays($this->expiry_date->copy()->startOfDay(), false));
    }

    public function expiryState(): ?string
    {
        $days = $this->daysUntilExpiry();

        if ($days === null) {
            return null;
        }

        if ($days < 0) {
            return 'expired';
        }

        if ($days <= 30) {
            return 'expiring';
        }

        return 'valid';
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function typeOptions(): array
    {
        return [
            ['value' => 'contract', 'label' => 'Contract'],
            ['value' => 'offer_letter', 'label' => 'Offer letter'],
            ['value' => 'id', 'label' => 'ID / verification'],
            ['value' => 'policy', 'label' => 'Signed policy'],
            ['value' => 'certificate', 'label' => 'Certificate'],
            ['value' => 'other', 'label' => 'Other'],
        ];
    }
}
