<?php

namespace App\Support\Quotes;

use App\Mail\QuoteRequestArtisanAlertMail;
use App\Mail\QuoteRequestClientConfirmationMail;
use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Support\Admin\AnnouncementService;
use App\Support\Realtime\Realtime;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class QuoteRequestNotifier
{
    public const KIND = 'quote_request';

    public function __construct(
        private readonly AnnouncementService $announcements,
        private readonly Realtime $realtime,
    ) {}

    public function notify(QuoteRequest $request): void
    {
        $request->loadMissing(['artisan', 'workLog']);
        $artisan = $request->artisan;

        if (! $artisan) {
            return;
        }

        $this->notifyArtisanInApp($request, $artisan);
        $this->sendEmails($request, $artisan);
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

    private function sendEmails(QuoteRequest $request, User $artisan): void
    {
        if (filled($request->email)) {
            Mail::to($request->email)->send(new QuoteRequestClientConfirmationMail($request, $artisan));
        }

        if (filled($artisan->email)) {
            Mail::to($artisan->email)->send(new QuoteRequestArtisanAlertMail(
                $request,
                $artisan,
                route('quotes.show', $request),
            ));
        }
    }
}
