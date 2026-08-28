<?php

namespace Database\Seeders;

use App\Enums\StaffStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleUserSeeder extends Seeder
{
    /**
     * Seed one account for each application role.
     */
    public function run(): void
    {
        $password = env('SEED_USER_PASSWORD', 'password');

        $profiles = [
            [
                'name' => 'Super Admin',
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'super@isabi.dev',
                'role' => UserRole::SuperAdmin,
                'staff_status' => StaffStatus::Active,
            ],
            [
                'name' => 'Operations Admin',
                'first_name' => 'Operations',
                'last_name' => 'Admin',
                'email' => 'ops@isabi.dev',
                'role' => UserRole::OperationsAdmin,
                'staff_status' => StaffStatus::Active,
            ],
            [
                'name' => 'Demo Artisan',
                'first_name' => 'Demo',
                'last_name' => 'Artisan',
                'email' => 'user@isabi.dev',
                'role' => UserRole::User,
                'staff_status' => null,
            ],
        ];

        foreach ($profiles as $profile) {
            $user = User::query()->firstOrNew(['email' => $profile['email']]);
            $user->name = $profile['name'];
            $user->first_name = $profile['first_name'];
            $user->last_name = $profile['last_name'];
            $user->role = $profile['role'];
            $user->staff_status = $profile['staff_status'];
            $user->password = $password;
            $user->email_verified_at = now();
            $user->password_set_at = $profile['role']->isStaff() ? now() : $user->password_set_at;
            \App\Support\Identity\UserUid::fill($user);
            $user->save();
        }
    }
}
