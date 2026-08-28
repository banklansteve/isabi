<?php

namespace App\Support\Patrol;

use App\Models\WorkLog;

interface PatrolRule
{
    public function key(): string;

    /**
     * @return array<string, mixed>|null
     */
    public function evaluate(WorkLog $log): ?array;
}
