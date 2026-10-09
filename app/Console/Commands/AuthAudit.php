<?php

namespace App\Console\Commands;

use App\CrmUser;
use Illuminate\Console\Command;

/**
 * crm:auth-audit — who still depends on the legacy mailbox (IMAP) login, who has a local
 * password, and whether the IMAP fallback can be switched off (CRM_IMAP_LOGIN_FALLBACK=false).
 */
class AuthAudit extends Command
{
    protected $signature = 'crm:auth-audit {--days=30 : Window for "recent" fallback use / activity}';
    protected $description = 'Audit CRM login mode per user (local bcrypt vs legacy IMAP fallback) and advise on step B';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $rows = [];
        $needFallback = 0; $recentFallback = 0; $noLocal = 0; $imapUsers = 0; $notYetLocal = []; $dormant = [];

        foreach (CrmUser::with('workspaces')->orderBy('id')->get() as $u) {
            $roles = $u->workspaces->pluck('pivot.role')->unique()->values()->all();
            $imapEligible = !empty($roles) && empty(array_diff($roles, ['sales', 'sales_manager'])) && !str_ends_with(strtolower($u->email), '@example.com');
            $hasLocal = !empty($u->password) && str_starts_with($u->password, '$2y$');
            $fb = $u->imap_login_fallback_at;
            $recent = $fb && $fb->gt(now()->subDays($days));
            if ($imapEligible) {
                $imapUsers++; if (!$hasLocal) $noLocal++; if ($fb) $needFallback++; if ($recent) $recentFallback++;
                $isDormant = !$u->last_seen_at || $u->last_seen_at->lt(now()->subDays(90));
                if ($isDormant) { $dormant[] = $u->email; }
                // "proved local": logged in since step B without the fallback (last_login_at set, and the last
                // fallback — if any — is older than the last login)
                $provedLocal = $u->last_login_at && (!$fb || $fb->lt($u->last_login_at));
                if (!$isDormant && !$provedLocal) { $notYetLocal[] = $u->email; }
            }
            $rows[] = [
                $u->id, mb_strimwidth($u->name, 0, 18, '…'), mb_strimwidth($u->email, 0, 30, '…'), implode(',', $roles) ?: '-',
                $imapEligible ? 'yes' : 'no', $hasLocal ? 'yes' : 'NO',
                optional($u->last_login_at)->format('Y-m-d') ?: '-', optional($fb)->format('Y-m-d') ?: '-',
                optional($u->password_changed_at)->format('Y-m-d') ?: '-', optional($u->last_seen_at)->format('Y-m-d') ?: '-',
            ];
        }

        $this->table(['id', 'name', 'email', 'roles', 'imap-eligible', 'local pw', 'last login', 'imap fallback', 'pw changed', 'last seen'], $rows);
        $this->newLine();
        $this->info("IMAP-eligible (sales-only) users: {$imapUsers}; without a local password: {$noLocal}; ever used fallback: {$needFallback}; used fallback in last {$days} days: {$recentFallback}");
        $this->line('Fallback currently ' . (config('crm.auth.imap_login_fallback', true) ? 'ENABLED' : 'DISABLED') . ' (CRM_IMAP_LOGIN_FALLBACK).');
        if ($dormant) {
            $this->line('Dormant (>90 days no activity, excluded from the decision): ' . implode(', ', $dormant));
        }
        if ($noLocal === 0 && $recentFallback === 0 && empty($notYetLocal)) {
            $this->info("✔ Safe to complete step B: set CRM_IMAP_LOGIN_FALLBACK=false. Every active sales user has signed in locally since step B and nobody needed the fallback in {$days} days. Users who forget can use \"Forgot password?\".");
        } else {
            $this->warn('✖ Keep the fallback on for now.');
            if ($notYetLocal) $this->warn('  Active sales users who have not yet signed in locally since step B: ' . implode(', ', $notYetLocal));
            if ($recentFallback) $this->warn("  {$recentFallback} user(s) needed the mailbox fallback in the last {$days} days (their local hash was stale).");
            if ($noLocal) $this->warn("  {$noLocal} user(s) have no local password — reset via Edit User, crm:user-password, or Forgot password.");
        }
        return 0;
    }
}
