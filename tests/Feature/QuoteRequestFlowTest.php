<?php

namespace Tests\Feature;

use App\Mail\QuoteDeliveredClientMail;
use App\Mail\QuoteRequestArtisanAlertMail;
use App\Mail\QuoteRequestClientConfirmationMail;
use App\Mail\QuoteSentArtisanMail;
use App\Models\AnnouncementDelivery;
use App\Models\ArtisanQuote;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Models\WorkLog;
use App\Support\Quotes\QuoteLineItem;
use App\Support\Quotes\QuoteRequestNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QuoteRequestFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_quote_request_notifies_artisan_and_sends_emails(): void
    {
        Mail::fake();

        $artisan = User::factory()->regularUser()->create([
            'business_name' => 'Bright Plumbing',
            'slug' => 'bright-plumbing',
        ]);

        $this->postJson(route('public.profile.quote', $artisan->slug), [
            'name' => 'Chidi Okafor',
            'phone' => '08098765432',
            'email' => 'chidi@example.com',
            'subject' => 'Fix kitchen sink',
            'message' => 'Need plumbing in Victoria Island',
        ])->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('toast.title', 'Request sent');

        $quote = QuoteRequest::query()->first();
        $this->assertSame('Fix kitchen sink', $quote->subject);
        $this->assertSame(QuoteRequest::STATUS_NEW, $quote->status);

        Mail::assertSent(QuoteRequestClientConfirmationMail::class);
        Mail::assertSent(QuoteRequestArtisanAlertMail::class);

        $delivery = AnnouncementDelivery::query()->where('user_id', $artisan->id)->first();
        $this->assertSame(QuoteRequestNotifier::KIND, $delivery->announcement->segment['kind'] ?? null);
    }

    public function test_artisan_can_send_quote_and_client_can_respond(): void
    {
        Mail::fake();

        $artisan = User::factory()->regularUser()->create([
            'slug' => 'bright-plumbing',
            'email' => 'artisan@example.com',
        ]);

        $quoteRequest = QuoteRequest::query()->create([
            'user_id' => $artisan->id,
            'name' => 'Ada George',
            'phone' => '08012345678',
            'email' => 'ada@example.com',
            'subject' => 'Rewire two-bedroom flat',
            'message' => 'Similar work needed',
            'status' => QuoteRequest::STATUS_DRAFT,
        ]);

        ArtisanQuote::query()->create([
            'quote_request_id' => $quoteRequest->id,
            'user_id' => $artisan->id,
            'quote_number' => 'Q-TEST01',
            'line_items' => [[
                'kind' => 'labour',
                'label' => 'Labour',
                'quantity' => 1,
                'unit' => 'fee',
                'unit_price' => 450000,
            ]],
            'subtotal_kobo' => 45000000,
            'vat_rate' => 7.5,
            'vat_kobo' => 3375000,
            'total_kobo' => 48375000,
            'valid_until' => now()->addDays(14)->toDateString(),
            'status' => ArtisanQuote::STATUS_DRAFT,
        ]);

        $this->actingAs($artisan)
            ->post(route('quotes.send', $quoteRequest), [
                'valid_until' => now()->addDays(14)->toDateString(),
                'line_items' => [[
                    'kind' => 'labour',
                    'label' => 'Labour',
                    'quantity' => 1,
                    'unit' => 'fee',
                    'unit_price' => 450000,
                ]],
                'vat_rate' => 7.5,
                'discount_naira' => 0,
            ])
            ->assertRedirect(route('quotes.show', $quoteRequest));

        $quoteRequest->refresh();
        $this->assertSame(QuoteRequest::STATUS_AWAITING_CLIENT, $quoteRequest->status);
        $this->assertNotNull($quoteRequest->client_token);
        $this->assertMatchesRegularExpression('/^Q-[A-Z0-9]{6,7}$/', $quoteRequest->client_token);
        $this->assertSame($quoteRequest->client_token, $quoteRequest->artisanQuote->quote_number);
        $this->assertSame(
            $quoteRequest->artisanQuote->valid_until->toDateString(),
            $quoteRequest->client_token_expires_at->toDateString()
        );
        $this->assertTrue($quoteRequest->client_token_expires_at->isEndOfDay());

        Mail::assertSent(QuoteDeliveredClientMail::class, function (QuoteDeliveredClientMail $mail) {
            return str_contains($mail->quoteUrl, '/q/')
                && str_contains($mail->pdfUrl, '/download')
                && filled($mail->pdfBytes)
                && filled($mail->pdfFilename);
        });
        Mail::assertSent(QuoteSentArtisanMail::class, function (QuoteSentArtisanMail $mail) {
            return str_contains($mail->quoteUrl, '/q/')
                && str_contains($mail->pdfUrl, '/download')
                && filled($mail->pdfBytes)
                && filled($mail->pdfFilename);
        });

        $this->post(route('quotes.public.respond', $quoteRequest->client_token), [
            'decision' => 'accepted',
            'message' => 'Looks good',
        ])->assertRedirect();

        $quoteRequest->refresh();
        $this->assertSame(QuoteRequest::STATUS_ACCEPTED, $quoteRequest->status);
        $this->assertSame('Looks good', $quoteRequest->client_response);

        Mail::assertSent(\App\Mail\QuoteClientRespondedArtisanMail::class, function ($mail) use ($quoteRequest) {
            return $mail->decision === 'accepted'
                && $mail->quoteRequest->is($quoteRequest);
        });
    }

    public function test_client_can_request_quote_adjustments_with_required_note(): void
    {
        Mail::fake();

        $artisan = User::factory()->regularUser()->create([
            'email' => 'artisan@example.com',
        ]);

        $quoteRequest = QuoteRequest::query()->create([
            'user_id' => $artisan->id,
            'name' => 'Ada George',
            'phone' => '08012345678',
            'email' => 'ada@example.com',
            'subject' => 'Kitchen work',
            'status' => QuoteRequest::STATUS_AWAITING_CLIENT,
            'client_token' => 'Q-ADJ7XK',
            'client_token_expires_at' => now()->addDays(7),
        ]);

        ArtisanQuote::query()->create([
            'quote_request_id' => $quoteRequest->id,
            'user_id' => $artisan->id,
            'quote_number' => 'Q-ADJ7XK',
            'line_items' => [[
                'kind' => 'labour',
                'label' => 'Labour',
                'quantity' => 1,
                'unit' => 'fee',
                'unit_price' => 100000,
            ]],
            'subtotal_kobo' => 10000000,
            'vat_rate' => 0,
            'total_kobo' => 10000000,
            'status' => ArtisanQuote::STATUS_SENT,
            'sent_at' => now(),
        ]);

        $this->post(route('quotes.public.respond', $quoteRequest->client_token), [
            'decision' => 'adjustments',
            'message' => 'short',
        ])->assertSessionHasErrors('message');

        $this->post(route('quotes.public.respond', $quoteRequest->client_token), [
            'decision' => 'adjustments',
            'message' => 'Please reduce labour cost and extend timeline by one week.',
        ])->assertRedirect(route('quotes.public.thanks', $quoteRequest->client_token));

        $quoteRequest->refresh();
        $this->assertSame(QuoteRequest::STATUS_ADJUSTMENTS_REQUESTED, $quoteRequest->status);
        $this->assertTrue($quoteRequest->isEditableByArtisan());
        $this->assertStringContainsString('reduce labour', (string) $quoteRequest->client_response);
        $this->assertSame(ArtisanQuote::STATUS_DRAFT, $quoteRequest->artisanQuote->status);

        Mail::assertSent(\App\Mail\QuoteClientRespondedArtisanMail::class, function ($mail) {
            return $mail->decision === 'adjustments'
                && str_contains((string) $mail->quoteRequest->client_response, 'reduce labour');
        });
    }

    public function test_artisan_is_notified_when_client_declines_quote(): void
    {
        Mail::fake();

        $artisan = User::factory()->regularUser()->create([
            'email' => 'artisan@example.com',
        ]);

        $quoteRequest = QuoteRequest::query()->create([
            'user_id' => $artisan->id,
            'name' => 'Ada George',
            'phone' => '08012345678',
            'email' => 'ada@example.com',
            'subject' => 'Kitchen work',
            'status' => QuoteRequest::STATUS_AWAITING_CLIENT,
            'client_token' => 'Q-DEC7XK',
            'client_token_expires_at' => now()->addDays(7),
        ]);

        ArtisanQuote::query()->create([
            'quote_request_id' => $quoteRequest->id,
            'user_id' => $artisan->id,
            'quote_number' => 'Q-DEC7XK',
            'line_items' => [[
                'kind' => 'labour',
                'label' => 'Labour',
                'quantity' => 1,
                'unit' => 'fee',
                'unit_price' => 100000,
            ]],
            'subtotal_kobo' => 10000000,
            'total_kobo' => 10000000,
            'status' => ArtisanQuote::STATUS_SENT,
            'sent_at' => now(),
        ]);

        $this->post(route('quotes.public.respond', $quoteRequest->client_token), [
            'decision' => 'declined',
            'message' => 'Budget is too tight right now.',
        ])->assertRedirect();

        $quoteRequest->refresh();
        $this->assertSame(QuoteRequest::STATUS_DECLINED, $quoteRequest->status);

        Mail::assertSent(\App\Mail\QuoteClientRespondedArtisanMail::class, function ($mail) {
            return $mail->decision === 'declined';
        });
    }

    public function test_artisan_can_update_and_resend_quote_after_adjustments_while_keeping_prior_details(): void
    {
        Mail::fake();

        $artisan = User::factory()->regularUser()->create([
            'email' => 'artisan@example.com',
        ]);

        $quoteRequest = QuoteRequest::query()->create([
            'user_id' => $artisan->id,
            'name' => 'Ada George',
            'phone' => '08012345678',
            'email' => 'ada@example.com',
            'subject' => 'Kitchen work',
            'status' => QuoteRequest::STATUS_ADJUSTMENTS_REQUESTED,
            'client_response' => 'Please reduce labour and add tiles.',
            'client_responded_at' => now()->subHour(),
            'client_token' => 'Q-REV7XK',
            'client_token_expires_at' => now()->addDays(7),
        ]);

        ArtisanQuote::query()->create([
            'quote_request_id' => $quoteRequest->id,
            'user_id' => $artisan->id,
            'quote_number' => 'Q-REV7XK',
            'scope_of_work' => 'Full kitchen remodel',
            'line_items' => [
                [
                    'kind' => 'labour',
                    'label' => 'Labour',
                    'quantity' => 1,
                    'unit' => 'fee',
                    'unit_price' => 150000,
                ],
                [
                    'kind' => 'materials',
                    'label' => 'Pipes',
                    'quantity' => 2,
                    'unit' => 'unit',
                    'unit_price' => 25000,
                ],
            ],
            'notes' => 'Includes cleanup',
            'terms' => 'Valid as stated',
            'payment_terms' => '50% mobilisation',
            'subtotal_kobo' => 20000000,
            'vat_rate' => 7.5,
            'vat_kobo' => 1500000,
            'discount_kobo' => 0,
            'total_kobo' => 21500000,
            'valid_until' => now()->addDays(10)->toDateString(),
            'status' => ArtisanQuote::STATUS_DRAFT,
            'sent_at' => now()->subDay(),
        ]);

        $this->actingAs($artisan)
            ->get(route('quotes.show', $quoteRequest))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Quotes/Builder')
                ->where('request.is_editable', true)
                ->where('request.wants_adjustments', true)
                ->where('quote.scope_of_work', 'Full kitchen remodel')
                ->where('quote.notes', 'Includes cleanup')
                ->where('quote.line_items.0.unit_price', 150000)
                ->where('quote.line_items.1.label', 'Pipes')
                ->where('quote.line_items.1.quantity', 2));

        $this->actingAs($artisan)
            ->post(route('quotes.send', $quoteRequest), [
                'valid_until' => now()->addDays(14)->toDateString(),
                'scope_of_work' => 'Full kitchen remodel',
                'notes' => 'Includes cleanup',
                'terms' => 'Valid as stated',
                'payment_terms' => '50% mobilisation',
                'line_items' => [
                    [
                        'kind' => 'labour',
                        'label' => 'Labour',
                        'quantity' => 1,
                        'unit' => 'fee',
                        'unit_price' => 120000,
                    ],
                    [
                        'kind' => 'materials',
                        'label' => 'Pipes',
                        'quantity' => 2,
                        'unit' => 'unit',
                        'unit_price' => 25000,
                    ],
                    [
                        'kind' => 'materials',
                        'label' => 'Tiles',
                        'quantity' => 1,
                        'unit' => 'unit',
                        'unit_price' => 40000,
                    ],
                ],
                'vat_rate' => 7.5,
                'discount_naira' => 0,
            ])
            ->assertRedirect(route('quotes.show', $quoteRequest));

        $quoteRequest->refresh();
        $quote = $quoteRequest->artisanQuote()->first();

        $this->assertSame(QuoteRequest::STATUS_AWAITING_CLIENT, $quoteRequest->status);
        $this->assertNull($quoteRequest->client_response);
        $this->assertSame(ArtisanQuote::STATUS_SENT, $quote->status);
        $this->assertSame('Full kitchen remodel', $quote->scope_of_work);
        $this->assertSame('Includes cleanup', $quote->notes);
        $this->assertSame('50% mobilisation', $quote->payment_terms);

        $labels = collect($quote->line_items)->pluck('label')->all();
        $this->assertContains('Labour', $labels);
        $this->assertContains('Pipes', $labels);
        $this->assertContains('Tiles', $labels);

        $pipes = collect($quote->line_items)->firstWhere('label', 'Pipes');
        $this->assertSame(2.0, (float) $pipes['quantity']);
        $this->assertSame(25000.0, (float) $pipes['unit_price']);

        Mail::assertSent(QuoteDeliveredClientMail::class);
    }

    public function test_quote_line_item_defaults_use_quantity_one(): void
    {
        $rows = QuoteLineItem::defaultRows();

        $this->assertSame(1.0, (float) $rows[0]['quantity']);
        $this->assertSame(1.0, (float) $rows[1]['quantity']);

        $editorRows = QuoteLineItem::rowsForEditor([]);
        $materials = collect($editorRows)->firstWhere('kind', 'materials');
        $this->assertSame(1.0, (float) $materials['quantity']);
    }

    public function test_artisan_can_download_quote_pdf(): void
    {
        $artisan = User::factory()->regularUser()->create();

        $quoteRequest = QuoteRequest::query()->create([
            'user_id' => $artisan->id,
            'name' => 'Ada',
            'phone' => '08012345678',
            'email' => 'ada@example.com',
            'subject' => 'Kitchen work',
            'status' => QuoteRequest::STATUS_DRAFT,
        ]);

        ArtisanQuote::query()->create([
            'quote_request_id' => $quoteRequest->id,
            'user_id' => $artisan->id,
            'quote_number' => 'Q-PDF001',
            'line_items' => [[
                'kind' => 'labour',
                'label' => 'Labour',
                'quantity' => 1,
                'unit' => 'fee',
                'unit_price' => 100000,
            ]],
            'subtotal_kobo' => 10000000,
            'vat_rate' => 0,
            'vat_kobo' => 0,
            'total_kobo' => 10000000,
            'status' => ArtisanQuote::STATUS_DRAFT,
        ]);

        $this->actingAs($artisan)
            ->get(route('quotes.pdf', $quoteRequest))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs(User::factory()->regularUser()->create())
            ->get(route('quotes.pdf', $quoteRequest))
            ->assertForbidden();
    }

    public function test_unified_quotes_index_filters_by_pipeline_tab(): void
    {
        $artisan = User::factory()->regularUser()->create();

        QuoteRequest::query()->create([
            'user_id' => $artisan->id,
            'name' => 'Ada',
            'phone' => '08012345678',
            'email' => 'ada@example.com',
            'subject' => 'Kitchen work',
            'status' => QuoteRequest::STATUS_NEW,
        ]);

        QuoteRequest::query()->create([
            'user_id' => $artisan->id,
            'name' => 'Bola',
            'phone' => '08087654321',
            'email' => 'bola@example.com',
            'subject' => 'Gate welding',
            'status' => QuoteRequest::STATUS_AWAITING_CLIENT,
        ]);

        $this->actingAs($artisan)
            ->get(route('quotes.index', ['tab' => 'needs_response']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Quotes/Index')
                ->has('quotes.data', 1)
                ->where('quotes.data.0.subject', 'Kitchen work')
                ->where('filters.tab', 'needs_response')
                ->has('filterOptions.statuses')
                ->has('filterOptions.sorts'));

        $this->actingAs($artisan)
            ->get(route('quotes.index', [
                'tab' => 'all',
                'q' => 'Gate',
                'sort' => 'name',
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('quotes.data', 1)
                ->where('quotes.data.0.name', 'Bola')
                ->where('filters.q', 'Gate')
                ->where('filters.sort', 'name'));

        $this->actingAs($artisan)
            ->get(route('quotes.index', ['status' => QuoteRequest::STATUS_AWAITING_CLIENT]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('quotes.data', 1)
                ->where('quotes.data.0.status', QuoteRequest::STATUS_AWAITING_CLIENT));
    }

    public function test_accepted_quote_prefills_work_log_create(): void
    {
        $artisan = User::factory()->regularUser()->create();

        $quoteRequest = QuoteRequest::query()->create([
            'user_id' => $artisan->id,
            'name' => 'Ada',
            'phone' => '08012345678',
            'email' => 'ada@example.com',
            'subject' => 'Install fan in 3 rooms',
            'status' => QuoteRequest::STATUS_ACCEPTED,
            'accepted_at' => now()->subDays(5),
        ]);

        $this->actingAs($artisan)
            ->get(route('work-log.create', ['quote' => $quoteRequest->uid]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('defaults.description', 'Install fan in 3 rooms')
                ->where('defaults.client_name', 'Ada'));
    }

    public function test_client_can_download_quote_pdf(): void
    {
        $artisan = User::factory()->regularUser()->create(['slug' => 'bright-plumbing']);

        $quoteRequest = QuoteRequest::query()->create([
            'user_id' => $artisan->id,
            'name' => 'Ada George',
            'phone' => '08012345678',
            'email' => 'ada@example.com',
            'subject' => 'Rewire two-bedroom flat',
            'message' => 'Similar work needed',
            'status' => QuoteRequest::STATUS_AWAITING_CLIENT,
            'client_token' => 'Q-PDF7XK',
            'client_token_expires_at' => now()->addDays(7),
        ]);

        ArtisanQuote::query()->create([
            'quote_request_id' => $quoteRequest->id,
            'user_id' => $artisan->id,
            'quote_number' => 'Q-PDF7XK',
            'line_items' => QuoteLineItem::defaultRows(),
            'subtotal_kobo' => 10000000,
            'vat_rate' => 7.5,
            'total_kobo' => 10750000,
            'valid_until' => now()->addDays(14)->toDateString(),
            'status' => ArtisanQuote::STATUS_SENT,
            'sent_at' => now(),
        ]);

        $this->get(route('quotes.public.pdf.page', $quoteRequest->client_token))
            ->assertOk()
            ->assertSee('Download PDF', false)
            ->assertSee(route('quotes.public.pdf', $quoteRequest->client_token), false);

        $this->get(route('quotes.public.pdf', [
            'token' => $quoteRequest->client_token,
        ]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition');

        $response = $this->get(route('quotes.public.pdf', $quoteRequest->client_token));
        $response->assertOk();
        $this->assertNotEmpty($response->getContent());
        $this->assertSame(strlen($response->getContent()), (int) $response->headers->get('content-length'));
        $this->assertStringContainsString('%PDF', substr($response->getContent(), 0, 8));
    }

    public function test_quote_expires_after_valid_until_day_and_nudges_when_looming(): void
    {
        Mail::fake();

        $artisan = User::factory()->regularUser()->create([
            'email' => 'artisan@example.com',
        ]);

        $looming = QuoteRequest::query()->create([
            'user_id' => $artisan->id,
            'name' => 'Ada',
            'phone' => '08012345678',
            'email' => 'ada@example.com',
            'subject' => 'Looming quote',
            'status' => QuoteRequest::STATUS_AWAITING_CLIENT,
            'client_token' => 'Q-NUDGE1',
            'client_token_expires_at' => now()->addDays(2)->endOfDay(),
        ]);

        ArtisanQuote::query()->create([
            'quote_request_id' => $looming->id,
            'user_id' => $artisan->id,
            'quote_number' => 'Q-NUDGE1',
            'line_items' => QuoteLineItem::defaultRows(),
            'subtotal_kobo' => 10000000,
            'vat_rate' => 7.5,
            'total_kobo' => 10750000,
            'valid_until' => now()->addDays(2)->toDateString(),
            'status' => ArtisanQuote::STATUS_SENT,
            'sent_at' => now(),
        ]);

        $expired = QuoteRequest::query()->create([
            'user_id' => $artisan->id,
            'name' => 'Chidi',
            'phone' => '08099998888',
            'email' => 'chidi@example.com',
            'subject' => 'Expired quote',
            'status' => QuoteRequest::STATUS_AWAITING_CLIENT,
            'client_token' => 'Q-EXPIRD',
            'client_token_expires_at' => now()->subDay()->endOfDay(),
        ]);

        ArtisanQuote::query()->create([
            'quote_request_id' => $expired->id,
            'user_id' => $artisan->id,
            'quote_number' => 'Q-EXPIRD',
            'line_items' => QuoteLineItem::defaultRows(),
            'subtotal_kobo' => 5000000,
            'vat_rate' => 7.5,
            'total_kobo' => 5375000,
            'valid_until' => now()->subDay()->toDateString(),
            'status' => ArtisanQuote::STATUS_SENT,
            'sent_at' => now()->subDays(10),
        ]);

        $this->artisan('quotes:process-expiry')
            ->assertSuccessful();

        $looming->refresh();
        $expired->refresh();

        $this->assertNotNull($looming->expiry_nudge_sent_at);
        $this->assertSame(QuoteRequest::STATUS_AWAITING_CLIENT, $looming->status);
        $this->assertSame(QuoteRequest::STATUS_EXPIRED, $expired->status);

        Mail::assertSent(\App\Mail\QuoteExpiryNudgeClientMail::class);
        Mail::assertSent(\App\Mail\QuoteExpiryNudgeArtisanMail::class);

        $this->get(route('quotes.public.show', $expired->client_token))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Public/QuoteExpired'));
    }
}
