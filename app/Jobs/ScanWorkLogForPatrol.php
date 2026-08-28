<?php

namespace App\Jobs;

use App\Models\WorkLog;
use App\Support\Patrol\PatrolRunner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ScanWorkLogForPatrol implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $workLogId) {}

    public function handle(PatrolRunner $runner): void
    {
        $log = WorkLog::query()->with(['media', 'user'])->find($this->workLogId);
        if (! $log) {
            return;
        }

        $runner->scanWorkLog($log);
    }
}
