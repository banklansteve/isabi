<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileBioTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_bio(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Ada',
            'last_name' => 'Okafor',
            'business_name' => 'Ada Wiring',
            'trade' => 'Electrician',
            'bio' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'section' => 'basics',
                'first_name' => 'Ada',
                'last_name' => 'Okafor',
                'business_name' => 'Ada Wiring',
                'trade' => 'Electrician',
                'bio' => 'I wire homes across Lagos Island.',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame(
            'I wire homes across Lagos Island.',
            $user->fresh()->bio,
        );
    }
}
