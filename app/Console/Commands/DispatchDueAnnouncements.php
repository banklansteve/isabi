<?php

namespace App\Console\Commands;

use App\Jobs\SendAnnouncementDeliveryJob;
use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Support\Admin\AnnouncementService;
use Illuminate\Console\Command;

class DispatchDueAnnouncements extends Command
{
    protected $signature = 'announcements:dispatch';

    protected $description = 'Queue scheduled announcements that are due, and retry pending deliveries';

    public function handle(AnnouncementService $announcements): int
    {
        $due = Announcement::query()
            ->where('status', Announcement::STATUS_SCHEDULED)
            ->whereNotNull('send_at')
            ->where('send_at', '<=', now())
            ->get();

        foreach ($due as $announcement) {
            $announcements->queue($announcement);
            $this->line("Queued #{$announcement->id} — {$announcement->title}");
        }

        $pending = AnnouncementDelivery::query()
            ->where('status', AnnouncementDelivery::STATUS_PENDING)
            ->where('updated_at', '<=', now()->subMinutes(5))
            ->whereHas('announcement', fn ($query) => $query->where('status', Announcement::STATUS_SENDING))
            ->pluck('id');

        $pending->each(fn ($id) => SendAnnouncementDeliveryJob::dispatchSync((int) $id));

        $this->info("Due announcements: {$due->count()}. Pending deliveries dispatched: {$pending->count()}.");

        return self::SUCCESS;
    }
}
