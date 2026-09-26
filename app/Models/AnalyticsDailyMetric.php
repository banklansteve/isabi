<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsDailyMetric extends Model
{
    protected $fillable = [
        'metric_date',
        'dau',
        'wau',
        'mau',
        'page_my_page',
        'page_work_log',
        'page_credits',
        'page_help',
        'page_help_chat',
        'page_referrals',
        'page_credits_users',
        'purchase_users',
        'referral_signups',
        'export_users',
        'review_messages_users',
        'login_freq_0',
        'login_freq_1_3',
        'login_freq_4_10',
        'login_freq_11_plus',
        'active_users_30d',
    ];

    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
        ];
    }
}
