<?php

namespace App\Enums;

enum RetentionTier: string
{
    /** Public identity / work / quote events — hot 90d, total ~2 years. */
    case StandardPublic = 'standard_public';

    /** Purchases, credit adjustments, referral rewards — hot 90d, retain ≥6 years. */
    case Financial = 'financial';

    /** Staff, HR, moderation, discipline — hot 12 months, retain indefinitely. */
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::StandardPublic => 'Standard public',
            self::Financial => 'Financial',
            self::Staff => 'Staff / ops',
        };
    }

    /**
     * Hot window in days (queryable primary store). Archival/purge jobs come later.
     */
    public function hotDays(): int
    {
        return match ($this) {
            self::StandardPublic, self::Financial => 90,
            self::Staff => 365,
        };
    }

    /**
     * Total retention in days. Null means never purge.
     */
    public function retainDays(): ?int
    {
        return match ($this) {
            self::StandardPublic => 730,
            self::Financial => 2190,
            self::Staff => null,
        };
    }
}
