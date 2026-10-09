<?php

namespace App\Services\Mail;

use App\CrmMailAccount;

/**
 * Keeps one persistent IMAP connection per account for the long-running watcher, so each
 * round costs a few STATUS calls instead of a fresh login per mailbox. A client whose sync
 * errors is evicted and reconnected on the next round.
 */
class PooledImapClientFactory extends ImapClientFactory
{
    /** @var array<int, ImapClient> */
    private array $pool = [];

    public function make(CrmMailAccount $account): ImapClient
    {
        $key = (int) $account->id;
        if (!isset($this->pool[$key])) {
            $this->pool[$key] = (new ImapClient($account))->setPersistent(true);
        }
        return $this->pool[$key];
    }

    public function evict(int $accountId): void
    {
        if (isset($this->pool[$accountId])) {
            $this->pool[$accountId]->shutdown();
            unset($this->pool[$accountId]);
        }
    }

    public function shutdownAll(): void
    {
        foreach ($this->pool as $client) {
            $client->shutdown();
        }
        $this->pool = [];
    }
}
