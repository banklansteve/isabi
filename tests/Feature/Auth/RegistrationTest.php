<?php

namespace Tests\Feature\Auth;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
            ->has('trades')
            ->has('locations')
            ->where('pendingVerification', null));
    }

    public function test_new_users_can_register_and_stay_on_verify_step(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'business_name' => 'Test Plumbing',
            'email' => 'test@example.com',
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
        ]);

        $this->assertAuthenticated();
        Mail::assertSent(VerifyEmailMail::class);

        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
            ->where('pendingVerification.email', 'test@example.com')
            ->has('pendingVerification.resendCooldown')
            ->has('pendingVerification.codeTtlMinutes'));

        $user = User::query()->where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertNotNull($user->email_verification_code_hash);
        $this->assertSame('Test Plumbing', $user->business_name);
    }
}
