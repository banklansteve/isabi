<?php

namespace App\Console\Commands;

use App\Support\Patrol\PatrolRunner;
use App\Support\Patrol\ReviewPatrolRunner;
use Illuminate\Console\Command;

class ScanPatrolCases extends Command
{
    protected $signature = 'patrol:scan
        {--work-log= : Scan a single work log id}
        {--review= : Scan a single review id}
        {--limit=2000 : Max records to scan per kind}';

    protected $description = 'Scan artisan job logs and reviews and open or update patrol cases. Never auto-resolves.';

    public function handle(PatrolRunner $jobs, ReviewPatrolRunner $reviews): int
    {
        $workLogId = $this->option('work-log') ? (int) $this->option('work-log') : null;
        $reviewId = $this->option('review') ? (int) $this->option('review') : null;
        $limit = max(1, (int) $this->option('limit'));

        $jobCases = $reviewId && ! $workLogId ? [] : $jobs->scan($workLogId, $limit);
        $reviewCases = $workLogId && ! $reviewId ? [] : $reviews->scan($reviewId, $limit);

        $this->info(sprintf(
            'Patrol scan finished. %d job case%s and %d review case%s touched. No cases were auto-resolved.',
            count($jobCases),
            count($jobCases) === 1 ? '' : 's',
            count($reviewCases),
            count($reviewCases) === 1 ? '' : 's',
        ));

        return self::SUCCESS;
    }
}
