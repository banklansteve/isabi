<?php

namespace App\Support\Quotes;

use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Support\Str;

class QuoteBuilderService
{
    public const DEFAULT_VAT_RATE = 7.5;

    public function ensureDraft(QuoteRequest $request, User $artisan): ArtisanQuote
    {
        $existing = $request->artisanQuote;

        if ($existing) {
            if ($existing->vat_rate === null) {
                $existing->forceFill(['vat_rate' => self::DEFAULT_VAT_RATE])->save();
            }

            return $existing->fresh();
        }

        return ArtisanQuote::query()->create([
            'quote_request_id' => $request->id,
            'user_id' => $artisan->id,
            'quote_number' => $this->nextQuoteNumber($artisan),
            'scope_of_work' => $request->message,
            'line_items' => QuoteLineItem::defaultRows(),
            'terms' => 'Quote valid for the period stated. Prices may adjust if scope changes.',
            'payment_terms' => '50% mobilisation · balance on completion',
            'valid_until' => now()->addDay()->addDays(13)->toDateString(),
            'vat_rate' => self::DEFAULT_VAT_RATE,
            'status' => ArtisanQuote::STATUS_DRAFT,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function syncDraft(ArtisanQuote $quote, array $data): ArtisanQuote
    {
        $lineItems = QuoteLineItem::normalizeCollection($data['line_items'] ?? []);

        $subtotalKobo = (int) round(QuoteLineItem::subtotalNaira($lineItems) * 100);

        $vatRate = min(100, max(0, (float) ($data['vat_rate'] ?? self::DEFAULT_VAT_RATE)));
        $discountKobo = (int) round(max(0, (float) ($data['discount_naira'] ?? 0)) * 100);
        $taxable = max(0, $subtotalKobo - $discountKobo);
        $vatKobo = (int) round($taxable * ($vatRate / 100));
        $totalKobo = $taxable + $vatKobo;

        $quote->forceFill([
            'valid_until' => $data['valid_until'] ?? null,
            'estimated_start' => $data['estimated_start'] ?? null,
            'estimated_duration_days' => filled($data['estimated_duration_days'] ?? null)
                ? (int) $data['estimated_duration_days']
                : null,
            'scope_of_work' => $data['scope_of_work'] ?? null,
            'line_items' => $lineItems,
            'notes' => $data['notes'] ?? null,
            'terms' => $data['terms'] ?? null,
            'payment_terms' => $data['payment_terms'] ?? null,
            'subtotal_kobo' => $subtotalKobo,
            'vat_rate' => $vatRate,
            'vat_kobo' => $vatKobo,
            'discount_kobo' => $discountKobo,
            'total_kobo' => $totalKobo,
        ])->save();

        return $quote->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    public function presentForPage(QuoteRequest $request, User $artisan, ArtisanQuote $quote): array
    {
        $request->refreshExpiry();
        $request->loadMissing('workLog');
        $presenter = app(QuotePipelinePresenter::class);

        $wa = preg_replace('/\D+/', '', (string) $artisan->whatsapp) ?? '';
        $clientPhone = preg_replace('/\D+/', '', (string) $request->phone) ?? '';

        return [
            'request' => [
                'uid' => $request->uid,
                'subject' => $request->displayTitle(),
                'name' => $request->name,
                'phone' => $request->phone,
                'phone_href' => $clientPhone !== '' ? 'tel:'.$clientPhone : null,
                'email' => $request->email,
                'email_href' => filled($request->email)
                    ? 'mailto:'.$request->email.'?subject='.rawurlencode('Re: '.$request->displayTitle())
                        .'&body='.rawurlencode("Hi {$request->name},\n\n")
                    : null,
                'message' => $request->message,
                'status' => $request->status,
                'status_label' => $presenter->statusLabel($request->status),
                'is_editable' => $request->isEditableByArtisan(),
                'is_read_only' => in_array($request->status, [
                    QuoteRequest::STATUS_ACCEPTED,
                    QuoteRequest::STATUS_DECLINED,
                    QuoteRequest::STATUS_EXPIRED,
                ], true),
                'client_response' => $request->client_response,
                'client_responded_at' => $request->client_responded_at
                    ?->timezone(config('app.display_timezone'))
                    ->format('j M Y · g:i A'),
                'submitted_at' => $request->created_at
                    ?->timezone(config('app.display_timezone'))
                    ->format('j M Y · g:i A'),
                'should_log_job' => $request->shouldPromptLogJob(),
                'log_job_url' => route('work-log.create', ['quote' => $request->uid]),
                'logged_work_log_id' => $request->logged_work_log_id,
                'job' => $request->workLog ? [
                    'description' => $request->workLog->description,
                    'reference' => $request->workLog->reference,
                    'public_url' => $request->workLog->publicUrl(),
                ] : null,
            ],
            'business' => [
                'business_name' => $artisan->displayBusinessName(),
                'email' => $artisan->email,
                'phone' => $artisan->whatsapp,
                'phone_href' => $wa !== '' ? 'https://wa.me/'.$wa : null,
                'address' => $artisan->office_address,
                'logo_url' => $artisan->logo_url ?: $artisan->avatar_url,
                'trade' => $artisan->trade,
                'area_label' => collect([$artisan->lga, $artisan->state])->filter()->implode(', ') ?: null,
            ],
            'quote' => [
                'uid' => $quote->uid,
                'quote_number' => $quote->quote_number,
                'valid_until' => $quote->valid_until?->toDateString(),
                'estimated_start' => $quote->estimated_start?->toDateString(),
                'estimated_duration_days' => $quote->estimated_duration_days,
                'scope_of_work' => $quote->scope_of_work,
                'line_items' => QuoteLineItem::rowsForEditor($quote->line_items ?? []),
                'notes' => $quote->notes,
                'terms' => $quote->terms,
                'payment_terms' => $quote->payment_terms,
                'vat_rate' => (float) ($quote->vat_rate ?? self::DEFAULT_VAT_RATE),
                'discount_naira' => $quote->discount_kobo / 100,
                'subtotal_naira' => $quote->subtotal_kobo / 100,
                'vat_naira' => $quote->vat_kobo / 100,
                'total_naira' => $quote->total_kobo / 100,
                'status' => $quote->status,
                'sent_at_label' => $quote->sent_at
                    ?->timezone(config('app.display_timezone'))
                    ->format('j M Y'),
            ],
            'whatsappShare' => $request->status === QuoteRequest::STATUS_AWAITING_CLIENT
                ? QuoteDelivery::payload($request, $quote)
                : null,
            'formMeta' => [
                'validUntilMin' => now()->addDay()->toDateString(),
                'defaultVatRate' => self::DEFAULT_VAT_RATE,
                'extraCharges' => QuoteLineItem::extraChargeOptions(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentPublicPage(QuoteRequest $request): array
    {
        $request->loadMissing(['artisan', 'artisanQuote', 'workLog']);
        $artisan = $request->artisan;
        $quote = $request->artisanQuote;

        abort_unless($quote && $artisan, 404);

        return [
            'business' => [
                'business_name' => $artisan->displayBusinessName(),
                'logo_url' => $artisan->logo_url ?: $artisan->avatar_url,
                'trade' => $artisan->trade,
                'phone' => $artisan->whatsapp,
            ],
            'request' => [
                'subject' => $request->displayTitle(),
                'message' => $request->message,
                'client_name' => $request->name,
                'status' => $request->status,
            ],
            'quote' => [
                'quote_number' => $quote->quote_number,
                'scope_of_work' => $quote->scope_of_work,
                'line_items' => collect(QuoteLineItem::normalizeCollection($quote->line_items ?? []))
                    ->map(fn ($row) => [
                        ...$row,
                        'display_label' => QuoteLineItem::displayLabel($row),
                        'line_total' => QuoteLineItem::lineTotalNaira($row),
                    ])
                    ->values()
                    ->all(),
                'notes' => $quote->notes,
                'terms' => $quote->terms,
                'payment_terms' => $quote->payment_terms,
                'valid_until' => $quote->valid_until
                    ?->timezone(config('app.display_timezone'))
                    ->format('j M Y'),
                'estimated_start' => $quote->estimated_start
                    ?->timezone(config('app.display_timezone'))
                    ->format('j M Y'),
                'estimated_duration_days' => $quote->estimated_duration_days,
                'subtotal_naira' => $quote->subtotal_kobo / 100,
                'discount_naira' => $quote->discount_kobo / 100,
                'vat_rate' => (float) $quote->vat_rate,
                'vat_naira' => $quote->vat_kobo / 100,
                'total_naira' => $quote->total_kobo / 100,
            ],
            'can_respond' => $request->status === QuoteRequest::STATUS_AWAITING_CLIENT,
            'respond_url' => route('quotes.public.respond', $request->client_token),
            'pdf_url' => filled($request->client_token)
                ? QuoteDelivery::pdfUrl($request)
                : null,
        ];
    }

    private function nextQuoteNumber(User $artisan): string
    {
        $count = ArtisanQuote::query()->where('user_id', $artisan->id)->count() + 1;

        return 'Q'.now()->format('y').'-'.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
