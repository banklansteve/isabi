<?php

namespace Tests\Feature;

use App\Models\QuoteRequest;
use App\Models\User;
use App\Models\WorkLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicJobReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_job_resolves_by_reference_and_redirects_slug_urls(): void
    {
        $artisan = User::factory()->regularUser()->create([
            'business_name' => 'Bright Plumbing',
            'slug' => 'bright-plumbing',
        ]);

        $log = WorkLog::query()->create([
            'user_id' => $artisan->id,
            'description' => 'Bathroom retile',
            'worked_on' => now()->subDays(3),
            'slug' => 'bathroom-retile',
        ])->fresh();

        $this->assertNotNull($log->reference);
        $this->assertMatchesRegularExpression('/^[a-z0-9]{6,8}$/', $log->reference);

        $this->get(route('public.job', [$artisan->slug, $log->reference]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Job')
                ->where('job.reference', $log->reference));

        $this->get(route('public.job', [$artisan->slug, $log->slug]))
            ->assertRedirect(route('public.job', [$artisan->slug, $log->reference]));
    }

    public function test_visitor_can_submit_quote_request_on_public_job(): void
    {
        $artisan = User::factory()->regularUser()->create([
            'business_name' => 'Bright Plumbing',
            'slug' => 'bright-plumbing',
        ]);

        $log = WorkLog::query()->create([
            'user_id' => $artisan->id,
            'description' => 'Kitchen pipes',
            'worked_on' => now()->subDays(2),
        ])->fresh();

        $this->postJson(route('public.job.quote', [$artisan->slug, $log->reference]), [
            'name' => 'Ada George',
            'phone' => '08012345678',
            'email' => 'ada@example.com',
            'subject' => 'Kitchen pipes',
            'message' => 'Need similar work in Lekki',
        ])->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('quote_requests', [
            'work_log_id' => $log->id,
            'user_id' => $artisan->id,
            'name' => 'Ada George',
            'phone' => '08012345678',
            'email' => 'ada@example.com',
            'status' => QuoteRequest::STATUS_NEW,
        ]);
    }

    public function test_quote_request_requires_email(): void
    {
        $artisan = User::factory()->regularUser()->create(['slug' => 'bright-plumbing']);
        $log = WorkLog::query()->create([
            'user_id' => $artisan->id,
            'description' => 'Kitchen pipes',
            'worked_on' => now()->subDays(2),
        ])->fresh();

        $this->postJson(route('public.job.quote', [$artisan->slug, $log->reference]), [
            'name' => 'Ada George',
            'phone' => '08012345678',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_embed_profile_page_renders_for_public_slug(): void
    {
        $artisan = User::factory()->regularUser()->create([
            'business_name' => 'Bright Plumbing',
            'slug' => 'bright-plumbing',
        ]);

        $this->get(route('embed.profile', $artisan->slug))
            ->assertOk()
            ->assertSee('Bright Plumbing', false);
    }

    public function test_artisan_can_upload_business_logo(): void
    {
        $artisan = User::factory()->regularUser()->create();

        $this->actingAs($artisan)
            ->post(route('profile.logo'), [
                'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png', 400, 400),
            ])
            ->assertRedirect(route('profile.edit'));

        $artisan->refresh();
        $this->assertNotNull($artisan->logo_url);
    }
}
