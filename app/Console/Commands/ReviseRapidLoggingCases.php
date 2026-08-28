<?php

namespace App\Console\Commands;

use App\Support\Patrol\PatrolCaseService;
use Illuminate\Console\Command;

class ReviseRapidLoggingCases extends Command
{
    protected $signature = 'patrol:revise-rapid-logging';

    protected $description = 'Dismiss or detach rapid_logging cases the revised rule no longer matches. Never deletes cases.';

    public function handle(PatrolCaseService $cases): int
    {
        $result = $cases->reviseStaleRapidLogging();

        $this->info(sprintf(
            'Rapid logging revision finished. %d case%s dismissed, %d rule attachment%s dropped, %d job%s restored to public.',
            $result['dismissed'],
            $result['dismissed'] === 1 ? '' : 's',
            $result['detached'],
            $result['detached'] === 1 ? '' : 's',
            $result['restored'],
            $result['restored'] === 1 ? '' : 's',
        ));

        return self::SUCCESS;
    }
}
