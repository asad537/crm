<?php

namespace App\Console\Commands;

use App\CrmMailAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * crm:mail-repair-attachments — undo the UTF-8 "fix-up" that corrupted binary attachments
 * stored before the ImapClient fix (bytes >= 0x80 were re-encoded as 2-byte UTF-8 sequences).
 * Reversal (UTF-8 → ISO-8859-1) is exact for affected files; files that are not valid UTF-8,
 * or contain no multibyte sequences, were never touched and are skipped. Safe to re-run.
 */
class MailRepairAttachments extends Command
{
    protected $signature = 'crm:mail-repair-attachments {--dry-run : Report only}';
    protected $description = 'Restore binary mail attachments that were corrupted by charset conversion';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $fixed = 0; $skipped = 0; $missing = 0;
        foreach (CrmMailAttachment::orderBy('id')->lazy(200) as $a) {
            $mime = strtolower((string) $a->mime_type);
            if (str_starts_with($mime, 'text/') || str_starts_with($mime, 'message/')) { $skipped++; continue; }
            $disk = Storage::disk($a->disk ?: 'local');
            if (!$a->path || !$disk->exists($a->path)) { $missing++; continue; }
            $bytes = $disk->get($a->path);
            if ($bytes === '' || !preg_match('/[\x80-\xFF]/', $bytes) || !mb_check_encoding($bytes, 'UTF-8')) { $skipped++; continue; }
            $restored = mb_convert_encoding($bytes, 'ISO-8859-1', 'UTF-8');
            if ($restored === false || strlen($restored) >= strlen($bytes)) { $skipped++; continue; }
            $fixed++;
            $this->line(($dry ? 'would fix ' : 'fixed ') . "#{$a->id} {$a->original_name} ({$mime}) " . strlen($bytes) . ' → ' . strlen($restored) . ' bytes');
            if ($dry) continue;
            $disk->put($a->path, $restored);
            $a->forceFill(['size' => strlen($restored)])->saveQuietly();
        }
        $this->info(($dry ? 'Would fix ' : 'Fixed ') . "$fixed attachments; skipped $skipped (fine); missing files $missing.");
        return self::SUCCESS;
    }
}
