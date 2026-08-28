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
use Illuminate\Support\Str;

class StaffChatNotifier
{
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
        $preview = filled($message->body)
            ? Str::limit(trim((string) $message->body), 120)
            : ($message->attachment_name ? 'Sent an attachment' : 'New message');

        $title = $conversation->isAsap()
            ? 'ASAP'
            : ($sender->first_name ?: Str::before($sender->name, ' '));

        $href = route('admin.asap.show', $conversation);

        foreach ($conversation->participants as $participant) {
            if ((int) $participant->id === (int) $sender->id) {
                continue;
            }

            $announcement = Announcement::query()->create([
                'audience' => Announcement::AUDIENCE_STAFF,
                'title' => $title,
                'subject' => $title,
                'body' => "**{$sender->name}:** {$preview}",
                'channels' => [Announcement::CHANNEL_IN_APP],
                'segment' => [
                    'user_id' => $participant->id,
                    'href' => $href,
                    'staff_conversation_uid' => $conversation->uid,
                ],
                'status' => Announcement::STATUS_SENT,
                'sent_at' => now(),
                'recipient_count' => 1,
                'sent_count' => 1,
                'created_by_user_id' => $sender->id,
            ]);

            $delivery = AnnouncementDelivery::query()->create([
                'announcement_id' => $announcement->id,
                'user_id' => $participant->id,
                'channel' => Announcement::CHANNEL_IN_APP,
                'status' => AnnouncementDelivery::STATUS_SENT,
                'sent_at' => now(),
            ]);

            $item = $this->announcements->presentDelivery($delivery->load('announcement'), $participant);
            $item['href'] = $href;

            $this->realtime->notification(
                $participant,
                $item,
                $this->announcements->unreadInAppCount($participant),
            );
        }
    }

    public function markConversationNotificationsRead(User $user, StaffConversation $conversation): void
    {
        if (! Schema::hasTable('announcement_deliveries') || ! Schema::hasTable('announcements')) {
            return;
        }

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
