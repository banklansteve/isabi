<?php

namespace App\Support\Patrol\Rules;

use App\Models\WorkLog;
use App\Support\Patrol\PatrolRule;
use App\Support\Patrol\PatrolText;

class GenericMinimalRule implements PatrolRule
{
    public function key(): string
    {
        return 'generic_minimal';
    }

    public function evaluate(WorkLog $log): ?array
    {
        $config = config('patrol.rules.generic_minimal');
        $minChars = max(4, (int) ($config['min_chars'] ?? 12));
        $phrases = collect($config['phrases'] ?? [])
            ->map(fn ($phrase) => PatrolText::normalizeDescription((string) $phrase))
            ->filter()
            ->values();

        $normalized = PatrolText::normalizeDescription($log->description);
        $length = mb_strlen($normalized);

        if ($length > 0 && $length < $minChars) {
            return [
                'trigger' => sprintf('description is only %d characters (minimum %d)', $length, $minChars),
                'reason' => 'too_short',
                'length' => $length,
                'min_chars' => $minChars,
                'sample' => $normalized,
            ];
        }

        if ($phrases->contains($normalized)) {
            return [
                'trigger' => sprintf('description is a generic phrase: “%s”', $normalized),
                'reason' => 'generic_phrase',
                'sample' => $normalized,
            ];
        }

        return null;
    }
}
