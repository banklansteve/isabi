<?php

namespace App\Support\Quotes;

use App\Mail\QuoteExpiryNudgeArtisanMail;
use App\Mail\QuoteExpiryNudgeClientMail;
use App\Models\QuoteRequest;
use Illuminate\Support\Facades\Mail;

class QuoteExpiryService
{
    /**
     * Expire awaiting quotes whose valid-until day has ended (or token has lapsed).
     */
    public function expireDue(): int
    {
        $count = 0;

        QuoteRequest::query()
            ->where('status', QuoteRequest::STATUS_AWAITING_CLIENT)
            ->with('artisanQuote')
            ->orderBy('id')
            ->chunkById(100, function ($requests) use (&$count): void {
                foreach ($requests as $request) {
                    if (! $request->isOfferExpired()) {
                        continue;
                    }

                    $request->forceFill(['status' => QuoteRequest::STATUS_EXPIRED])->save();
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Email both parties when a quote is within the looming window (once).
     */
    public function sendLoomingNudges(): int
    {
        $windowDays = max(1, (int) config('profiles.quote_expiry_nudge_days', 3));
        $today = now()->startOfDay();
        $horizon = $today->copy()->addDays($windowDays);
        $sent = 0;

        QuoteRequest::query()
            ->where('status', QuoteRequest::STATUS_AWAITING_CLIENT)
            ->whereNull('expiry_nudge_sent_at')
            ->whereHas('artisanQuote', function ($query) use ($today, $horizon): void {
                $query->whereDate('valid_until', '>=', $today->toDateString())
                    ->whereDate('valid_until', '<=', $horizon->toDateString());
            })
            ->with(['artisanQuote', 'artisan'])
            ->orderBy('id')
            ->chunkById(50, function ($requests) use (&$sent): void {
                foreach ($requests as $request) {
                    if ($this->nudge($request)) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    public function nudge(QuoteRequest $request): bool
    {
        $request->loadMissing(['artisanQuote', 'artisan']);

        if ($request->status !== QuoteRequest::STATUS_AWAITING_CLIENT) {
            return false;
        }

        if ($request->expiry_nudge_sent_at) {
            return false;
        }

        $quote = $request->artisanQuote;
        $artisan = $request->artisan;

        if (! $quote?->valid_until || ! $artisan) {
            return false;
        }

        if ($request->isOfferExpired()) {
            return false;
        }

        $daysLeft = (int) now()->startOfDay()->diffInDays($quote->valid_until->startOfDay(), false);

        if ($daysLeft < 1) {
            return false;
        }

        $publicUrl = filled($request->client_token)
            ? QuoteDelivery::publicUrl($request)
            : null;
        $builderUrl = route('quotes.show', $request);

        if (filled($request->email) && $publicUrl) {
            Mail::to($request->email)->send(new QuoteExpiryNudgeClientMail(
                $request,
                $quote,
                $artisan,
                $publicUrl,
                $daysLeft,
            ));
        }

        if (filled($artisan->email)) {
            Mail::to($artisan->email)->send(new QuoteExpiryNudgeArtisanMail(
                $request,
                $quote,
                $artisan,
                $builderUrl,
                $publicUrl,
                $daysLeft,
            ));
        }

        $request->forceFill(['expiry_nudge_sent_at' => now()])->save();

        return true;
    }
}
