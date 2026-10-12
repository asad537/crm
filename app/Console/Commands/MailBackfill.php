<?php

namespace App\Console\Commands;

use App\CrmMailAccount;
use App\Jobs\BackfillMailAccountJob;
use App\Services\Mail\ImapSyncService;
use Illuminate\Console\Command;

/**
 * crm:mail-backfill — import OLDER mail than the first sync pulled (which was 30 days).
 *   php artisan crm:mail-backfill --days=365          # last year, every syncable mailbox, inline
 *   php artisan crm:mail-backfill --days=all --queue  # everything, in background jobs
 */
class MailBackfill extends Command
{
    protected $signature = 'crm:mail-backfill
                            {--account= : Only this crm_mail_accounts.id}
                            {--days=365 : How far back (number of days, or "all")}
                            {--max=2000 : Max messages per run/batch}
                            {--queue : Run as background jobs (resumable, bounded batches)}';

    protected $description = 'Import older mailbox history into the CRM mail client';

    public function handle(ImapSyncService $sync): int
    {
        $days = $this->option('days') === 'all' ? null : max(1, (int) $this->option('days'));
        $max = max(50, (int) $this->option('max'));
        $q = CrmMailAccount::withoutGlobalScopes()->syncable()->whereNotNull('last_synced_at')->orderBy('id');
        if ($this->option('account')) $q->where('id', (int) $this->option('account'));
        $accounts = $q->get();
        if ($accounts->isEmpty()) { $this->warn('No synced mailboxes to backfill.'); return 0; }

        foreach ($accounts as $a) {
            if ($this->option('queue')) {
                BackfillMailAccountJob::dispatch($a->id, $days, $max);
                $this->line("queued  #{$a->id} {$a->email_address}" . ($days ? " ({$days} days)" : ' (all history)'));
                continue;
            }
            $t = microtime(true);
            $r = $sync->backfillAccount($a, $days, $max);
            $this->line(sprintf('%-7s #%d %s — folders=%d imported=%d skipped=%d done=%s (%.0fs)%s', $r['status'], $a->id, $a->email_address, $r['folders'], $r['imported'], $r['skipped'], $r['done'] ? 'yes' : 'no (run again)', microtime(true) - $t, $r['errors'] ? ' ! ' . implode(' | ', $r['errors']) : ''));
        }
        return 0;
    }
}
