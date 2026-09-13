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
                && str_contains($mail->pdfUrl, '/pdf')
                && str_contains($mail->pdfUrl, 'download=1')
                && filled($mail->pdfBytes)
                && filled($mail->pdfFilename);
        });
        Mail::assertSent(QuoteSentArtisanMail::class, function (QuoteSentArtisanMail $mail) {
            return str_contains($mail->quoteUrl, '/q/')
                && str_contains($mail->pdfUrl, '/pdf')
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

        $this->get(route('quotes.public.pdf', $quoteRequest->client_token))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
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
