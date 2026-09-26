<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsRetentionCohort extends Model
{
    protected $fillable = [
        'cohort_month',
        'signed_up',
        'active_30',
        'active_60',
        'active_90',
        'rate_30',
        'rate_60',
        'rate_90',
    ];

    protected function casts(): array
    {
        return [
            'cohort_month' => 'date',
        ];
    }
}
