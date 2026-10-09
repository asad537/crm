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
                            {--types= : Comma-separated folder types to sync (inbox,sent,drafts,archive,junk,trash,custom)}
                            {--watch : Keep running: sync all accounts, sleep --interval seconds, repeat (near real-time receive)}
                            {--interval=15 : Seconds between rounds in --watch mode}';

    protected $description = 'Sync connected mailboxes (IMAP) into the CRM mail client';

    public function handle(ImapSyncService $sync): int
    {
        if ($this->option('watch')) {
            return $this->watch();
        }
        return $this->runOnce($sync);
    }

    /**
     * Long-running mode. Already-synced accounts are refreshed INLINE over persistent IMAP
     * connections (no repeated logins — an idle round is a handful of STATUS calls per
     * mailbox). Never-synced accounts (first 30-day backfill) are handed to queue workers so
     * they never delay the live refresh. --queue forces everything through the queue.
     */
    private function watch(): int
    {
        $interval = max(3, (int) $this->option('interval'));
        $pool = new \App\Services\Mail\PooledImapClientFactory();
        app()->instance(\App\Services\Mail\ImapClientFactory::class, $pool);
        /** @var ImapSyncService $sync */
        $sync = app()->make(ImapSyncService::class);
        $opts = $this->opts();
        $this->info("Watching mailboxes every {$interval}s — persistent connections, backfills queued (Ctrl+C to stop)…");

        while (true) {
            $started = microtime(true);
            $imported = 0; $errors = 0;
            try {
                $accounts = CrmMailAccount::withoutGlobalScopes()->syncable()->orderBy('id')->get();
                foreach ($accounts as $account) {
                    if (ImapSyncService::inBackoff($account->id)) {
                        continue; // paused after a failed login / error
                    }
                    if ($this->option('queue') || $account->last_synced_at === null) {
                        SyncMailAccountJob::dispatch($account->id, $opts); // unique per account
                        continue;
                    }
                    $r = $sync->syncAccount($account, $opts);
                    $imported += $r['imported'];
                    if ($r['status'] === 'error' || $r['errors']) {
                        $errors++;
                        $pool->evict($account->id); // reconnect next round
                    }
                }
            } catch (\Throwable $e) {
                $this->error('round failed: ' . $e->getMessage());
            }
            $secs = microtime(true) - $started;
            if ($imported || $errors) {
                $this->line(sprintf('%s round: imported=%d errors=%d (%.1fs)', now()->format('H:i:s'), $imported, $errors, $secs));
            }
            $sleep = max(1, $interval - (int) $secs);
            sleep($sleep);
        }
    }

    private function opts(): array
    {
        return array_filter([
            'since_days' => (int) $this->option('since'),
            'cap' => (int) $this->option('cap'),
            'types' => $this->option('types') ? array_filter(array_map('trim', explode(',', $this->option('types')))) : null,
        ]);
    }

    private function runOnce(ImapSyncService $sync): int
    {
        // Already-synced accounts first (cheap incremental passes), never-synced backfills last.
        $query = CrmMailAccount::withoutGlobalScopes()->syncable()->orderByRaw('last_synced_at IS NULL')->orderBy('last_synced_at')->orderBy('id');
        if ($this->option('account')) {
            $query->where('id', (int) $this->option('account'));
        }
        $accounts = $query->get();
        if ($accounts->isEmpty()) {
            $this->warn('No syncable mail accounts.');
            return 0;
        }

        $opts = $this->opts();

        foreach ($accounts as $account) {
            if ($this->option('queue')) {
                SyncMailAccountJob::dispatch($account->id, $opts); // unique per account: dropped if one is already pending/running
                if (!$this->option('watch')) $this->line("queued  #{$account->id} {$account->email_address}");
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
