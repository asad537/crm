<?php

namespace App\Console\Commands;

use App\CrmMailAccount;
use App\Jobs\SyncMailAccountJob;
use App\Services\Mail\ImapSyncService;
use Illuminate\Console\Command;

/**
 * crm:mail-sync — pull new mail for every active, sync-enabled mailbox into crm_mail_*.
 * Scheduled every minute (Kernel). Use --queue to dispatch one job per account instead
 * of syncing inline (requires a queue worker).
 */
class MailSync extends Command
{
    protected $signature = 'crm:mail-sync
                            {--account= : Only this crm_mail_accounts.id}
                            {--queue : Dispatch SyncMailAccountJob per account instead of running inline}
                            {--since=30 : On a first sync, how many days back to pull}
                            {--cap=200 : Max messages per folder per run}
                            {--types= : Comma-separated folder types to sync (inbox,sent,drafts,archive,junk,trash,custom)}';

    protected $description = 'Sync connected mailboxes (IMAP) into the CRM mail client';

    public function handle(ImapSyncService $sync): int
    {
        $query = CrmMailAccount::withoutGlobalScopes()->syncable()->orderBy('id');
        if ($this->option('account')) {
            $query->where('id', (int) $this->option('account'));
        }
        $accounts = $query->get();
        if ($accounts->isEmpty()) {
            $this->warn('No syncable mail accounts.');
            return 0;
        }

        $opts = array_filter([
            'since_days' => (int) $this->option('since'),
            'cap' => (int) $this->option('cap'),
            'types' => $this->option('types') ? array_filter(array_map('trim', explode(',', $this->option('types')))) : null,
        ]);

        foreach ($accounts as $account) {
            if ($this->option('queue')) {
                SyncMailAccountJob::dispatch($account->id, $opts);
                $this->line("queued  #{$account->id} {$account->email_address}");
                continue;
            }
            $started = microtime(true);
            $r = $sync->syncAccount($account, $opts);
            $secs = number_format(microtime(true) - $started, 1);
            $line = sprintf('%-7s #%d %s — folders=%d imported=%d skipped=%d linked=%d mirrored=%d (%ss)',
                $r['status'], $account->id, $account->email_address, $r['folders'], $r['imported'], $r['skipped'], $r['linked'], $r['mirrored'], $secs);
            $r['status'] === 'ok' && !$r['errors'] ? $this->info($line) : $this->warn($line);
            foreach ($r['errors'] as $err) {
                $this->line('        ! ' . $err);
            }
        }
        return 0;
    }
}
