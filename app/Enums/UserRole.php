<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case OperationsAdmin = 'operations_admin';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::OperationsAdmin => 'Operations Admin',
            self::User => 'User',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Full platform control — users, billing, settings, and every admin surface.',
            self::OperationsAdmin => 'Day-to-day staff access — content, support queues, and limited user management.',
            self::User => 'Regular artisan account — public page, jobs, reviews, and billing for their own profile.',
        };
    }

    public function isStaff(): bool
    {
        return $this === self::SuperAdmin || $this === self::OperationsAdmin;
    }

    public function homeRouteName(): string
    {
        return match ($this) {
            self::SuperAdmin, self::OperationsAdmin => 'admin.dashboard',
            self::User => 'dashboard',
        };
    }

    /**
     * Capability map for future fine-grained checks.
     * Super admin implicitly has everything; this list is for ops + documentation.
     *
     * @return list<string>
     */
    public function abilities(): array
    {
        return match ($this) {
            self::SuperAdmin => [
                'admin.access',
                'admin.users.manage',
                'admin.users.impersonate',
                'admin.content.manage',
                'admin.requests.manage',
                'admin.billing.manage',
                'admin.settings.manage',
                'admin.roles.manage',
                'admin.activity.view',
            ],
            self::OperationsAdmin => [
                'admin.access',
                'admin.content.manage',
                'admin.requests.manage',
                'admin.users.view',
            ],
            self::User => [],
        };
    }

    public function can(string $ability): bool
    {
        if ($this === self::SuperAdmin) {
            return true;
        }

        return in_array($ability, $this->abilities(), true);
    }
}
