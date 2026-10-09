# Mail client — deployment runbook

Applies to the Outlook-style multi-account mail client (phases 0–5, commits `fe81bb6` … `be58402`).
Everything is **additive**: existing lead/inquiry flows keep working; legacy `crm_users.email_*`
columns and the old `crm:fetch-emails` / `crm:imap-daemon` commands are untouched.

## 0. Before you start (production)
1. **Back up the database** and verify you can restore it.
2. **Back up `APP_KEY`** (`.env`). Mailbox passwords are stored with Laravel's `encrypted` cast —
   losing the key makes every stored mailbox password unrecoverable.
3. PHP **8.2** with `ext-imap` (as in `docker/Dockerfile`). Redis is used for cache/queue/locks.

## 1. Code + dependencies
```bash
git pull origin main
composer install --no-dev --optimize-autoloader --ignore-platform-req=php   # pint in the lock wants PHP 8.3; runtime is 8.2
php artisan config:cache && php artisan route:cache && php artisan view:clear
```
New package: `symfony/html-sanitizer` (inbound HTML is sanitised before storage).

## 2. Database (path-scoped — the migrations table is out of sync with the legacy schema)
```bash
php artisan migrate --force --path=database/migrations/2026_10_09_000001_create_crm_mail_tables.php
```
Creates `crm_mail_accounts`, `crm_mail_folders`, `crm_mail_threads`, `crm_mail_messages` (+FULLTEXT),
`crm_mail_attachments`, and adds nullable `crm_emails.mail_account_id`. Guarded with `hasTable/hasColumn`;
reversible with `migrate:rollback --path=...`.

## 3. Migrate existing per-user mailboxes (one encrypted account per user with legacy creds)
```bash
php artisan crm:mail-migrate-legacy-accounts --dry-run   # review the report
php artisan crm:mail-migrate-legacy-accounts             # apply (idempotent; --rollback removes only what it created)
```
Legacy columns are **not** modified. Leads assigned to a user are linked to that user's account;
website/form leads and leads assigned to deleted users stay unlinked (reported).

## 4. Background sync + queue
The scheduler runs `crm:mail-sync` every minute (`app/Console/Kernel.php`, `withoutOverlapping`, per-account
`Cache::lock`). Make sure a scheduler is running:
- **Docker:** `docker compose up -d worker` (new service: `schedule:work` + `queue:work redis`).
- **Bare server:** cron `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`.

First sync per folder pulls the last **30 days** (junk/trash 7), capped at 200 messages per folder per run,
then only new UIDs. Manual: `php artisan crm:mail-sync --account=<id> [--since=7 --cap=50 --types=inbox,sent]`.

Once the new sync is live, **stop the old cron** for `crm:imap-daemon` (inbound lead replies are now mirrored
by the new sync; running both is harmless but doubles IMAP traffic).

## 5. Smoke test
1. Log in as a CSR → **Mail** → the migrated mailbox is listed; Inbox fills after the first minute.
2. Open a message → Reply → it leaves from that mailbox's SMTP; a copy appears in Sent (DB + IMAP).
3. Admin/Sales Manager sees shared mailboxes **read-only** (no composer).
4. `storage/logs/laravel.log`: look for `Mail sync failed` / `IMAP open failed` (raw server errors are
   logged there only; the UI shows safe messages). `crm_mail_accounts.last_sync_error` shows per-account status.

## 6. Rollback
- Hide the feature: the Mail page is the old Chats route; revert the view commits if needed. The read/send
  APIs are owner-scoped and inert without accounts.
- `php artisan crm:mail-migrate-legacy-accounts --rollback` (only before any mail was synced into those accounts).
- `php artisan migrate:rollback --path=database/migrations/2026_10_09_000001_create_crm_mail_tables.php`
  drops the new tables/column (synced mail is lost — prefer disabling sync + hiding the UI instead).

## 7. Login step B (local authentication) — how to roll it out safely
Code (commit after `dc74bdf`): local bcrypt is checked **first** for everyone; for sales-only accounts a legacy
mailbox (IMAP) check remains as a **guarded fallback** that re-hashes the password locally on success and records
`crm_users.imap_login_fallback_at`. Self-service **Forgot password?** (email link, `crm` password broker) and
`php artisan crm:user-password <email> [--generate]` cover recovery; admins can also set a password in Edit User.

1. `php artisan migrate --force --path=database/migrations/2026_10_09_000002_add_login_audit_columns_to_crm_users.php`
2. Make sure the global mailer works (reset links are sent with it) and `APP_URL` is correct (links in the email).
3. Deploy with the fallback **enabled** (default `CRM_IMAP_LOGIN_FALLBACK=true`). Nobody is locked out.
4. After a few weeks run `php artisan crm:auth-audit --days=30`. When it reports "Safe to complete step B"
   (every sales user has a local password and nobody needed the fallback), set `CRM_IMAP_LOGIN_FALLBACK=false`,
   `php artisan config:cache`. From then on CRM login never touches a mailbox.
5. Legacy `crm_users.email_pass` is no longer written anywhere; it can be nulled/dropped in a later cleanup once
   `crm:imap-daemon` / `crm:fetch-emails` are retired (they still read it).

## 8. Still pending (next phases)
- Phase 6: server-side folder actions (archive/trash/move, flag sync to IMAP).
- Phase 8: broader automated tests (sync idempotency with a mocked IMAP, threading, send routing).
