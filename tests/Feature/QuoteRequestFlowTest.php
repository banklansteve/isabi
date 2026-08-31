<?php

namespace Tests\Feature;

use App\Mail\QuoteDeliveredClientMail;
use App\Mail\QuoteRequestArtisanAlertMail;
use App\Mail\QuoteRequestClientConfirmationMail;
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

        $artisan = User::factory()->regularUser()->create(['slug' => 'bright-plumbing']);

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
            'quote_number' => 'Q26-0001',
            'line_items' => [[
                'kind' => 'labour',
                'label' => 'Labour',
                'quantity' => 1,
                'unit' => 'fee',
                'unit_price' => 450000,
            ]],
            'subtotal_kobo' => 45000000,
            'vat_rate' => 7.5,
            'total_kobo' => 45000000,
            'valid_until' => now()->addDays(14)->toDateString(),
            'status' => ArtisanQuote::STATUS_DRAFT,
        ]);

        $this->actingAs($artisan)
            ->post(route('quotes.send', $quoteRequest))
            ->assertRedirect(route('quotes.show', $quoteRequest));

        $quoteRequest->refresh();
        $this->assertSame(QuoteRequest::STATUS_AWAITING_CLIENT, $quoteRequest->status);
        $this->assertNotNull($quoteRequest->client_token);

        Mail::assertSent(QuoteDeliveredClientMail::class);

        $this->post(route('quotes.public.respond', $quoteRequest->client_token), [
            'decision' => 'accepted',
            'message' => 'Looks good',
        ])->assertRedirect();

        $quoteRequest->refresh();
        $this->assertSame(QuoteRequest::STATUS_ACCEPTED, $quoteRequest->status);
        $this->assertSame('Looks good', $quoteRequest->client_response);
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

        $this->actingAs($artisan)
            ->get(route('quotes.index', ['tab' => 'needs_response']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Quotes/Index')
                ->has('quotes.data', 1)
                ->where('quotes.data.0.subject', 'Kitchen work'));
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
            'client_token' => 'testtoken123456789012345678901234567890',
            'client_token_expires_at' => now()->addDays(7),
        ]);

        ArtisanQuote::query()->create([
            'quote_request_id' => $quoteRequest->id,
            'user_id' => $artisan->id,
            'quote_number' => 'Q26-0002',
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
}
