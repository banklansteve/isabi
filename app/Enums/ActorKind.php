<?php

namespace App\Enums;

enum ActorKind: string
{
    case Customer = 'customer';
    case Staff = 'staff';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Staff => 'Staff',
            self::System => 'System',
        };
    }

    public static function fromUser(?\App\Models\User $user): self
    {
        if (! $user) {
            return self::System;
        }

        return $user->isStaff() ? self::Staff : self::Customer;
    }
}
