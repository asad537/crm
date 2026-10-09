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

/** Sync one mailbox. Unique per account so overlapping dispatches collapse. */
class SyncMailAccountJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2; // idempotent: a job killed by a worker restart may run once more
    public int $timeout = 300;
    public int $uniqueFor = 300;

    public function __construct(public int $accountId, public array $opts = [])
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->accountId;
    }

    public function handle(ImapSyncService $sync): void
    {
        $account = CrmMailAccount::withoutGlobalScopes()->syncable()->find($this->accountId);
        if ($account) {
            $sync->syncAccount($account, $this->opts);
        }
    }
}
