<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientTokensException;
use App\Http\Requests\StoreWorkLogRequest;
use App\Http\Requests\UpdateWorkLogRequest;
use App\Models\QuoteRequest;
use App\Models\WorkLog;
use App\Models\WorkLogMedia;
use App\Services\CloudinaryMediaService;
use App\Support\ActivityLogger;
use App\Support\JobCategories;
use App\Support\NigeriaLocations;
use App\Support\Referrals\ReferralService;
use App\Support\ReviewInvite;
use App\Support\Tokens\ReviewLinkGate;
use App\Support\WorkLogEditPolicy;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Throwable;

class WorkLogController extends Controller
{
    public function __construct(
        private readonly CloudinaryMediaService $cloudinary,
        private readonly ReviewLinkGate $reviewLinkGate,
        private readonly ReferralService $referrals,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        ActivityLogger::log(
            action: 'page.work_log',
            summary: "{$user->name} opened Work log.",
            user: $user,
        );

        // Full list for client-side search / filter / sort (no reload round-trips).
        $entries = WorkLog::query()
            ->where('user_id', $user->id)
            ->with(['media', 'review'])
            ->orderByDesc('worked_on')
            ->orderByDesc('id')
            ->get()
            ->map(fn (WorkLog $log) => [
                'id' => $log->id,
                'uid' => $log->uid,
                'reference' => $log->reference,
                'public_url' => $log->publicUrl(),
                'embed_url' => filled($log->reference) && filled($user->slug)
                    ? route('embed.job', [$user->slug, $log->reference])
                    : null,
                'description' => $log->description,
                'worked_on' => $log->worked_on?->toDateString(),
                'worked_on_label' => $log->worked_on?->timezone(config('app.display_timezone'))->format('j M Y'),
                'client_name' => $log->client_name,
                'job_category' => $log->job_category,
                'job_subcategory' => $log->job_subcategory,
                'category_label' => JobCategories::displayLabel($log->job_category, $log->job_subcategory),
                'service_label' => self::serviceLabel($log),
                'client_whatsapp' => $log->client_whatsapp,
                'amount_naira' => $log->amountInNaira(),
                'media_count' => $log->media->count(),
                'thumbnail' => $log->media->first()?->thumbUrl(600),
                'review_requested' => WorkLogEditPolicy::hasReviewRequested($log),
                'has_review' => $log->relationLoaded('review')
                    ? $log->review !== null
                    : $log->review()->exists(),
                'reminder_due' => $log->setRelation('user', $user)->reminderDue(),
                'reminder_sent' => $log->review_reminder_sent_at !== null,
            ])
            ->values();

        return Inertia::render('WorkLog/Index', [
            'entries' => $entries,
            'maxLookbackDays' => StoreWorkLogRequest::MAX_LOOKBACK_DAYS,
            'dueReminderCount' => $entries->where('reminder_due', true)->count(),
        ]);
    }

    /**
     * Download the artisan's full job log + reviews as a PDF for tenders.
     */
    public function export(Request $request)
    {
        $user = $request->user();

        $logs = WorkLog::query()
            ->where('user_id', $user->id)
            ->with('review')
            ->orderByDesc('worked_on')
            ->orderByDesc('id')
            ->get();

        ActivityLogger::log(
            action: 'work_log.exported',
            summary: "{$user->name} exported their work log as PDF.",
            user: $user,
            properties: ['jobs' => $logs->count()],
        );

        $pdf = Pdf::loadView('pdf.work-log-export', [
            'user' => $user,
            'logs' => $logs,
            'brandLogo' => $user->brandLogoUrl(),
            'generatedAt' => now()->timezone(config('app.display_timezone')),
            'publicUrl' => $user->publicUrl(),
        ])->setPaper('a4');

        $filename = Str::slug($user->displayBusinessName() ?: 'references').'-references-'.now()->format('Y-m-d').'.pdf';

        return $pdf->download($filename);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $defaults = [
            'service_state' => $user->state,
            'service_lga' => $user->lga,
        ];

        $fromQuote = null;
        if ($request->filled('quote')) {
            $fromQuote = QuoteRequest::query()
                ->where('uid', $request->query('quote'))
                ->where('user_id', $user->id)
                ->where('status', QuoteRequest::STATUS_ACCEPTED)
                ->with('artisanQuote')
                ->first();
        }

        if ($fromQuote) {
            $defaults['description'] = $fromQuote->subject
                ?: ($fromQuote->artisanQuote?->scope_of_work ?: $fromQuote->message);
            $defaults['client_name'] = $fromQuote->name;
            $defaults['client_whatsapp'] = $fromQuote->phone;
        }

        return Inertia::render('WorkLog/Create', [
            'maxLookbackDays' => StoreWorkLogRequest::MAX_LOOKBACK_DAYS,
            'today' => now()->toDateString(),
            'minDate' => now()->subDays(StoreWorkLogRequest::MAX_LOOKBACK_DAYS)->toDateString(),
            'jobCategories' => JobCategories::forFrontend(),
            'locations' => NigeriaLocations::all(),
            'defaults' => $defaults,
            'fromQuote' => $fromQuote ? [
                'uid' => $fromQuote->uid,
                'subject' => $fromQuote->displayTitle(),
            ] : null,
        ]);
    }

    public function store(StoreWorkLogRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        try {
            $workLog = DB::transaction(function () use ($request, $user, $data) {
                $workLog = WorkLog::create([
                    'user_id' => $user->id,
                    'created_ip' => $request->ip(),
                    'description' => $data['description'],
                    'worked_on' => $data['worked_on'],
                    'client_name' => $data['client_name'] ?? null,
                    'job_category' => $data['job_category'] ?? null,
                    'job_subcategory' => $data['job_subcategory'] ?? null,
                    'job_review_phrase' => JobCategories::reviewPhrase(
                        $data['job_category'] ?? null,
                        $data['job_subcategory'] ?? null,
                    ),
                    'service_state' => $data['service_state'] ?? null,
                    'service_lga' => $data['service_lga'] ?? null,
                    'service_city' => $data['service_city'] ?? null,
                    'client_whatsapp' => $data['client_whatsapp'] ?? null,
                    'amount_charged' => $request->amountInKobo(),
                ]);

                $this->storeMedia($request, $workLog);

                return $workLog;
            });
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'media' => $e->getMessage(),
            ]);
        }

        ActivityLogger::log(
            action: 'work_log.created',
            summary: "{$user->name} logged a job: “{$workLog->description}”.",
            user: $user,
            properties: [
                'work_log_id' => $workLog->id,
                'worked_on' => $workLog->worked_on?->toDateString(),
                'job_category' => $workLog->job_category,
                'job_subcategory' => $workLog->job_subcategory,
                'has_whatsapp' => filled($workLog->client_whatsapp),
                'media_count' => $workLog->media()->count(),
            ],
        );

        $this->referrals->qualifyOnFirstJob($user->fresh());

        if ($request->filled('from_quote_uid')) {
            QuoteRequest::query()
                ->where('uid', $request->input('from_quote_uid'))
                ->where('user_id', $user->id)
                ->where('status', QuoteRequest::STATUS_ACCEPTED)
                ->whereNull('logged_work_log_id')
                ->update(['logged_work_log_id' => $workLog->id]);
        }

        return redirect()
            ->route('work-log.show', $workLog)
            ->with('toast', [
                'type' => 'success',
                'title' => 'Job logged',
                'message' => 'Your proof trail just got stronger.',
                'duration' => 5000,
            ]);
    }

    public function show(Request $request, WorkLog $workLog): Response
    {
        abort_unless((int) $workLog->user_id === (int) $request->user()->id, 403);

        $workLog->load(['media', 'review', 'user']);
        $flags = $workLog->editFlags();
        $hasReview = $workLog->review !== null;
        $user = $request->user();
        $reviewInvite = ReviewInvite::payload(
            $workLog,
            $user->first_name ?: $user->displayBusinessName(),
        );

        return Inertia::render('WorkLog/Show', [
            'entry' => [
                'uid' => $workLog->uid,
                'reference' => $workLog->reference,
                'slug' => $workLog->slug,
                'public_url' => $workLog->publicUrl(),
                'embed_url' => filled($workLog->reference) && filled($workLog->user?->slug)
                    ? route('embed.job', [$workLog->user->slug, $workLog->reference])
                    : null,
                'description' => $workLog->description,
                'worked_on' => $workLog->worked_on?->toDateString(),
                'worked_on_label' => $workLog->worked_on?->timezone(config('app.display_timezone'))->format('l, j F Y'),
                'worked_on_short' => $workLog->worked_on?->timezone(config('app.display_timezone'))->format('j M Y'),
                'client_name' => $workLog->client_name,
                'job_category' => $workLog->job_category,
                'job_subcategory' => $workLog->job_subcategory,
                'category_label' => JobCategories::displayLabel($workLog->job_category, $workLog->job_subcategory),
                'service_state' => $workLog->service_state,
                'service_lga' => $workLog->service_lga,
                'service_city' => $workLog->service_city,
                'service_label' => self::serviceLabel($workLog),
                'client_whatsapp' => $workLog->client_whatsapp,
                'amount_naira' => $workLog->amountInNaira(),
                'created_at_label' => $workLog->created_at?->timezone(config('app.display_timezone'))->format('j M Y · g:i A'),
                'created_at_short' => $workLog->created_at?->timezone(config('app.display_timezone'))->format('j M Y'),
                'review_requested' => WorkLogEditPolicy::hasReviewRequested($workLog),
                'review_requested_short' => $workLog->review_requested_at
                    ?->timezone(config('app.display_timezone'))
                    ->format('j M Y'),
                'review_requested_ago' => $workLog->review_requested_at
                    ?->timezone(config('app.display_timezone'))
                    ->diffForHumans(),
                'has_review' => $hasReview,
                'reminder_due' => $workLog->reminderDue(),
                'reminder_sent' => $workLog->review_reminder_sent_at !== null,
                'reminder_sent_short' => $workLog->review_reminder_sent_at
                    ?->timezone(config('app.display_timezone'))
                    ->format('j M Y'),
                'review' => $hasReview ? [
                    'rating' => (float) $workLog->review->rating,
                    'would_recommend' => $workLog->review->would_recommend,
                    'comment' => $workLog->review->comment,
                    'client_display_name' => $workLog->review->client_display_name,
                    'referred_by' => $workLog->review->referred_by,
                    'photo_url' => $workLog->review->photoUrl(),
                    'photo_thumb_url' => $workLog->review->photoThumbUrl(700),
                    'photo_preview_url' => $workLog->review->photoPreviewUrl(),
                    'submitted_at_label' => $workLog->review->submitted_at
                        ?->timezone(config('app.display_timezone'))
                        ->format('j M Y'),
                ] : null,
                'media' => $workLog->media->map(fn (WorkLogMedia $m) => [
                    'id' => $m->id,
                    'url' => $m->url(),
                    'thumb_url' => $m->thumbUrl(700),
                    'preview_url' => $m->previewUrl(1600),
                    'poster_url' => $m->posterUrl(800),
                    'kind' => $m->kind,
                    'original_name' => $m->original_name,
                ])->values(),
            ],
            'editFlags' => $flags,
            'reviewInvite' => $reviewInvite,
            'reviewQuota' => $this->reviewLinkGate->status($request->user()),
            'whatsappShare' => $request->session()->pull('whatsapp_share'),
            'openReviewShare' => (bool) $request->session()->pull('open_review_share'),
        ]);
    }

    public function requestReview(Request $request, WorkLog $workLog): RedirectResponse
    {
        return $this->openWhatsAppShare(
            $request,
            $workLog,
            kind: ReviewInvite::KIND_INVITE,
            activity: 'review.requested',
            summary: 'requested a client review',
            toast: 'Review link ready — tap Open WhatsApp to send.',
        );
    }

    /**
     * One-shot reminder nudge — same WhatsApp click-to-chat flow as the first invite.
     */
    public function remindReview(Request $request, WorkLog $workLog): RedirectResponse
    {
        abort_unless((int) $workLog->user_id === (int) $request->user()->id, 403);

        if ($workLog->review()->exists()) {
            return redirect()
                ->route('work-log.show', $workLog)
                ->with('toast', [
                    'type' => 'info',
                    'message' => 'This job already has a client review.',
                    'duration' => 4500,
                ]);
        }

        if ($workLog->review_reminder_sent_at) {
            return redirect()
                ->route('work-log.show', $workLog)
                ->with('toast', [
                    'type' => 'info',
                    'message' => 'You’ve already sent the reminder for this job.',
                    'duration' => 4500,
                ]);
        }

        if (! $workLog->review_requested_at) {
            return redirect()
                ->route('work-log.show', $workLog)
                ->with('toast', [
                    'type' => 'info',
                    'message' => 'Send the first review link before a reminder.',
                    'duration' => 4500,
                ]);
        }

        $workLog->forceFill(['review_reminder_sent_at' => now()])->save();

        return $this->openWhatsAppShare(
            $request,
            $workLog->fresh(['user']),
            kind: ReviewInvite::KIND_REMINDER,
            activity: 'review.reminder_prepared',
            summary: 'prepared a review reminder',
            toast: 'Reminder ready — tap Open WhatsApp to nudge your client.',
        );
    }

    private function openWhatsAppShare(
        Request $request,
        WorkLog $workLog,
        string $kind,
        string $activity,
        string $summary,
        string $toast,
    ): RedirectResponse {
        abort_unless((int) $workLog->user_id === (int) $request->user()->id, 403);

        if ($workLog->review()->exists()) {
            return redirect()
                ->route('work-log.show', $workLog)
                ->with('toast', [
                    'type' => 'info',
                    'message' => 'This job already has a client review.',
                    'duration' => 4500,
                ]);
        }

        $user = $request->user();

        if ($kind === ReviewInvite::KIND_INVITE && blank($workLog->review_requested_at)) {
            try {
                $this->reviewLinkGate->authorizeNewRequest($user, $workLog);
            } catch (InsufficientTokensException $e) {
                return redirect()
                    ->route('tokens.buy')
                    ->with('toast', [
                        'type' => 'error',
                        'message' => $e->getMessage(),
                        'duration' => 6500,
                    ]);
            }
        }

        $workLog = ReviewInvite::ensureToken($workLog->loadMissing('user'));
        $payload = ReviewInvite::payload(
            $workLog,
            $user->first_name ?: $user->displayBusinessName(),
            $kind,
        );

        ActivityLogger::log(
            action: $activity,
            summary: "{$user->name} {$summary}.",
            user: $user,
            properties: [
                'work_log_uid' => $workLog->uid,
                'kind' => $kind,
            ],
        );

        return redirect()
            ->route('work-log.show', $workLog)
            ->with('whatsapp_share', [
                'url' => $payload['whatsapp_app_url'],
                'whatsapp_app_url' => $payload['whatsapp_app_url'],
                'whatsapp_protocol_url' => $payload['whatsapp_protocol_url'],
                'whatsapp_web_url' => $payload['whatsapp_web_url'],
                'review_url' => $payload['review_url'],
                'message' => $payload['message'],
                'kind' => $kind,
            ])
            ->with('open_review_share', true)
            ->with('toast', [
                'type' => 'success',
                'message' => $toast,
                'duration' => 5000,
            ]);
    }

    public function edit(Request $request, WorkLog $workLog): Response|RedirectResponse
    {
        abort_unless((int) $workLog->user_id === (int) $request->user()->id, 403);

        if (! WorkLogEditPolicy::canOpenEditor($workLog)) {
            return redirect()
                ->route('work-log.show', $workLog)
                ->with('toast', [
                    'type' => 'error',
                    'message' => 'This job is outside the edit window.',
                    'duration' => 4500,
                ]);
        }

        $workLog->load('media');
        $flags = $workLog->editFlags();

        return Inertia::render('WorkLog/Edit', [
            'entry' => [
                'uid' => $workLog->uid,
                'description' => $workLog->description,
                'worked_on' => $workLog->worked_on?->toDateString(),
                'client_name' => $workLog->client_name,
                'job_category' => $workLog->job_category,
                'job_subcategory' => $workLog->job_subcategory,
                'service_state' => $workLog->service_state,
                'service_lga' => $workLog->service_lga,
                'service_city' => $workLog->service_city,
                'client_whatsapp' => $workLog->client_whatsapp,
                'amount_charged' => $workLog->amountInNaira(),
                'media' => $workLog->media->map(fn (WorkLogMedia $m) => [
                    'id' => $m->id,
                    'url' => $m->url(),
                    'thumb_url' => $m->thumbUrl(700),
                    'preview_url' => $m->previewUrl(1600),
                    'poster_url' => $m->posterUrl(800),
                    'kind' => $m->kind,
                    'original_name' => $m->original_name,
                ])->values(),
            ],
            'editFlags' => $flags,
            'maxLookbackDays' => StoreWorkLogRequest::MAX_LOOKBACK_DAYS,
            'today' => now()->toDateString(),
            'minDate' => now()->subDays(StoreWorkLogRequest::MAX_LOOKBACK_DAYS)->toDateString(),
            'jobCategories' => JobCategories::forFrontend(),
            'locations' => NigeriaLocations::all(),
        ]);
    }

    public function update(UpdateWorkLogRequest $request, WorkLog $workLog): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        try {
            DB::transaction(function () use ($request, $workLog, $data) {
                $payload = [
                    'client_name' => $data['client_name'] ?? null,
                    'job_category' => $data['job_category'] ?? null,
                    'job_subcategory' => $data['job_subcategory'] ?? null,
                    'job_review_phrase' => JobCategories::reviewPhrase(
                        $data['job_category'] ?? null,
                        $data['job_subcategory'] ?? null,
                    ),
                    'service_state' => $data['service_state'] ?? null,
                    'service_lga' => $data['service_lga'] ?? null,
                    'service_city' => $data['service_city'] ?? null,
                    'client_whatsapp' => $data['client_whatsapp'] ?? null,
                    'amount_charged' => $request->amountInKobo(),
                ];

                if (WorkLogEditPolicy::canEditDescription($workLog) && array_key_exists('description', $data)) {
                    $payload['description'] = $data['description'];
                }

                if (WorkLogEditPolicy::canEditDate($workLog) && array_key_exists('worked_on', $data)) {
                    $payload['worked_on'] = $data['worked_on'];
                }

                $workLog->update($payload);

                $removeIds = array_map('intval', $request->input('remove_media', []) ?: []);
                if ($removeIds !== []) {
                    $media = $workLog->media()->whereIn('id', $removeIds)->get();
                    foreach ($media as $item) {
                        $this->deleteMediaFile($item);
                        $item->delete();
                    }
                }

                $this->storeMedia($request, $workLog->fresh());
            });
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'media' => $e->getMessage(),
            ]);
        }

        ActivityLogger::log(
            action: 'work_log.updated',
            summary: "{$user->name} updated a job log.",
            user: $user,
            properties: [
                'work_log_id' => $workLog->id,
            ],
        );

        return redirect()
            ->route('work-log.show', $workLog)
            ->with('toast', [
                'type' => 'success',
                'title' => 'Job updated',
                'message' => 'Changes are saved to this work log entry.',
                'duration' => 4500,
            ]);
    }

    private function storeMedia(Request $request, WorkLog $workLog): void
    {
        $files = array_values(array_filter($request->file('media', []) ?: []));
        if ($files === []) {
            return;
        }

        $startOrder = (int) $workLog->media()->max('sort_order');

        foreach ($files as $index => $file) {
            if (! $file) {
                continue;
            }

            $uploaded = $this->cloudinary->uploadWorkLogMedia(
                $file,
                (int) $workLog->user_id,
                (int) $workLog->id,
            );

            $mime = (string) ($uploaded['mime_type'] ?: $file->getMimeType());
            $kind = ($uploaded['resource_type'] === 'video' || str_starts_with($mime, 'video/'))
                ? 'video'
                : 'image';

            WorkLogMedia::create([
                'work_log_id' => $workLog->id,
                'disk' => WorkLogMedia::DISK_CLOUDINARY,
                'path' => $uploaded['public_id'],
                'cdn_url' => $uploaded['url'],
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $mime,
                'kind' => $kind,
                'size' => $uploaded['bytes'],
                'sort_order' => $startOrder + $index + 1,
            ]);
        }
    }

    private function deleteMediaFile(WorkLogMedia $item): void
    {
        if ($item->disk === WorkLogMedia::DISK_CLOUDINARY) {
            $this->cloudinary->delete(
                $item->path,
                $item->isVideo() ? 'video' : 'image',
            );

            return;
        }

        try {
            Storage::disk($item->disk)->delete($item->path);
        } catch (Throwable) {
            // Ignore local cleanup failures for legacy rows.
        }
    }

    private static function serviceLabel(WorkLog $log): ?string
    {
        $parts = array_filter([
            $log->service_city,
            $log->service_lga,
            $log->service_state,
        ]);

        return $parts === [] ? null : implode(', ', $parts);
    }
}
