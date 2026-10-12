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
            // more to do: continue in a fresh job (keeps each run bounded)
            static::dispatch($this->accountId, $this->sinceDays, $this->maxMessages)->delay(now()->addSeconds(5));
        }
    }
}
