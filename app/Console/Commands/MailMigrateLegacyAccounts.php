<?php

namespace App\Console\Commands;

use App\CrmEmail;
use App\CrmMailAccount;
use App\CrmUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Phase 1 data migration: copy each CRM user's single legacy mailbox
 * (crm_users.email_user/email_pass, imap_* and smtp_* settings, signature) into one
 * crm_mail_accounts row (password encrypted), and link already-assigned
 * leads (crm_emails.assigned_to) to that account via crm_emails.mail_account_id.
 *
 * Legacy columns are NOT touched (compatibility period). Idempotent:
 * re-running skips users that already have an account for that address.
 *
 *   php artisan crm:mail-migrate-legacy-accounts --dry-run
 *   php artisan crm:mail-migrate-legacy-accounts
 *   php artisan crm:mail-migrate-legacy-accounts --rollback   (removes only rows this command created,
 *                                                              and only if they hold no synced mail yet)
 */
class MailMigrateLegacyAccounts extends Command
{
    protected $signature = 'crm:mail-migrate-legacy-accounts
                            {--dry-run : Report what would change without writing}
                            {--rollback : Remove accounts created by this command (safe only before sync)}';

    protected $description = 'Copy legacy per-user mailbox credentials into crm_mail_accounts and link assigned leads';

    public function handle(): int
    {
        if ($this->option('rollback')) {
            return $this->rollback();
        }

        $dry = (bool) $this->option('dry-run');

        $users = CrmUser::query()
            ->whereNotNull('email_user')->where('email_user', '!=', '')
            ->whereNotNull('email_pass')->where('email_pass', '!=', '')
            ->orderBy('id')
            ->get();

        $this->info(($dry ? '[DRY RUN] ' : '') . "Legacy users with mailbox credentials: {$users->count()}");

        $created = 0; $skipped = 0; $linked = 0; $unlinkedOwners = [];

        DB::beginTransaction();
        try {
            foreach ($users as $user) {
                $email = strtolower(trim((string) $user->email_user));

                $existing = CrmMailAccount::withoutGlobalScopes()
                    ->where('crm_user_id', $user->id)
                    ->where('email_address', $email)
                    ->first();

                if ($existing) {
                    $skipped++;
                    $account = $existing;
                } else {
                    $workspaceId = optional($user->workspaces()->first())->id;
                    $hasDefault  = CrmMailAccount::withoutGlobalScopes()
                        ->where('crm_user_id', $user->id)->where('is_default', true)->exists();

                    $account = new CrmMailAccount([
                        'workspace_id'         => $workspaceId,
                        'crm_user_id'          => $user->id,
                        'email_address'        => $email,
                        'display_name'         => $user->name,
                        'provider'             => CrmMailAccount::inferProvider($user->imap_host ?: $user->smtp_host),
                        'auth_type'            => 'password',
                        'imap_host'            => $user->imap_host ?: 'imap.hostinger.com',
                        'imap_port'            => (int) ($user->imap_port ?: 993),
                        'imap_encryption'      => $user->imap_encryption ?: 'ssl',
                        'smtp_host'            => $user->smtp_host ?: 'smtp.hostinger.com',
                        'smtp_port'            => (int) ($user->smtp_port ?: 587),
                        'smtp_encryption'      => $user->smtp_encryption ?: 'tls',
                        'email_user'           => $user->email_user,
                        'email_pass'           => $user->email_pass,   // encrypted by the model cast
                        'signature'            => $user->signature,
                        'is_active'            => true,
                        'is_default'           => !$hasDefault,
                        'sync_enabled'         => true,
                        'share_with_admin'     => true,
                        'migrated_from_legacy' => true,
                    ]);
                    $account->save();
                    $created++;
                    $this->line("  + account #{$account->id} for user #{$user->id} {$user->name} <{$email}>");
                }

                // Link assigned leads only when the owner has exactly one account (unambiguous).
                $accountCount = CrmMailAccount::withoutGlobalScopes()->where('crm_user_id', $user->id)->count();
                if ($accountCount === 1) {
                    $n = CrmEmail::withoutGlobalScopes()
                        ->where('assigned_to', $user->id)
                        ->whereNull('mail_account_id')
                        ->update(['mail_account_id' => $account->id]);
                    $linked += $n;
                } else {
                    $unlinkedOwners[] = "user #{$user->id} has {$accountCount} accounts";
                }
            }

            $unassigned = CrmEmail::withoutGlobalScopes()->whereNull('mail_account_id')->count();

            if ($dry) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed, rolled back: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info("Accounts created: {$created}, already present: {$skipped}");
        $this->info("Leads linked to an account: {$linked}");
        $this->warn("Leads still without a mailbox link (website/form/manual or unassigned): {$unassigned}");
        foreach ($unlinkedOwners as $note) {
            $this->warn("  ! not linked (ambiguous): {$note}");
        }
        if ($dry) {
            $this->comment('Dry run — nothing was written.');
        }
        return 0;
    }

    private function rollback(): int
    {
        $accounts = CrmMailAccount::withoutGlobalScopes()->where('migrated_from_legacy', true)->get();
        $this->info("Accounts created by this command: {$accounts->count()}");

        $removed = 0; $kept = 0;
        DB::transaction(function () use ($accounts, &$removed, &$kept) {
            foreach ($accounts as $account) {
                if ($account->messages()->withoutGlobalScopes()->exists() || $account->folders()->withoutGlobalScopes()->exists()) {
                    $kept++;
                    $this->warn("  ! kept #{$account->id} <{$account->email_address}> — it already holds synced mail");
                    continue;
                }
                CrmEmail::withoutGlobalScopes()->where('mail_account_id', $account->id)->update(['mail_account_id' => null]);
                $account->forceDelete();
                $removed++;
            }
        });

        $this->info("Removed: {$removed}, kept: {$kept}. Legacy crm_users columns were never modified.");
        return 0;
    }
}
