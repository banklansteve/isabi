<?php

namespace Tests\Feature\Auth;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use App\Models\WorkLog;
use App\Support\Auth\EmailVerificationService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_can_be_verified_with_code(): void
    {
        Mail::fake();
        Event::fake([Verified::class]);

        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        $issued = app(EmailVerificationService::class)->issue($user, session()->getId(), sendMail: true);

        Mail::assertSent(VerifyEmailMail::class);

        $response = $this->post('/email/verification-code', [
            'code' => $issued['code'],
        ]);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNull($user->fresh()->email_verification_code_hash);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_email_can_be_verified_with_signed_link(): void
    {
        Event::fake([Verified::class]);

        $user = User::factory()->unverified()->create();
        $issued = app(EmailVerificationService::class)->issue($user, 'session-abc', sendMail: false);

        $response = $this->get($issued['verify_url']);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertOk();
    }

    public function test_invalid_code_is_rejected_and_counts_attempts(): void
    {
        $user = User::factory()->unverified()->create();
        app(EmailVerificationService::class)->issue($user, session()->getId(), sendMail: false);

        $this->actingAs($user)
            ->post('/email/verification-code', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertSame(1, (int) $user->fresh()->email_verification_attempts);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_resend_invalidates_previous_code_and_respects_cooldown(): void
    {
        Mail::fake();

        $user = User::factory()->unverified()->create();
        $first = app(EmailVerificationService::class)->issue($user, session()->getId(), sendMail: false);

        $this->actingAs($user)
            ->post('/email/verification-notification')
            ->assertSessionHasErrors('email');

        $user->forceFill(['email_verification_sent_at' => now()->subMinutes(2)])->save();

        $this->actingAs($user)
            ->from('/register')
            ->post('/email/verification-notification')
            ->assertRedirect(route('register'));

        $this->assertFalse(Hash::check($first['code'], $user->fresh()->email_verification_code_hash));
        Mail::assertSent(VerifyEmailMail::class);
    }

    public function test_password_reset_requires_verified_email(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
        ]);

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasErrors('email');
    }

    public function test_unverified_user_can_use_app_but_not_log_jobs_or_request_review(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();

        $this->actingAs($user)
            ->get(route('work-log.create'))
            ->assertRedirect(route('verification.notice'));

        $this->actingAs($user)
            ->post(route('work-log.store'), [
                'subject' => 'Kitchen sink repair',
                'description' => 'Fixed a leaking sink for a client.',
                'job_category' => 'Plumbing',
                'job_subcategory' => 'Kitchen plumbing',
                'worked_on' => now()->subDay()->toDateString(),
            ])
            ->assertRedirect(route('verification.notice'));

        $this->assertSame(0, WorkLog::query()->where('user_id', $user->id)->count());

        $workLog = WorkLog::query()->create([
            'user_id' => $user->id,
            'subject' => 'Kitchen sink repair',
            'description' => 'Fixed a leaking sink for a client.',
            'job_category' => 'Plumbing',
            'job_subcategory' => 'Kitchen plumbing',
            'worked_on' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->from(route('work-log.show', $workLog))
            ->post(route('work-log.request-review', $workLog))
            ->assertRedirect(route('verification.notice'));

        $this->assertNull($workLog->fresh()->review_requested_at);
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();
        $issued = app(EmailVerificationService::class)->issue($user, 'sess', sendMail: false);

        $badUrl = preg_replace(
            '#/'.preg_quote((string) sha1($user->email), '#').'#',
            '/'.sha1('wrong-email'),
            $issued['verify_url'],
        );

        $this->get($badUrl)->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_registration_sends_only_one_verification_code(): void
    {
        Mail::fake();

        $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'business_name' => 'Test Plumbing',
            'email' => 'once@example.com',
            'job_category' => 'Plumbing, Water & Gas',
            'trade' => 'Plumber',
            'trades' => ['Plumber'],
            'skills' => ['Pipe repairs'],
            'state' => 'Lagos',
            'lga' => 'Ikeja',
            'office_address' => '12 Allen Avenue, Ikeja',
            'whatsapp' => '08031234567',
            'password' => 'password1',
            'password_confirmation' => 'password1',
            'terms_accepted' => true,
        ])->assertOk();

        Mail::assertSent(VerifyEmailMail::class, 1);
        $this->assertAuthenticated();
        $this->assertSame('once@example.com', auth()->user()->email);
    }

    public function test_registration_replaces_previous_session_user(): void
    {
        Mail::fake();

        $previous = User::factory()->create([
            'email' => 'previous@example.com',
            'business_name' => 'Previous Biz',
        ]);

        $this->actingAs($previous)
            ->post('/register', [
                'first_name' => 'New',
                'last_name' => 'Artisan',
                'business_name' => 'New Plumbing',
                'email' => 'fresh@example.com',
                'job_category' => 'Plumbing, Water & Gas',
                'trade' => 'Plumber',
                'trades' => ['Plumber'],
                'skills' => ['Pipe repairs'],
                'state' => 'Lagos',
                'lga' => 'Ikeja',
                'office_address' => '12 Allen Avenue, Ikeja',
                'whatsapp' => '08031234567',
                'password' => 'password1',
                'password_confirmation' => 'password1',
                'terms_accepted' => true,
            ])
            ->assertOk();

        $fresh = User::query()->where('email', 'fresh@example.com')->first();
        $this->assertNotNull($fresh);
        $this->assertAuthenticatedAs($fresh);
        $this->assertNotSame($previous->id, auth()->id());
    }
}
