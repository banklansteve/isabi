<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'summary',
        'properties',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function icon(): string
    {
        return match (true) {
            str_starts_with($this->action, 'auth.login') => 'ti ti-login',
            str_starts_with($this->action, 'auth.logout') => 'ti ti-logout',
            str_starts_with($this->action, 'auth.register') => 'ti ti-user-plus',
            str_starts_with($this->action, 'profile') => 'ti ti-user-circle',
            str_starts_with($this->action, 'password') => 'ti ti-lock',
            str_starts_with($this->action, 'cookie') => 'ti ti-cookie',
            str_starts_with($this->action, 'page.') => 'ti ti-eye',
            str_starts_with($this->action, 'work_log') => 'ti ti-briefcase',
            str_starts_with($this->action, 'work') => 'ti ti-notebook',
            str_starts_with($this->action, 'credit') => 'ti ti-wallet',
            str_starts_with($this->action, 'referral') => 'ti ti-gift',
            default => 'ti ti-activity',
        };
    }

    /**
     * Compact feed item for the user dashboard.
     *
     * @return array{id: int, icon: string, title: string, body: string, time: string}
     */
    public function toFeedItem(): array
    {
        return [
            'id' => $this->id,
            'icon' => $this->icon(),
            'title' => $this->titleFromAction(),
            'body' => $this->summary,
            'time' => $this->created_at?->diffForHumans() ?? '',
        ];
    }

    public function titleFromAction(): string
    {
        return match ($this->action) {
            'auth.login' => 'Signed in',
            'auth.logout' => 'Signed out',
            'auth.register' => 'Account created',
            'profile.updated' => 'Profile updated',
            'profile.deleted' => 'Account deleted',
            'password.updated' => 'Password changed',
            'cookie.accepted' => 'Cookies accepted',
            'cookie.rejected' => 'Cookies rejected',
            'page.my_page' => 'Viewed my page',
            'page.work_log' => 'Opened work log',
            'page.credits' => 'Opened credits & plan',
            'page.referrals' => 'Opened referrals',
            'page.help' => 'Opened help',
            'page.dashboard' => 'Opened home',
            'work_log.created' => 'Job logged',
            default => str($this->action)->replace('.', ' · ')->headline()->toString(),
        };
    }
}

