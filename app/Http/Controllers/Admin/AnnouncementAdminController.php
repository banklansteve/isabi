<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PreviewAnnouncementRequest;
use App\Http\Requests\Admin\StoreAnnouncementRequest;
use App\Http\Requests\Admin\StoreAnnouncementTemplateRequest;
use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Models\AnnouncementTemplate;
use App\Support\Admin\AdminAudit;
use App\Support\Admin\AnnouncementService;
use App\Support\NigeriaLocations;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementAdminController extends Controller
{
    public function index(Request $request, AnnouncementService $announcements): Response
    {
        $audience = (string) $request->query('audience', Announcement::AUDIENCE_USERS);
        if (! in_array($audience, [Announcement::AUDIENCE_USERS, Announcement::AUDIENCE_STAFF], true)) {
            $audience = Announcement::AUDIENCE_USERS;
        }

        $messages = Announcement::query()
            ->with('creator:id,name')
            ->latest('id')
            ->limit(400)
            ->get()
            ->map(fn (Announcement $item) => $this->listPayload($item, $announcements))
            ->values();

        return Inertia::render('Admin/Messaging/Index', [
            'messages' => $messages,
            'filters' => ['audience' => $audience],
        ]);
    }

    public function create(Request $request): Response
    {
        $audience = $request->query('audience') === Announcement::AUDIENCE_STAFF
            ? Announcement::AUDIENCE_STAFF
            : Announcement::AUDIENCE_USERS;

        return Inertia::render('Admin/Messaging/Create', [
            'audience' => $audience,
            'templates' => AnnouncementTemplate::query()
                ->orderBy('is_system', 'desc')
                ->orderBy('name')
                ->get()
                ->map(fn (AnnouncementTemplate $template) => $this->templatePayload($template)),
            'trades' => config('trades'),
            'locations' => NigeriaLocations::all(),
        ]);
    }

    public function store(StoreAnnouncementRequest $request, AnnouncementService $announcements): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $channels = $announcements->channelsFor($data['audience'], $data['channels']);
        $action = $data['action'];

        $announcement = Announcement::query()->create([
            'announcement_template_id' => $data['announcement_template_id'] ?? null,
            'audience' => $data['audience'],
            'title' => $data['title'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'channels' => $channels,
            'segment' => $this->cleanSegment($data['segment'] ?? []),
            'status' => $action === 'schedule' ? Announcement::STATUS_SCHEDULED : Announcement::STATUS_DRAFT,
            'send_at' => $action === 'schedule' ? $data['send_at'] : null,
            'created_by_user_id' => $request->user()->id,
        ]);

        if ($action === 'send') {
            $announcements->queue($announcement);
        }

        AdminAudit::record(
            $action === 'send' ? 'announcement.sent' : ($action === 'schedule' ? 'announcement.scheduled' : 'announcement.drafted'),
            "{$request->user()->name} {$action} announcement “{$announcement->title}”.",
            $announcement,
        );

        $toast = ['type' => 'success', ...match ($action) {
            'send' => ['title' => 'Sending', 'message' => 'Deliveries are being queued now.'],
            'schedule' => ['title' => 'Scheduled', 'message' => 'It will send at the time you picked.'],
            default => ['title' => 'Draft saved', 'message' => 'You can send or schedule it later.'],
        }];

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json([
                'ok' => true,
                'toast' => $toast,
                'id' => $announcement->id,
            ]);
        }

        return redirect()
            ->route('admin.messaging.show', $announcement)
            ->with('toast', $toast);
    }

    public function preview(PreviewAnnouncementRequest $request, AnnouncementService $announcements): JsonResponse
    {
        $data = $request->validated();

        return response()->json([
            'count' => $announcements->recipientCount($data['audience'], $this->cleanSegment($data['segment'] ?? [])),
        ]);
    }

    public function show(Announcement $announcement, AnnouncementService $announcements): Response
    {
        $announcement->load('creator:id,name');

        $channelStats = $announcement->deliveries()
            ->selectRaw('channel, status, COUNT(*) as total')
            ->groupBy('channel', 'status')
            ->get()
            ->groupBy('channel')
            ->map(function ($rows) {
                return $rows->mapWithKeys(fn ($row) => [$row->status => (int) $row->total]);
            });

        $deliveries = $announcement->deliveries()
            ->with('user:id,name,email,business_name')
            ->latest('id')
            ->paginate(30)
            ->through(fn (AnnouncementDelivery $delivery) => [
                'id' => $delivery->id,
                'channel' => $delivery->channel,
                'status' => $delivery->status,
                'error' => $delivery->error,
                'sent_at' => $delivery->sent_at?->diffForHumans(),
                'read_at' => $delivery->read_at?->diffForHumans(),
                'user' => [
                    'id' => $delivery->user?->id,
                    'name' => $delivery->user?->name,
                    'email' => $delivery->user?->email,
                ],
            ]);

        return Inertia::render('Admin/Messaging/Show', [
            'announcement' => [
                ...$this->listPayload($announcement, $announcements),
                'subject' => $announcement->subject,
                'body' => $announcement->body,
                'segment' => $announcement->segment ?? [],
                'channel_stats' => $channelStats,
            ],
            'deliveries' => $deliveries,
        ]);
    }

    public function send(Announcement $announcement, AnnouncementService $announcements, Request $request): RedirectResponse
    {
        abort_unless($announcement->isEditable(), 422, 'This announcement can no longer be sent.');

        $announcements->queue($announcement);

        AdminAudit::record(
            'announcement.sent',
            "{$request->user()->name} sent announcement “{$announcement->title}”.",
            $announcement,
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Sending',
            'message' => 'Deliveries are being queued now.',
        ]);
    }

    public function cancel(Announcement $announcement, Request $request): RedirectResponse
    {
        abort_unless($announcement->status === Announcement::STATUS_SCHEDULED, 422, 'Only scheduled messages can be cancelled.');

        $announcement->forceFill(['status' => Announcement::STATUS_CANCELLED])->save();

        AdminAudit::record(
            'announcement.cancelled',
            "{$request->user()->name} cancelled announcement “{$announcement->title}”.",
            $announcement,
        );

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Cancelled',
            'message' => 'This message will not send.',
        ]);
    }

    public function templates(): Response
    {
        return Inertia::render('Admin/Messaging/Templates', [
            'templates' => AnnouncementTemplate::query()
                ->orderBy('audience')
                ->orderBy('is_system', 'desc')
                ->orderBy('name')
                ->get()
                ->map(fn (AnnouncementTemplate $template) => $this->templatePayload($template)),
        ]);
    }

    public function storeTemplate(StoreAnnouncementTemplateRequest $request, AnnouncementService $announcements): RedirectResponse
    {
        $data = $request->validated();

        AnnouncementTemplate::query()->create([
            'audience' => $data['audience'],
            'name' => $data['name'],
            'slug' => $this->uniqueTemplateSlug($data['audience'], $data['name']),
            'subject' => $data['subject'],
            'body' => $data['body'],
            'channels' => $announcements->channelsFor($data['audience'], $data['channels']),
            'is_system' => false,
            'created_by_user_id' => $request->user()->id,
        ]);

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Template saved',
            'message' => 'You can reuse it the next time you compose.',
        ]);
    }

    public function updateTemplate(
        StoreAnnouncementTemplateRequest $request,
        AnnouncementTemplate $template,
        AnnouncementService $announcements,
    ): RedirectResponse {
        $data = $request->validated();

        $template->forceFill([
            'audience' => $template->is_system ? $template->audience : $data['audience'],
            'name' => $data['name'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'channels' => $announcements->channelsFor(
                $template->is_system ? $template->audience : $data['audience'],
                $data['channels'],
            ),
        ])->save();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Template updated',
            'message' => 'Changes apply to new messages only.',
        ]);
    }

    public function destroyTemplate(AnnouncementTemplate $template): RedirectResponse
    {
        abort_if($template->is_system, 422, 'System templates cannot be deleted.');

        $template->delete();

        return back()->with('toast', [
            'type' => 'success',
            'title' => 'Template removed',
            'message' => 'Existing messages that used it are unchanged.',
        ]);
    }

    /**
     * @param  array<string, mixed>  $segment
     * @return array<string, mixed>
     */
    private function cleanSegment(array $segment): array
    {
        return collect($segment)
            ->only(['plan', 'expiring_days', 'trade', 'state', 'lga', 'user_id', 'user_ids'])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->all();
    }

    private function uniqueTemplateSlug(string $audience, string $name): string
    {
        $base = Str::slug($audience.'-'.$name) ?: 'template';
        $slug = $base;
        $i = 1;

        while (AnnouncementTemplate::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /**
     * @return array<string, mixed>
     */
    private function listPayload(Announcement $announcement, AnnouncementService $announcements): array
    {
        return [
            'id' => $announcement->id,
            'audience' => $announcement->audience,
            'title' => $announcement->title,
            'status' => $announcement->status,
            'channels' => $announcement->channels ?? [],
            'segment_label' => $announcements->segmentLabel($announcement->audience, $announcement->segment ?? []),
            'recipient_count' => $announcement->recipient_count,
            'sent_count' => $announcement->sent_count,
            'failed_count' => $announcement->failed_count,
            'read_count' => $announcement->read_count,
            'send_at' => $announcement->send_at?->timezone(config('app.display_timezone'))->toDayDateTimeString(),
            'sent_at' => $announcement->sent_at?->timezone(config('app.display_timezone'))->toDayDateTimeString(),
            'created_at' => $announcement->created_at?->diffForHumans(),
            'created_iso' => $announcement->created_at?->toIso8601String(),
            'creator' => $announcement->creator?->name,
            'editable' => $announcement->isEditable(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function templatePayload(AnnouncementTemplate $template): array
    {
        return [
            'id' => $template->id,
            'audience' => $template->audience,
            'name' => $template->name,
            'slug' => $template->slug,
            'subject' => $template->subject,
            'body' => $template->body,
            'channels' => $template->channels ?? [],
            'is_system' => $template->is_system,
        ];
    }
}
