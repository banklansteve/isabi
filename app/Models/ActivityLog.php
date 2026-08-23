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
            'staff.invited' => 'Staff invited',
            str_starts_with($this->action, 'staff') => 'ti ti-users',
            str_starts_with($this->action, 'admin.settings') => 'ti ti-settings',
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
            'auth.admin_login' => 'Admin signed in',
            'auth.admin_logout' => 'Admin signed out',
            'auth.register' => 'Account created',
            'staff.invited' => 'Staff invited',
            'staff.invite_resent' => 'Invite code resent',
            'staff.email_verified' => 'Staff email confirmed',
            'staff.password_set' => 'Staff password set',
            'staff.duties_assigned' => 'Staff duties updated',
            'staff.updated' => 'Staff updated',
            'staff.destroyed' => 'Staff removed',
            'staff.role_created' => 'Duty created',
            'staff.role_updated' => 'Duty updated',
            'staff.role_destroyed' => 'Duty removed',
            'admin.settings_updated' => 'Settings updated',
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
            'page.help_chat' => 'Opened support chat',
            'page.dashboard' => 'Opened home',
            'work_log.created' => 'Job logged',
            default => str($this->action)->replace('.', ' · ')->headline()->toString(),
        };
    }
}
