<?php

namespace App\Console\Commands;

use App\Support\Quotes\QuoteExpiryService;
use Illuminate\Console\Command;

class ProcessQuoteExpiryCommand extends Command
{
    protected $signature = 'quotes:process-expiry';

    protected $description = 'Expire due quotes and email looming-expiry nudges to both parties';

    public function handle(QuoteExpiryService $expiry): int
    {
        $nudged = $expiry->sendLoomingNudges();
        $expired = $expiry->expireDue();

        $this->info("Expiry nudges sent: {$nudged}");
        $this->info("Quotes expired: {$expired}");

        return self::SUCCESS;
    }
}
