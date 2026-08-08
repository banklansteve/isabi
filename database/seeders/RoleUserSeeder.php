<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RoleUserSeeder extends Seeder
{
    /**
     * Seed one account for each application role.
     */
    public function run(): void
    {
        $password = Hash::make(env('SEED_USER_PASSWORD', 'password'));

        $profiles = [
            [
                'name' => 'Super Admin',
                'email' => 'super@isabi.dev',
                'role' => UserRole::SuperAdmin,
            ],
            [
                'name' => 'Operations Admin',
                'email' => 'ops@isabi.dev',
                'role' => UserRole::OperationsAdmin,
            ],
            [
                'name' => 'Demo Artisan',
                'email' => 'user@isabi.dev',
                'role' => UserRole::User,
            ],
        ];

        foreach ($profiles as $profile) {
            $user = User::query()->firstOrNew(['email' => $profile['email']]);
            $user->name = $profile['name'];
            $user->role = $profile['role'];
            $user->password = $password;
            $user->email_verified_at = now();
            $user->save();
        }
    }
}
