<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequestRequest;
use App\Models\QuoteRequest;
use App\Models\WorkLog;
use App\Support\ActivityLogger;
use App\Support\JobPublicLocator;
use App\Support\PublicArtisan;
use App\Support\Quotes\QuoteRequestNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicQuoteRequestController extends Controller
{
    public function store(
        StoreQuoteRequestRequest $request,
        string $slug,
        string $context,
        string $ref,
        QuoteRequestNotifier $notifier,
    ): JsonResponse|RedirectResponse {
        return $this->handle($request, $slug, $context, $ref, $notifier);
    }

    public function storeLegacy(
        StoreQuoteRequestRequest $request,
        string $slug,
        string $job,
        QuoteRequestNotifier $notifier,
    ): JsonResponse|RedirectResponse {
        return $this->handle($request, $slug, $job, null, $notifier);
    }

    private function handle(
        StoreQuoteRequestRequest $request,
        string $slug,
        string $contextOrJob,
        ?string $ref,
        QuoteRequestNotifier $notifier,
    ): JsonResponse|RedirectResponse {
        $artisan = PublicArtisan::locate($slug);

        if ($artisan instanceof RedirectResponse) {
            abort(404);
        }

        if (! $artisan->isPublicPageEnabled()) {
            abort(404);
        }

        $log = JobPublicLocator::findForArtisan((int) $artisan->id, $contextOrJob, $ref);

        if (! $log || ! $log->isPubliclyVisible()) {
            abort(404);
        }

        $data = $request->validated();

        $quote = QuoteRequest::query()->create([
            'work_log_id' => $log->id,
            'user_id' => $artisan->id,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'message' => $data['message'] ?? null,
            'status' => QuoteRequest::STATUS_NEW,
            'created_ip' => $request->ip(),
        ]);

        ActivityLogger::log(
            action: 'quote.requested',
            summary: "Quote request for “{$log->description}” from {$quote->name}.",
            user: $artisan,
            properties: [
                'quote_request_uid' => $quote->uid,
                'work_log_reference' => $log->reference,
                'phone' => $quote->phone,
            ],
        );

        $mailed = $notifier->notify($quote);

        if ($mailed['client'] && $mailed['artisan']) {
            $message = 'Thanks — we emailed you a confirmation and alerted '.$artisan->displayBusinessName().'.';
        } elseif ($mailed['client']) {
            $message = 'Thanks — we emailed you a confirmation. '.$artisan->displayBusinessName().' will follow up shortly.';
        } else {
            $message = 'Thanks — '.$artisan->displayBusinessName().' will reach out on the details you left.';
        }

        $toast = [
            'type' => 'success',
            'title' => 'Request sent',
            'message' => $message,
            'duration' => 5200,
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => $toast['message'],
                'toast' => $toast,
            ]);
        }

        return back()->with('toast', $toast);
    }
}
