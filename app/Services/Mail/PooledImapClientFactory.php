<?php

namespace App\Services\Mail;

use App\CrmMailAccount;

/**
 * Keeps one persistent IMAP connection per account for the long-running watcher, so each
 * round costs a few STATUS calls instead of a fresh login per mailbox. A client whose sync
 * errors is evicted and reconnected on the next round.
 *
 * IMPORTANT: PHP's imap_open caches streams per process and will hand a NEW imap_open() an
 * already-open stream to the SAME host, ignoring the different username/password. With several
 * mailboxes on one provider (e.g. many @hostinger.com accounts) kept open at once, one account
 * would then read ANOTHER account's mailbox and store its mail under the wrong account. So we
 * keep at most one open connection PER HOST in this process: before handing out a client we
 * shut down any other account's client on the same host. The watcher processes accounts one at
 * a time, so each still gets its own authenticated stream; only same-host accounts reconnect
 * each round (correctness over the small persistence saving).
 */
class PooledImapClientFactory extends ImapClientFactory
{
    /** @var array<int, ImapClient> */
    private array $pool = [];

    /** @var array<int, string> accountId => imap host, to find same-host siblings */
    private array $hostFor = [];

    public function make(CrmMailAccount $account): ImapClient
    {
        $key = (int) $account->id;
        $host = strtolower(trim((string) $account->imap_host));

        // Close any OTHER account's connection to the same host first (see class note).
        foreach ($this->hostFor as $otherId => $otherHost) {
            if ($otherId !== $key && $otherHost === $host) {
                $this->evict($otherId);
            }
        }

        if (!isset($this->pool[$key])) {
            $this->pool[$key] = (new ImapClient($account))->setPersistent(true);
        }
        $this->hostFor[$key] = $host;
        return $this->pool[$key];
    }

    public function evict(int $accountId): void
    {
        if (isset($this->pool[$accountId])) {
            $this->pool[$accountId]->shutdown();
            unset($this->pool[$accountId]);
        }
        unset($this->hostFor[$accountId]);
    }

    public function shutdownAll(): void
    {
        foreach ($this->pool as $client) {
            $client->shutdown();
        }
        $this->pool = [];
        $this->hostFor = [];
    }
}
