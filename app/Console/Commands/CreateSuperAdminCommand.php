<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class CreateSuperAdminCommand extends Command
{
    protected $signature = 'isabi:create-super-admin
                            {email : Super admin email}
                            {--name= : Full name (defaults to Super Admin)}
                            {--password= : Password (prompted if omitted)}';

    protected $description = 'Create a super admin account (same outcome as Tinker User::createSuperAdmin)';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));

        if (User::query()->where('email', $email)->exists()) {
            $this->error("An account already exists for {$email}.");

            return self::FAILURE;
        }

        $name = trim((string) $this->option('name')) ?: 'Super Admin';
        $parts = preg_split('/\s+/', $name) ?: ['Super', 'Admin'];
        $firstName = $parts[0];
        $lastName = implode(' ', array_slice($parts, 1)) ?: 'Admin';

        $password = $this->option('password') ?: $this->secret('Password');

        if (! is_string($password) || $password === '') {
            $this->error('A password is required.');

            return self::FAILURE;
        }

        try {
            validator(
                ['password' => $password],
                ['password' => ['required', Password::defaults()]],
            )->validate();
        } catch (ValidationException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $user = User::createSuperAdmin($email, $password, $firstName, $lastName);

        $this->info("Super admin created: {$user->email}");
        $this->line('Sign in at '.route('admin.login'));

        return self::SUCCESS;
    }
}
