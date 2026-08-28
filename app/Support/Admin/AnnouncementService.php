<?php

namespace App\Support\Admin;

use App\Enums\StaffStatus;
use App\Jobs\SendAnnouncementDeliveryJob;
use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AnnouncementService
{
    /**
     * @param  array<string, mixed>  $segment
     */
    public function recipients(string $audience, array $segment = []): Builder
    {
        if (! empty($segment['user_ids']) && is_array($segment['user_ids'])) {
            $ids = array_map('intval', $segment['user_ids']);

            if ($audience === Announcement::AUDIENCE_STAFF) {
                return User::query()->staff()->whereIn('id', $ids);
            }

            return User::query()->artisans()->whereIn('id', $ids);
        }

        if (! empty($segment['user_id'])) {
            $id = (int) $segment['user_id'];

            if ($audience === Announcement::AUDIENCE_STAFF) {
                return User::query()->staff()->where('id', $id);
            }

            return User::query()->artisans()->where('id', $id);
        }

        $query = User::query();

        if ($audience === Announcement::AUDIENCE_STAFF) {
            return $query->staff()->where('staff_status', StaffStatus::Active);
        }

        $query->artisans()->whereNull('suspended_at');

        $plan = $segment['plan'] ?? '';
        if ($plan === 'free') {
            $query->where(function ($builder) {
                $builder->whereNull('plan')->orWhere('plan', 'free');
            })->where(function ($builder) {
                $builder->whereNull('annual_expires_at')->orWhere('annual_expires_at', '<=', now());
            })->where('token_balance', '=', 0);
        } elseif ($plan === 'payg') {
            $query->where(function ($builder) {
                $builder->whereNull('plan')->orWhere('plan', '!=', 'annual');
            })->where(function ($builder) {
                $builder->whereNull('annual_expires_at')->orWhere('annual_expires_at', '<=', now());
            })->where('token_balance', '>', 0);
        } elseif ($plan === 'annual') {
            $query->where(function ($builder) {
                $builder->where('plan', 'annual')
                    ->orWhere(function ($inner) {
                        $inner->whereNotNull('annual_expires_at')->where('annual_expires_at', '>', now());
                    });
            });
        }

        if (! empty($segment['expiring_days'])) {
            $days = (int) $segment['expiring_days'];
            $query->whereNotNull('annual_expires_at')
                ->whereBetween('annual_expires_at', [now(), now()->addDays($days)]);
        }

        if (! empty($segment['trade'])) {
            $query->where('trade', $segment['trade']);
        }

        if (! empty($segment['state'])) {
            $query->where('state', $segment['state']);
        }

        if (! empty($segment['lga'])) {
            $query->where('lga', $segment['lga']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $segment
     */
    public function recipientCount(string $audience, array $segment = []): int
    {
        return $this->recipients($audience, $segment)->count();
    }

    /**
     * @return list<string>
     */
    public function channelsFor(string $audience, array $channels): array
    {
        $allowed = $audience === Announcement::AUDIENCE_STAFF
            ? [Announcement::CHANNEL_IN_APP, Announcement::CHANNEL_EMAIL]
            : [Announcement::CHANNEL_IN_APP, Announcement::CHANNEL_EMAIL, Announcement::CHANNEL_WHATSAPP];

        return array_values(array_unique(array_intersect($channels, $allowed)));
    }

    public function interpolate(string $text, User $user): string
    {
        $first = $user->first_name ?: strtok((string) $user->name, ' ') ?: $user->name;

        return strtr($text, [
            '{{first_name}}' => $first,
            '{{name}}' => (string) $user->name,
            '{{business_name}}' => (string) ($user->business_name ?: $user->name),
        ]);
    }

    public function queue(Announcement $announcement): void
    {
        $users = $this->recipients($announcement->audience, $announcement->segment ?? [])->get();
        $channels = $this->channelsFor(
            $announcement->audience,
            $announcement->channels ?: [Announcement::CHANNEL_IN_APP],
        );

        if ($channels === []) {
            $channels = [Announcement::CHANNEL_IN_APP];
        }

        foreach ($users as $user) {
            foreach ($channels as $channel) {
                AnnouncementDelivery::query()->firstOrCreate(
                    [
                        'announcement_id' => $announcement->id,
                        'user_id' => $user->id,
                        'channel' => $channel,
                    ],
                    ['status' => AnnouncementDelivery::STATUS_PENDING],
                );
            }
        }

        $announcement->forceFill([
            'status' => Announcement::STATUS_SENDING,
            'recipient_count' => $users->count(),
            'channels' => $channels,
        ])->save();

        $pendingIds = AnnouncementDelivery::query()
            ->where('announcement_id', $announcement->id)
            ->where('status', AnnouncementDelivery::STATUS_PENDING)
            ->pluck('id');

        if ($pendingIds->isEmpty()) {
            $announcement->forceFill([
                'status' => Announcement::STATUS_SENT,
                'sent_at' => now(),
            ])->save();

            return;
        }

        $pendingIds->each(fn ($id) => SendAnnouncementDeliveryJob::dispatchSync((int) $id));
    }

    public function refreshCounts(Announcement $announcement): void
    {
        $announcement->forceFill([
            'sent_count' => $announcement->deliveries()
                ->whereIn('status', [AnnouncementDelivery::STATUS_SENT, AnnouncementDelivery::STATUS_READ])
                ->count(),
            'failed_count' => $announcement->deliveries()
                ->where('status', AnnouncementDelivery::STATUS_FAILED)
                ->count(),
            'read_count' => $announcement->deliveries()
                ->where('status', AnnouncementDelivery::STATUS_READ)
                ->count(),
        ])->save();

        $pending = $announcement->deliveries()
            ->where('status', AnnouncementDelivery::STATUS_PENDING)
            ->exists();

        if (! $pending && $announcement->status === Announcement::STATUS_SENDING) {
            $announcement->forceFill([
                'status' => Announcement::STATUS_SENT,
                'sent_at' => now(),
            ])->save();
        }
    }

    /**
     * @return Collection<int, AnnouncementDelivery>
     */
    public function inboxFor(User $user, int $limit = 12)
    {
        return AnnouncementDelivery::query()
            ->with('announcement:id,title,subject,body,audience')
            ->where('user_id', $user->id)
            ->where('channel', Announcement::CHANNEL_IN_APP)
            ->whereIn('status', [AnnouncementDelivery::STATUS_SENT, AnnouncementDelivery::STATUS_READ])
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $segment
     */
    public function segmentLabel(string $audience, array $segment = []): string
    {
        if ($audience === Announcement::AUDIENCE_STAFF) {
            if (! empty($segment['user_ids']) && is_array($segment['user_ids'])) {
                return count($segment['user_ids']).' staff';
            }
            if (! empty($segment['user_id'])) {
                return '1 staff member';
            }

            return 'All active staff';
        }

        $parts = [];

        $plan = $segment['plan'] ?? '';
        if ($plan === 'free') {
            $parts[] = 'Free users';
        } elseif ($plan === 'payg') {
            $parts[] = 'Pay-as-you-go';
        } elseif ($plan === 'annual') {
            $parts[] = 'Annual plan';
        }

        if (! empty($segment['expiring_days'])) {
            $parts[] = 'Expires in '.(int) $segment['expiring_days'].' days';
        }

        if (! empty($segment['trade'])) {
            $parts[] = $segment['trade'];
        }

        if (! empty($segment['state'])) {
            $parts[] = $segment['state'];
        }

        if (! empty($segment['lga'])) {
            $parts[] = $segment['lga'];
        }

        if (! empty($segment['user_ids']) && is_array($segment['user_ids'])) {
            $parts[] = count($segment['user_ids']).' artisans';
        } elseif (! empty($segment['user_id'])) {
            $parts[] = '1 artisan';
        }

        return $parts === [] ? 'All artisans' : implode(' · ', $parts);
    }

    /**
     * @return array<string, mixed>
     */
    public function presentDelivery(AnnouncementDelivery $delivery, User $user): array
    {
        $message = $delivery->announcement;

        return [
            'id' => $delivery->id,
            'title' => $message
                ? $this->interpolate($message->subject ?: $message->title, $user)
                : 'Announcement',
            'body' => $message
                ? \Illuminate\Support\Str::limit($this->interpolate($message->body, $user), 90)
                : '',
            'time' => ($delivery->sent_at ?? $delivery->created_at)?->diffForHumans() ?? '',
            'icon' => $message?->audience === 'staff' ? 'ti ti-shield' : 'ti ti-megaphone',
            'unread' => $delivery->status === AnnouncementDelivery::STATUS_SENT,
        ];
    }

    public function unreadInAppCount(User $user): int
    {
        return AnnouncementDelivery::query()
            ->where('user_id', $user->id)
            ->where('channel', Announcement::CHANNEL_IN_APP)
            ->where('status', AnnouncementDelivery::STATUS_SENT)
            ->count();
    }

    /**
     * @param  list<int>  $userIds
     * @param  list<string>  $channels
     */
    public function sendToStaff(
        User $actor,
        array $userIds,
        string $subject,
        string $body,
        array $channels,
        ?int $templateId = null,
    ): Announcement {
        $ids = array_values(array_unique(array_map('intval', $userIds)));

        $announcement = Announcement::query()->create([
            'announcement_template_id' => $templateId,
            'audience' => Announcement::AUDIENCE_STAFF,
            'title' => $subject,
            'subject' => $subject,
            'body' => $body,
            'channels' => $this->channelsFor(Announcement::AUDIENCE_STAFF, $channels),
            'segment' => ['user_ids' => $ids],
            'status' => Announcement::STATUS_DRAFT,
            'created_by_user_id' => $actor->id,
        ]);

        $this->queue($announcement);

        return $announcement->fresh() ?? $announcement;
    }
}
