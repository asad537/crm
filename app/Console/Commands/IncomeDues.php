<?php

namespace App\Console\Commands;

use App\Services\IncomeDueService;
use Illuminate\Console\Command;

/** crm:income-dues — create this month's due rows for rent / other-income customers (runs daily). */
class IncomeDues extends Command
{
    protected $signature = 'crm:income-dues';
    protected $description = 'Generate monthly dues for other-income (rent etc.) customers';

    public function handle(IncomeDueService $svc): int
    {
        $n = $svc->ensureAll();
        $this->info("Created {$n} due row(s).");
        return self::SUCCESS;
    }
}
