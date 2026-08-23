<?php

namespace App\Jobs;

use App\Mail\AnnouncementMail;
use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Models\User;
use App\Support\Admin\AnnouncementService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendAnnouncementDeliveryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $deliveryId) {}

    public function handle(AnnouncementService $announcements): void
    {
        $delivery = AnnouncementDelivery::query()
            ->with(['announcement', 'user'])
            ->find($this->deliveryId);

        if (! $delivery || $delivery->status !== AnnouncementDelivery::STATUS_PENDING) {
            return;
        }

        $announcement = $delivery->announcement;
        $user = $delivery->user;

        if (! $announcement || ! $user) {
            $this->failDelivery($delivery, 'Missing announcement or recipient.', $announcements);

            return;
        }

        try {
            match ($delivery->channel) {
                Announcement::CHANNEL_EMAIL => $this->sendEmail($announcement, $user, $announcements),
                Announcement::CHANNEL_WHATSAPP => $this->sendWhatsapp($user),
                default => null,
            };

            $delivery->forceFill([
                'status' => AnnouncementDelivery::STATUS_SENT,
                'sent_at' => now(),
                'error' => null,
            ])->save();
        } catch (Throwable $e) {
            $this->failDelivery($delivery, $e->getMessage(), $announcements);

            return;
        }

        $announcements->refreshCounts($announcement);
    }

    private function sendEmail(Announcement $announcement, User $user, AnnouncementService $announcements): void
    {
        Mail::to($user->email)->send(new AnnouncementMail(
            announcement: $announcement,
            recipient: $user,
            subjectLine: $announcements->interpolate($announcement->subject, $user),
            bodyText: $announcements->interpolate($announcement->body, $user),
        ));
    }

    private function sendWhatsapp(User $user): void
    {
        if (! filled($user->whatsapp)) {
            throw new \RuntimeException('No WhatsApp number on file');
        }

        throw new \RuntimeException('WhatsApp provider not configured');
    }

    private function failDelivery(
        AnnouncementDelivery $delivery,
        string $error,
        AnnouncementService $announcements,
    ): void {
        $delivery->forceFill([
            'status' => AnnouncementDelivery::STATUS_FAILED,
            'failed_at' => now(),
            'error' => mb_substr($error, 0, 250),
        ])->save();

        if ($delivery->announcement) {
            $announcements->refreshCounts($delivery->announcement);
        }
    }
}
