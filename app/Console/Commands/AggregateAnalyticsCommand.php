<?php

namespace App\Console\Commands;

use App\Support\Analytics\AnalyticsAggregator;
use Illuminate\Console\Command;

class AggregateAnalyticsCommand extends Command
{
    protected $signature = 'analytics:aggregate
                            {--days=1 : How many trailing days to (re)aggregate, including today}
                            {--backfill= : Full backfill of N days (overrides --days)}
                            {--prune : Prune raw analytics_events older than retention window}';

    protected $description = 'Roll raw analytics_events into daily summary tables for product reports';

    public function handle(AnalyticsAggregator $aggregator): int
    {
        if ($backfill = $this->option('backfill')) {
            $days = max(1, (int) $backfill);
            $this->info("Backfilling {$days} days…");
            $aggregator->backfill($days);
            $this->info('Backfill complete.');
        } else {
            $days = max(1, (int) $this->option('days'));
            for ($i = $days - 1; $i >= 0; $i--) {
                $day = now()->subDays($i);
                $aggregator->aggregateDay($day);
                $this->line('Aggregated '.$day->toDateString());
            }
            $aggregator->recomputeRetentionCohorts(12);
        }

        if ($this->option('prune') || ! $this->option('backfill')) {
            $deleted = $aggregator->pruneRawEvents();
            $this->info('Pruned '.$deleted.' raw analytics_events older than '.AnalyticsAggregator::rawRetentionDays().' days.');
        }

        return self::SUCCESS;
    }
}
