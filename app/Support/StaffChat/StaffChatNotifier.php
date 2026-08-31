<?php

namespace App\Support\StaffChat;

use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Models\StaffConversation;
use App\Models\StaffMessage;
use App\Models\User;
use App\Support\Admin\AnnouncementService;
use App\Support\Realtime\Realtime;
use Illuminate\Support\Facades\Schema;

class StaffChatNotifier
{
    public const KIND_ASAP = 'staff_chat_asap';

    public const KIND_DIRECT = 'staff_chat_direct';

    public function __construct(
        private readonly AnnouncementService $announcements,
        private readonly Realtime $realtime,
    ) {}

    public function notifyNewMessage(StaffConversation $conversation, StaffMessage $message, User $sender): void
    {
        if (! Schema::hasTable('announcements') || ! Schema::hasTable('announcement_deliveries')) {
            return;
        }

        $conversation->loadMissing('participants');

        foreach ($conversation->participants as $participant) {
            if ((int) $participant->id === (int) $sender->id) {
                continue;
            }

            if ($conversation->isAsap()) {
                $this->upsertAsapNotification($participant, $conversation, $sender);
                continue;
            }

            $this->upsertDirectNotification($participant, $conversation, $sender);
        }
    }

    public function markConversationNotificationsRead(User $user, StaffConversation $conversation): void
    {
        if (! Schema::hasTable('announcement_deliveries') || ! Schema::hasTable('announcements')) {
            return;
        }

        if ($conversation->isAsap()) {
            $this->markKindRead($user, self::KIND_ASAP);
        } else {
            $this->markKindRead($user, self::KIND_DIRECT);
        }

        $this->markLegacyConversationNotificationsRead($user, $conversation);
    }

    private function upsertAsapNotification(User $recipient, StaffConversation $conversation, User $sender): void
    {
        $existing = $this->openChatAnnouncement($recipient, self::KIND_ASAP);
        $count = ((int) data_get($existing?->segment, 'count', 0)) + 1;
        $href = $conversation->adminShowUrl();
        $title = 'ASAP Message('.$count.')';

        $this->persistChatNotification(
            recipient: $recipient,
            existing: $existing,
            title: $title,
            body: 'New messages in the ASAP group chat.',
            href: $href,
            segment: [
                'kind' => self::KIND_ASAP,
                'user_id' => $recipient->id,
                'href' => $href,
                'staff_conversation_uid' => $conversation->uid,
                'count' => $count,
                'last_sender_id' => $sender->id,
            ],
            sender: $sender,
            icon: 'ti ti-bolt',
        );
    }

    private function upsertDirectNotification(User $recipient, StaffConversation $conversation, User $sender): void
    {
        $existing = $this->openChatAnnouncement($recipient, self::KIND_DIRECT);
        $senderIds = collect(data_get($existing?->segment, 'sender_ids', []))
            ->map(fn ($id) => (int) $id)
            ->push((int) $sender->id)
            ->unique()
            ->values()
            ->all();
        $count = ((int) data_get($existing?->segment, 'count', 0)) + 1;
        $title = $this->directTitle($senderIds, $sender, $count);
        $href = count($senderIds) === 1
            ? $conversation->adminShowUrl()
            : route('admin.asap.index');

        $this->persistChatNotification(
            recipient: $recipient,
            existing: $existing,
            title: $title,
            body: count($senderIds) === 1
                ? 'New direct message from '.$sender->name.'.'
                : 'New messages from ops teammates.',
            href: $href,
            segment: [
                'kind' => self::KIND_DIRECT,
                'user_id' => $recipient->id,
                'href' => $href,
                'staff_conversation_uid' => count($senderIds) === 1 ? $conversation->uid : null,
                'count' => $count,
                'sender_ids' => $senderIds,
                'last_sender_id' => $sender->id,
            ],
            sender: $sender,
            icon: 'ti ti-message',
        );
    }

    /**
     * @param  list<int>  $senderIds
     */
    private function directTitle(array $senderIds, User $latestSender, int $count): string
    {
        if (count($senderIds) <= 1) {
            $name = trim((string) $latestSender->name) ?: 'Ops';

            return 'Message from '.$name.'('.$count.')';
        }

        return 'Ops Messages('.$count.')';
    }

    /**
     * @param  array<string, mixed>  $segment
     */
    private function persistChatNotification(
        User $recipient,
        ?Announcement $existing,
        string $title,
        string $body,
        string $href,
        array $segment,
        User $sender,
        string $icon,
    ): void {
        if ($existing) {
            $existing->forceFill([
                'title' => $title,
                'subject' => $title,
                'body' => $body,
                'segment' => $segment,
                'sent_at' => now(),
                'created_by_user_id' => $sender->id,
            ])->save();

            $delivery = AnnouncementDelivery::query()
                ->where('announcement_id', $existing->id)
                ->where('user_id', $recipient->id)
                ->where('channel', Announcement::CHANNEL_IN_APP)
                ->first();

            if ($delivery) {
                $delivery->forceFill([
                    'status' => AnnouncementDelivery::STATUS_SENT,
                    'sent_at' => now(),
                    'read_at' => null,
                ])->save();
            } else {
                $delivery = AnnouncementDelivery::query()->create([
                    'announcement_id' => $existing->id,
                    'user_id' => $recipient->id,
                    'channel' => Announcement::CHANNEL_IN_APP,
                    'status' => AnnouncementDelivery::STATUS_SENT,
                    'sent_at' => now(),
                ]);
            }

            $item = $this->announcements->presentDelivery($delivery->load('announcement'), $recipient);
            $item['href'] = $href;
            $item['icon'] = $icon;

            $this->realtime->notification(
                $recipient,
                $item,
                $this->announcements->unreadInAppCount($recipient),
            );

            return;
        }

        $announcement = Announcement::query()->create([
            'audience' => Announcement::AUDIENCE_STAFF,
            'title' => $title,
            'subject' => $title,
            'body' => $body,
            'channels' => [Announcement::CHANNEL_IN_APP],
            'segment' => $segment,
            'status' => Announcement::STATUS_SENT,
            'sent_at' => now(),
            'recipient_count' => 1,
            'sent_count' => 1,
            'created_by_user_id' => $sender->id,
        ]);

        $delivery = AnnouncementDelivery::query()->create([
            'announcement_id' => $announcement->id,
            'user_id' => $recipient->id,
            'channel' => Announcement::CHANNEL_IN_APP,
            'status' => AnnouncementDelivery::STATUS_SENT,
            'sent_at' => now(),
        ]);

        $item = $this->announcements->presentDelivery($delivery->load('announcement'), $recipient);
        $item['href'] = $href;
        $item['icon'] = $icon;

        $this->realtime->notification(
            $recipient,
            $item,
            $this->announcements->unreadInAppCount($recipient),
        );
    }

    private function openChatAnnouncement(User $recipient, string $kind): ?Announcement
    {
        return Announcement::query()
            ->where('audience', Announcement::AUDIENCE_STAFF)
            ->where('segment->kind', $kind)
            ->where('segment->user_id', $recipient->id)
            ->whereHas('deliveries', function ($query) use ($recipient) {
                $query->where('user_id', $recipient->id)
                    ->where('channel', Announcement::CHANNEL_IN_APP)
                    ->where('status', AnnouncementDelivery::STATUS_SENT);
            })
            ->latest('id')
            ->first();
    }

    private function markKindRead(User $user, string $kind): void
    {
        $ids = Announcement::query()
            ->where('audience', Announcement::AUDIENCE_STAFF)
            ->where('segment->kind', $kind)
            ->where('segment->user_id', $user->id)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        AnnouncementDelivery::query()
            ->where('user_id', $user->id)
            ->where('channel', Announcement::CHANNEL_IN_APP)
            ->whereIn('announcement_id', $ids)
            ->where('status', AnnouncementDelivery::STATUS_SENT)
            ->update([
                'status' => AnnouncementDelivery::STATUS_READ,
                'read_at' => now(),
            ]);
    }

    private function markLegacyConversationNotificationsRead(User $user, StaffConversation $conversation): void
    {
        $ids = Announcement::query()
            ->where('audience', Announcement::AUDIENCE_STAFF)
            ->where('segment->staff_conversation_uid', $conversation->uid)
            ->where('segment->user_id', $user->id)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        AnnouncementDelivery::query()
            ->where('user_id', $user->id)
            ->where('channel', Announcement::CHANNEL_IN_APP)
            ->whereIn('announcement_id', $ids)
            ->where('status', AnnouncementDelivery::STATUS_SENT)
            ->update([
                'status' => AnnouncementDelivery::STATUS_READ,
                'read_at' => now(),
            ]);
    }
}
