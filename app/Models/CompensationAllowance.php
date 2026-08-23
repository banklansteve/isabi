<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompensationAllowance extends Model
{
    protected $fillable = [
        'compensation_record_id',
        'label',
        'amount',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function record(): BelongsTo
    {
        return $this->belongsTo(CompensationRecord::class, 'compensation_record_id');
    }
}
