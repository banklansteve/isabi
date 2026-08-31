<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuoteRequestRequest;
use App\Models\QuoteRequest;
use App\Support\ActivityLogger;
use App\Support\PublicArtisan;
use App\Support\Quotes\QuoteRequestNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicProfileQuoteController extends Controller
{
    public function store(
        StoreQuoteRequestRequest $request,
        string $slug,
        QuoteRequestNotifier $notifier,
    ): JsonResponse|RedirectResponse {
        $artisan = PublicArtisan::locate($slug);

        if ($artisan instanceof RedirectResponse) {
            abort(404);
        }

        $data = $request->validated();

        $quote = QuoteRequest::query()->create([
            'work_log_id' => null,
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
            summary: "Quote request from {$quote->name} via public page.",
            user: $artisan,
            properties: [
                'quote_request_uid' => $quote->uid,
                'phone' => $quote->phone,
            ],
        );

        $notifier->notify($quote);

        $toast = [
            'type' => 'success',
            'title' => 'Request sent',
            'message' => 'Thanks — '.$artisan->displayBusinessName().' will reach out on the details you left.',
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
