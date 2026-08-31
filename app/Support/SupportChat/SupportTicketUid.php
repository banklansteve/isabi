<?php

namespace App\Support\SupportChat;

use App\Models\SupportTicket;
use Illuminate\Support\Str;
use RuntimeException;

class SupportTicketUid
{
    public static function generate(): string
    {
        return (string) Str::uuid();
    }

    public static function unique(): string
    {
        for ($attempt = 0; $attempt < 32; $attempt++) {
            $uid = self::generate();

            if (! SupportTicket::query()->where('uid', $uid)->exists()) {
                return $uid;
            }
        }

        throw new RuntimeException('Could not allocate a unique support ticket uid.');
    }

    public static function isValid(?string $uid): bool
    {
        return is_string($uid) && Str::isUuid($uid);
    }
}
