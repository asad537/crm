<?php

namespace App\Console\Commands;

use App\CrmMailAccount;
use App\CrmMailAttachment;
use App\CrmMailMessage;
use App\Services\Mail\HtmlSanitizerService;
use App\Services\Mail\ImapClientFactory;
use Illuminate\Console\Command;

/**
 * crm:mail-repair-inline-images — messages synced before the cid: fix lost the src of their
 * inline images. Re-fetch their HTML from IMAP and store it again (sanitised with placeholders).
 */
class MailRepairInlineImages extends Command
{
    protected $signature = 'crm:mail-repair-inline-images {--account= : Only this account id} {--limit=500}';
    protected $description = 'Re-fetch HTML for messages whose inline (cid) images were stripped';

    public function handle(ImapClientFactory $clients, HtmlSanitizerService $sanitizer): int
    {
        $ids = CrmMailAttachment::whereNotNull('content_id')->distinct()->pluck('message_id');
        $q = CrmMailMessage::withoutGlobalScopes()->whereIn('id', $ids)->whereNotNull('uid')
            ->where('html_body', 'like', '%<img%')->where('html_body', 'not like', '%' . HtmlSanitizerService::CID_HOST . '%');
        if ($this->option('account')) $q->where('account_id', (int) $this->option('account'));
        $messages = $q->orderBy('account_id')->limit((int) $this->option('limit'))->get();
        $this->info("Messages to repair: {$messages->count()}");

        $fixed = 0; $failed = 0; $client = null; $currentAccount = null;
        foreach ($messages as $m) {
            try {
                if (!$currentAccount || $currentAccount->id !== $m->account_id) {
                    if ($client) $client->close();
                    $currentAccount = CrmMailAccount::withoutGlobalScopes()->find($m->account_id);
                    if (!$currentAccount) { $failed++; continue; }
                    $client = $clients->make($currentAccount);
                }
                $folder = $m->folder()->withoutGlobalScopes()->first();
                if (!$folder) { $failed++; continue; }
                $parsed = $client->fetchMessage($folder->path, (int) $m->uid);
                if (empty($parsed['html'])) { continue; }
                $m->forceFill(['html_body' => $sanitizer->sanitize($parsed['html'])])->saveQuietly();
                $fixed++;
            } catch (\Throwable $e) {
                $failed++;
                $this->warn("  #{$m->id}: " . $e->getMessage());
            }
        }
        if ($client) $client->close();
        $this->info("Repaired: {$fixed}, failed: {$failed}");
        return 0;
    }
}
