<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Register')
            ->has('trades')
            ->has('locations'));
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'trade' => 'Electrician',
            'state' => 'Lagos',
            'lga' => 'Ikeja',
            'office_address' => '12 Allen Avenue, Ikeja',
            'whatsapp' => '08031234567',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'name' => 'Test User',
            'trade' => 'Electrician',
            'state' => 'Lagos',
            'lga' => 'Ikeja',
            'whatsapp' => '08031234567',
        ]);
    }
}
