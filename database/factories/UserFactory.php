<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = fake()->firstName();
        $lastName = fake()->lastName();

        return [
            'name' => "{$firstName} {$lastName}",
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => fake()->unique()->safeEmail(),
            'role' => UserRole::User,
            'trade' => 'Electrician',
            'state' => 'Lagos',
            'lga' => 'Ikeja',
            'office_address' => fake()->streetAddress(),
            'whatsapp' => '0803'.fake()->numerify('#######'),
            'profile_completion' => 45,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::SuperAdmin,
        ]);
    }

    public function operationsAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::OperationsAdmin,
        ]);
    }

    public function regularUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::User,
        ]);
    }
}
