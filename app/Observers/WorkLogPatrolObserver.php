<?php

namespace App\Observers;

use App\Jobs\ScanWorkLogForPatrol;
use App\Models\WorkLog;

class WorkLogPatrolObserver
{
    public function created(WorkLog $workLog): void
    {
        ScanWorkLogForPatrol::dispatch($workLog->id);
    }

    public function updated(WorkLog $workLog): void
    {
        if ($workLog->wasChanged('review_requested_at')) {
            ScanWorkLogForPatrol::dispatch($workLog->id);
        }
    }
}
