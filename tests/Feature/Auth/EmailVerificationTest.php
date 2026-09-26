<?php

namespace Tests\Feature\Auth;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use App\Support\Auth\EmailVerificationService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

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
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
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

    public function test_password_reset_requires_verified_email(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
        ]);

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHasErrors('email');
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
}
