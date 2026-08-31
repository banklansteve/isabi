<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Support\Admin\AnnouncementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function read(Request $request, AnnouncementDelivery $delivery, AnnouncementService $announcements): RedirectResponse|JsonResponse
    {
        abort_unless($delivery->user_id === $request->user()->id, 403);

        if ($delivery->status !== AnnouncementDelivery::STATUS_READ) {
            $delivery->forceFill([
                'status' => AnnouncementDelivery::STATUS_READ,
                'read_at' => now(),
            ])->save();

            if ($delivery->announcement) {
                $announcements->refreshCounts($delivery->announcement);
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'notifications' => $announcements->inboxPayloadFor($request->user()),
            ]);
        }

        return back();
    }

    public function readAll(Request $request, AnnouncementService $announcements): RedirectResponse|JsonResponse
    {
        $ids = AnnouncementDelivery::query()
            ->where('user_id', $request->user()->id)
            ->where('channel', Announcement::CHANNEL_IN_APP)
            ->where('status', AnnouncementDelivery::STATUS_SENT)
            ->pluck('announcement_id')
            ->unique();

        AnnouncementDelivery::query()
            ->where('user_id', $request->user()->id)
            ->where('channel', Announcement::CHANNEL_IN_APP)
            ->where('status', AnnouncementDelivery::STATUS_SENT)
            ->update([
                'status' => AnnouncementDelivery::STATUS_READ,
                'read_at' => now(),
            ]);

        Announcement::query()
            ->whereIn('id', $ids)
            ->each(fn (Announcement $announcement) => $announcements->refreshCounts($announcement));

        if ($request->wantsJson()) {
            return response()->json([
                'notifications' => $announcements->inboxPayloadFor($request->user()),
            ]);
        }

        return back();
    }
}
