<?php

namespace App\Jobs;

use App\CrmMailAccount;
use App\Services\Mail\ImapSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/** Import older mail for one mailbox in the background (resumable; re-dispatches itself until done). */
class BackfillMailAccountJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 3500;
    public int $uniqueFor = 3600;

    public function __construct(public int $accountId, public ?int $sinceDays = 365, public int $maxMessages = 2000)
    {
    }

    public function uniqueId(): string
    {
        return 'backfill-' . $this->accountId;
    }

    public function handle(ImapSyncService $sync): void
    {
        $account = CrmMailAccount::withoutGlobalScopes()->syncable()->find($this->accountId);
        if (!$account) return;
        $r = $sync->backfillAccount($account, $this->sinceDays, $this->maxMessages);
        Log::info('Mail backfill batch', ['account_id' => $account->id] + $r);
        if ($r['status'] === 'ok' && !$r['done']) {
            // more to do: continue in a fresh job (keeps each run bounded). A batch that made no
            // progress because a folder errored (e.g. dropped IMAP connection) is retried after a
            // pause, and given up after 5 such batches in a row so a dead mailbox cannot loop forever.
            $failKey = 'mail:backfill:fails:' . $this->accountId;
            $noProgress = !empty($r['errors']) && ($r['imported'] + $r['skipped']) === 0;
            $fails = $noProgress ? (int) Cache::increment($failKey) : 0;
            if (!$noProgress) Cache::forget($failKey);
            if ($fails >= 5) {
                Log::warning('Mail backfill: giving up after repeated failures', ['account_id' => $account->id, 'errors' => $r['errors']]);
                Cache::forget($failKey);
                return;
            }
            static::dispatch($this->accountId, $this->sinceDays, $this->maxMessages)->delay(now()->addSeconds($noProgress ? 120 : 5));
        }
    }
}
