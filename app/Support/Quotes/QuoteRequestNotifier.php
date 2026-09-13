<?php

namespace App\Support\Quotes;

use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Support\Admin\AnnouncementService;
use App\Support\Realtime\Realtime;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class QuoteRequestNotifier
{
    public const KIND = 'quote_request';

    public function __construct(
        private readonly AnnouncementService $announcements,
        private readonly Realtime $realtime,
        private readonly QuoteMailer $mailer,
    ) {}

    /**
     * @return array{client: bool, artisan: bool}
     */
    public function notify(QuoteRequest $request): array
    {
        $request->loadMissing(['artisan', 'workLog']);
        $artisan = $request->artisan;

        if (! $artisan) {
            Log::warning('quote.request.notify_skipped', [
                'quote_request_uid' => $request->uid,
                'reason' => 'missing_artisan',
            ]);

            return ['client' => false, 'artisan' => false];
        }

        // Emails first — never blocked by in-app notification failures.
        $mailed = $this->mailer->sendRequestNotifications($request, $artisan);

        Log::info('quote.request.notify_complete', [
            'quote_request_uid' => $request->uid,
            'mailed_client' => $mailed['client'],
            'mailed_artisan' => $mailed['artisan'],
        ]);

        try {
            $this->notifyArtisanInApp($request, $artisan);
        } catch (Throwable $e) {
            report($e);
            Log::error('quote.request.in_app_failed', [
                'quote_request_uid' => $request->uid,
                'message' => $e->getMessage(),
            ]);
        }

        return $mailed;
    }

    private function notifyArtisanInApp(QuoteRequest $request, User $artisan): void
    {
        if (! Schema::hasTable('announcements') || ! Schema::hasTable('announcement_deliveries')) {
            return;
        }

        $jobLabel = $request->workLog?->description ?: 'your page';
        $title = 'Quote request from '.$request->name;
        $body = $request->message
            ? Str::limit($request->message, 120)
            : 'Someone asked for a quote on '.$jobLabel.'.';
        $href = route('quotes.show', $request);

        $announcement = Announcement::query()->create([
            'audience' => Announcement::AUDIENCE_USERS,
            'title' => $title,
            'subject' => $title,
            'body' => $body,
            'channels' => [Announcement::CHANNEL_IN_APP],
            'segment' => [
                'kind' => self::KIND,
                'user_id' => $artisan->id,
                'href' => $href,
                'quote_request_uid' => $request->uid,
            ],
            'status' => Announcement::STATUS_SENT,
            'sent_at' => now(),
            'recipient_count' => 1,
            'sent_count' => 1,
        ]);

        $delivery = AnnouncementDelivery::query()->create([
            'announcement_id' => $announcement->id,
            'user_id' => $artisan->id,
            'channel' => Announcement::CHANNEL_IN_APP,
            'status' => AnnouncementDelivery::STATUS_SENT,
            'sent_at' => now(),
        ]);

        $item = $this->announcements->presentDelivery($delivery->load('announcement'), $artisan);
        $item['href'] = $href;
        $item['icon'] = 'ti ti-file-invoice';

        $this->realtime->notification(
            $artisan,
            $item,
            $this->announcements->unreadInAppCount($artisan),
        );
    }
}
