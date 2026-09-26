<?php

namespace Tests\Feature;

use App\Models\QuoteRequest;
use App\Models\User;
use App\Models\WorkLog;
use App\Support\JobReference;
use App\Support\JobSlug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicJobReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_job_uses_seo_context_slash_opaque_id_and_redirects_legacy_paths(): void
    {
        $artisan = User::factory()->regularUser()->create([
            'business_name' => 'Bright Plumbing',
            'slug' => 'bright-plumbing',
        ]);

        $log = WorkLog::query()->create([
            'user_id' => $artisan->id,
            'subject' => 'Mrs Adeyemi kitchen — Lekki phase 1',
            'description' => 'Replaced pipes for Mrs Adeyemi at 12 Admiralty Way',
            'job_category' => 'Plumbing',
            'job_subcategory' => 'Kitchen plumbing',
            'worked_on' => now()->subDays(3),
        ])->fresh();

        $this->assertNotNull($log->reference);
        $this->assertTrue(JobReference::isCanonical($log->reference));
        $publicUrl = strtolower((string) $log->publicUrl());
        $this->assertStringNotContainsString('adeyemi', strtolower((string) $log->slug));
        $this->assertStringNotContainsString('admiralty', strtolower((string) $log->slug));
        $this->assertStringNotContainsString('adeyemi', $publicUrl);
        $this->assertStringNotContainsString('admiralty', $publicUrl);
        $this->assertStringNotContainsString('lekki', $publicUrl);
        $this->assertMatchesRegularExpression('#/p/bright-plumbing/[a-z0-9-]+/[a-z0-9]{6}$#', $publicUrl);
        $this->assertMatchesRegularExpression('/[a-z]/', $log->reference);
        $this->assertMatchesRegularExpression('/[0-9]/', $log->reference);

        $params = $log->publicRouteParams();
        $this->assertSame([
            'bright-plumbing',
            $log->slug,
            $log->reference,
        ], $params);
        $this->assertSame(
            JobSlug::compose($log->slug, $log->reference),
            $log->publicPathSegment(),
        );
        $this->assertStringContainsString('/'.$log->slug.'/'.$log->reference, $log->publicUrl());

        $this->get(route('public.job', $params))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Public/Job')
                ->where('job.reference', $log->reference)
                ->where('job.slug', $log->slug));

        // Legacy hyphenated segment
        $legacy = JobSlug::composeLegacy($log->slug, $log->reference);
        $this->get(route('public.job.legacy', [$artisan->slug, $legacy]))
            ->assertRedirect(route('public.job', $params));

        // Bare reference
        $this->get(route('public.job.legacy', [$artisan->slug, $log->reference]))
            ->assertRedirect(route('public.job', $params));
    }

    public function test_visitor_can_submit_quote_request_on_public_job(): void
    {
        $artisan = User::factory()->regularUser()->create([
            'business_name' => 'Bright Plumbing',
            'slug' => 'bright-plumbing',
        ]);

        $log = WorkLog::query()->create([
            'user_id' => $artisan->id,
            'subject' => 'Kitchen pipes',
            'description' => 'Kitchen pipes',
            'job_category' => 'Plumbing',
            'job_subcategory' => 'Kitchen plumbing',
            'worked_on' => now()->subDays(2),
        ])->fresh();

        $this->postJson(route('public.job.quote', $log->publicRouteParams()), [
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
            'subject' => 'Kitchen pipes',
            'description' => 'Kitchen pipes',
            'worked_on' => now()->subDays(2),
        ])->fresh();

        $this->postJson(route('public.job.quote.legacy', [$artisan->slug, $log->reference]), [
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
