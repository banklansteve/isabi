<?php

namespace App\Support\Quotes;

use App\Mail\QuoteDeliveredClientMail;
use App\Mail\QuoteRequestArtisanAlertMail;
use App\Mail\QuoteRequestClientConfirmationMail;
use App\Mail\QuoteSentArtisanMail;
use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Throwable;

class QuoteMailer
{
    /**
     * Client confirmation + artisan alert after a new quote request.
     *
     * @return array{client: bool, artisan: bool}
     */
    public function sendRequestNotifications(QuoteRequest $request, User $artisan): array
    {
        $request->loadMissing(['workLog']);

        $client = false;
        $artisanSent = false;

        if ($this->validEmail($request->email)) {
            $client = $this->attempt(
                'quote.request.client',
                (string) $request->email,
                fn () => $this->dispatch(
                    (string) $request->email,
                    new QuoteRequestClientConfirmationMail($request, $artisan)
                ),
                ['quote_request_uid' => $request->uid],
            );
        } else {
            Log::warning('quote.request.client.skipped', [
                'quote_request_uid' => $request->uid,
                'reason' => 'missing_or_invalid_client_email',
                'email' => $request->email,
            ]);
        }

        if ($this->validEmail($artisan->email)) {
            $artisanSent = $this->attempt(
                'quote.request.artisan',
                (string) $artisan->email,
                fn () => $this->dispatch(
                    (string) $artisan->email,
                    new QuoteRequestArtisanAlertMail(
                        $request,
                        $artisan,
                        route('quotes.show', $request, absolute: true),
                    )
                ),
                ['quote_request_uid' => $request->uid],
            );
        } else {
            Log::warning('quote.request.artisan.skipped', [
                'quote_request_uid' => $request->uid,
                'reason' => 'missing_or_invalid_artisan_email',
                'email' => $artisan->email,
            ]);
        }

        return ['client' => $client, 'artisan' => $artisanSent];
    }

    /**
     * Client delivery + artisan confirmation after a quote is sent.
     *
     * @return array{client: bool, artisan: bool}
     */
    public function sendQuoteDelivery(
        QuoteRequest $request,
        ArtisanQuote $quote,
        User $artisan,
        ?string $publicUrl = null,
    ): array {
        $request->loadMissing(['artisanQuote']);

        $publicUrl = $publicUrl ?: QuoteDelivery::publicUrl($request);
        $pdfUrl = QuoteDelivery::pdfUrl($request);
        $builderUrl = route('quotes.show', $request, absolute: true);
        $pdfBytes = $this->safePdfBytes($request, $quote, $artisan);
        $pdfName = app(QuotePdfService::class)->filename($quote, $artisan);

        $client = false;
        $artisanSent = false;

        if ($this->validEmail($request->email)) {
            $client = $this->attempt(
                'quote.delivered.client',
                (string) $request->email,
                fn () => $this->dispatch(
                    (string) $request->email,
                    new QuoteDeliveredClientMail(
                        $request,
                        $quote,
                        $artisan,
                        $publicUrl,
                        $pdfUrl,
                        $pdfBytes,
                        $pdfName,
                    )
                ),
                [
                    'quote_request_uid' => $request->uid,
                    'quote_number' => $quote->quote_number,
                    'quote_url' => $publicUrl,
                    'pdf_url' => $pdfUrl,
                    'has_pdf' => $pdfBytes !== null,
                ],
            );
        } else {
            Log::warning('quote.delivered.client.skipped', [
                'quote_request_uid' => $request->uid,
                'reason' => 'missing_or_invalid_client_email',
                'email' => $request->email,
            ]);
        }

        if ($this->validEmail($artisan->email)) {
            $artisanSent = $this->attempt(
                'quote.sent.artisan',
                (string) $artisan->email,
                fn () => $this->dispatch(
                    (string) $artisan->email,
                    new QuoteSentArtisanMail(
                        $request,
                        $quote,
                        $artisan,
                        $publicUrl,
                        $pdfUrl,
                        $builderUrl,
                        $pdfBytes,
                        $pdfName,
                    )
                ),
                [
                    'quote_request_uid' => $request->uid,
                    'quote_number' => $quote->quote_number,
                    'quote_url' => $publicUrl,
                    'pdf_url' => $pdfUrl,
                    'has_pdf' => $pdfBytes !== null,
                ],
            );
        } else {
            Log::warning('quote.sent.artisan.skipped', [
                'quote_request_uid' => $request->uid,
                'reason' => 'missing_or_invalid_artisan_email',
                'email' => $artisan->email,
            ]);
        }

        return ['client' => $client, 'artisan' => $artisanSent];
    }

    private function dispatch(string $to, object $mailable): ?SentMessage
    {
        $sent = $this->mailer()->to($to)->send($mailable);

        // Mail::fake() returns null even when the mailable was recorded.
        if ($sent === null && ! app()->runningUnitTests()) {
            throw new \RuntimeException('Mailer returned no SentMessage — delivery was not confirmed.');
        }

        return $sent;
    }

    private function mailer()
    {
        $name = (string) config('mail.default', 'smtp');

        // Prefer the real SMTP mailer even if something temporarily swapped the default.
        if ($name === 'log' && filled(config('mail.mailers.smtp.host'))) {
            $name = 'smtp';
        }

        return Mail::mailer($name);
    }

    private function validEmail(mixed $email): bool
    {
        $email = trim((string) $email);

        return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function safePdfBytes(QuoteRequest $request, ArtisanQuote $quote, User $artisan): ?string
    {
        try {
            $bytes = app(QuotePdfService::class)->output($request, $quote, $artisan);

            return $bytes !== '' ? $bytes : null;
        } catch (Throwable $e) {
            report($e);
            Log::warning('quote.pdf.attach_failed', [
                'quote_request_uid' => $request->uid,
                'quote_number' => $quote->quote_number,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @param  callable(): mixed  $send
     * @param  array<string, mixed>  $context
     */
    private function attempt(string $event, string $to, callable $send, array $context = []): bool
    {
        try {
            URL::forceRootUrl(rtrim((string) config('app.url'), '/'));

            /** @var SentMessage|null $sent */
            $sent = $send();

            $messageId = null;
            if ($sent instanceof SentMessage) {
                $messageId = method_exists($sent->getSymfonySentMessage(), 'getMessageId')
                    ? $sent->getSymfonySentMessage()->getMessageId()
                    : null;
            }

            Log::info($event.'.sent', array_merge($context, [
                'to' => $to,
                'message_id' => $messageId,
                'mailer' => config('mail.default'),
                'from' => config('mail.from.address'),
            ]));

            return true;
        } catch (Throwable $e) {
            report($e);
            Log::error($event.'.failed', array_merge($context, [
                'to' => $to,
                'message' => $e->getMessage(),
                'exception' => $e::class,
                'mailer' => config('mail.default'),
                'from' => config('mail.from.address'),
            ]));

            return false;
        }
    }
}
