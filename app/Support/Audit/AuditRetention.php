<?php

namespace App\Support\Audit;

use App\Enums\RetentionTier;

class AuditRetention
{
    public static function tierForAction(string $action, bool $staffSide = false): RetentionTier
    {
        if ($staffSide) {
            return RetentionTier::Staff;
        }

        $financial = config('audit.retention.financial_actions', []);

        if (in_array($action, $financial, true)) {
            return RetentionTier::Financial;
        }

        return RetentionTier::StandardPublic;
    }
}
