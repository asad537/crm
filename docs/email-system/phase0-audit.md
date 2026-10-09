# Phase 0 — Codebase Audit: Outlook-Style Multi-Account Email System

**Repo:** `crm_l10` (Laravel CRM) · **Audit date:** 2026-10-09 · **Mode:** read-only (no files modified; this document is the only addition)
**Scope:** everything the Outlook-style email client will touch — users/auth/credentials, the email receive/send pipeline, data, infrastructure, and the frontend.

> Convention: every claim below cites `path:line`. "Legacy / no migration" means the table exists in the DB but no `Schema::create` for it exists in this repo.

---

## 0. Executive summary (read this first)

1. **The CRM does not have a mailbox today — it has a lead/inquiry table.** `crm_emails` is 94% website/form/manual inquiries (676 of 722 rows); only 46 rows ever came from IMAP. Inbound mail that is not a reply to an existing lead is **discarded** (`app/Console/Commands/FetchImapEmails.php:307-312`). Messages are stored as plain text with no subject/from/to/date/headers/HTML/attachments (`app/CrmMessage.php:13-21`).
   → **Recommendation:** build the mail client on **new `crm_mail_*` tables** (accounts, raw messages, attachments, threads, sync state) and *link* messages to leads via a nullable `crm_email_id`, instead of bolting folders/stars/threads onto `crm_emails`.
2. **Outgoing mail is NOT sent per user.** Despite `smtp_*` columns on `crm_users`, all mail goes through the single global mailer with `From = MAIL_FROM_ADDRESS` (`app/Mail/ClientMessage.php:73-76`, `app/Http/Controllers/Crm/EmailController.php:915-918,947`). `CustomMailServiceProvider` is empty. Only the *Sent copy* is appended to the agent's IMAP (`EmailController.php:2063-2127`). Per-mailbox SMTP transports must be built from scratch.
3. **CRM login is coupled to the mailbox password.** On every successful login the plaintext password is written to `crm_users.email_pass` (`app/Http/Controllers/Crm/AuthController.php:39-41`); `sales`/`sales_manager` can only authenticate via IMAP (`AuthController.php:100-105`). One user ↔ N mailboxes cannot coexist with this — login must be **decoupled** (local bcrypt only) before/with Phase 2.
4. **Credentials are plaintext and leak into HTML.** No `encrypted` cast or `Crypt` usage exists anywhere in `app/`; `email_pass` is not in `$hidden` (`app/CrmUser.php:21-23`) and is rendered into `resources/views/crm/users/edit.blade.php:316`.
5. **No authorization layer.** Zero Policies/Gates (`app/Providers/AuthServiceProvider.php:15-25`); every controller does inline `isAdmin()` checks; user management has cross-workspace IDOR (`UserManagementController.php:141,163,269`).
6. **Infra:** Laravel 10.50.3 on PHP 8.2 (Docker), `ext-imap` used directly (no IMAP package), **scheduler empty**, **queue = sync in code defaults** but local `.env` is Redis for cache/session/queue, **no daemon/worker service in docker-compose**, polling comes from an untracked cron + a synchronous HTTP `chat-sync`. Threading headers are effectively inert today (`imap_message_id` is never written).
7. **Frontend:** plain Blade + inline CSS/JS, custom design tokens, FA5, TinyMCE 6.8.4 self-hosted, and a **custom AJAX partial-navigation system** with strict rules for page scripts (§6.3). The Live Chat two-pane shell is directly reusable for the 3-pane client.
8. **Open decision (spec conflict):** the pasted spec says "Implement OAuth2 where required (Gmail/M365)"; the product owner previously chose **IMAP/SMTP password only**. Audit assumes password/app-password with a schema that leaves room for OAuth later (`auth_type` + nullable token columns). Needs confirmation before Phase 2.

---

## 1. Platform & infrastructure

| Item | Finding | Source |
|---|---|---|
| Laravel | `^10.10`, locked **v10.50.3** | `composer.json:12`, `composer.lock:1285-1286` |
| PHP | constraint `^8.1`; Docker image `php:8.2-fpm` with `imap` ext; **local host CLI is PHP 7.4.10** (cannot run artisan/tests outside Docker) | `composer.json:8`, `docker/Dockerfile:3,10` |
| Mail libs | `symfony/mailer` v6.4.44 + `symfony/mime` (transitive). **No** webklex/php-imap/ddeboer/phpmailer. `ext-imap` not declared in composer but used directly | `composer.lock:4429,4513` |
| Direct `ext-imap` callers | `FetchImapEmails.php`, `ImapDaemon.php`, `EmailController.php:2063-2127` (appendToImapSent), `AuthController.php:117` (login), `UserManagementController.php:251` (testConnection) | grep |
| HTML sanitizer | **none** (no purifier/html-sanitizer/DOMPurify) | composer, views |
| Rate limiting | `throttle` alias registered (`app/Http/Kernel.php:70`) but **applied to no CRM route** | `routes/web.php` |
| Queue | `config/queue.php:16` default `sync`; `app/Jobs/` absent; local `.env`: `QUEUE_CONNECTION=redis`, `CACHE_DRIVER=redis`, `SESSION_DRIVER=redis` | `.env` (driver keys only) |
| Scheduler | `app/Console/Kernel.php:13-16` — empty (commented `inspire`) | |
| Docker services | `app` (php-fpm), `nginx`, `redis:7`, `mysql:8.0`. **No worker/daemon/cron service.** No artisan process running in the `app` container locally | `docker-compose.yml:1-64`, `/proc` scan |
| Deploy | No CI, no supervisor/systemd/Procfile, README is stock Laravel → **production deploy & daemon launch procedure undocumented** | repo root |
| Storage disks | `local` → `storage/app` (private), `public`, `s3` configured | `config/filesystems.php:33-48` |
| Tests | PHPUnit 10 (+ pest plugin allowed); 6 feature + 4 unit tests; **run against the real DB** (sqlite lines commented in `phpunit.xml:24-25`), `MAIL_MAILER=array`; pattern = `DB::beginTransaction()/rollBack()` + `Storage::fake()` + reflection on private controller methods (`tests/Feature/DesignJobCardAttachmentsTest.php`) | |
| Migrations | 22 migration files use `->foreign()`; **0 fullText indexes**; new tables use `Schema::hasTable()` guards (`2026_10_07_000007_create_crm_portal_accounts_table.php:11`). **Migrations table is out of sync with the DB** — plain `artisan migrate` fails on old files; run new ones with `--path` (memory note, 2026-09-24) | |

---

## 2. Users, auth, roles, workspaces

### 2.1 `CrmUser` — `app/CrmUser.php`
- Extends `Illuminate\Foundation\Auth\User`, table `crm_users` (L12). **Legacy table, no create migration.**
- `$fillable` (L14-19): `name, email, password, role, production_facility_id, allowed_ip, imap_host, imap_port, imap_encryption, smtp_host, smtp_port, smtp_encryption, email_user, email_pass, signature`.
- `$hidden` (L21-23): only `password`, `remember_token` → **`email_pass` leaks on any `toArray()/toJson()`**.
- No `$casts`, no accessors/mutators. No `encrypted` cast anywhere in `app/`.
- Roles are **per-workspace** via pivot `crm_user_workspace.role`; `activeWorkspaceRole()` (L135-144) uses `CrmWorkspaceContext::id()` → session → legacy `crm_users.role`. Helpers L25-117: `isAdmin()` = admin **or** super_admin; `isSales`, `isSalesManager`, `isDesigner`, `isEstimator`, `isTeamLead`, `isAccounts`, … ; `canAssign()` L124.

### 2.2 Schema (reconstructed)
**`crm_users`** — base columns inferred (`id, name, email, password[bcrypt], role, allowed_ip, imap_host/port/encryption, smtp_host/port/encryption, email_user, email_pass[plaintext], remember_token, timestamps`); migrated columns: `last_seen_at` (`2026_05_17_044046`), `signature` text (`2026_06_08_000000:23`), `production_facility_id` FK (`2026_06_15_000003:16-18`), `proposal_access` bool (`2026_09_14_000005:13`), `print_ready_access` bool (`2026_10_07_000009:13`).
**`crm_user_workspace`** (`2026_07_16_000001_add_crm_workspaces.php:37-45`): `crm_user_id` FK cascade, `workspace_id` FK cascade, `role string(50)` (**no enum**; values enforced only by controller `in:` lists at `UserManagementController.php:94,181`), composite PK → one role per user per workspace.
**`crm_workspaces`** (same file L12-19): `id, name, slug unique, api_key_hash, is_active`. Seeded: `my-box-printing` (id 1), `mybox-packaging-app` (id 2).

### 2.3 Authentication — `app/Http/Controllers/Crm/AuthController.php`
- Guard `crm` (session) → provider `crm_users` → `App\CrmUser` (`config/auth.php:12,17`).
- `login()` L17-85 → `verifyImap()` L90-125: `@example.com` → local hash; if **all** pivot roles ∈ {sales, sales_manager} → **IMAP auth mandatory** (`imap_open(... OP_HALFOPEN)` against `imap_host` with Hostinger defaults, L107-117); otherwise local `Hash::check`.
- **L39-41:** on success, `password = Hash::make($password)` **and `email_pass = $password` (plaintext), for every user incl. admins.**
- `updatePassword()` L145-178 changes only the bcrypt hash → for sales roles the change is **silently ineffective** (next login re-verifies against the mailbox and overwrites).

### 2.4 Workspace context & scoping
- `app/Support/CrmWorkspaceContext.php` — static, request-scoped; **never set in console commands** (`FetchImapEmails`, `ImapDaemon`) → workspace global scopes are no-ops in CLI (cross-workspace matching).
- Middleware `crm.workspace` (`RequireCrmWorkspace`), `crm.workspace.slug:<slug>`, `crm.admin`, `crm.admin_manager`, `crm.ip` (`app/Http/Kernel.php:73-79`).
- Global scope pattern to copy: `app/CrmEmail.php:98-109` (scope + `creating` auto-fill of `workspace_id`). `CrmUser` is **not** globally scoped.

### 2.5 Authorization
- **No Policies, no Gates** (`AuthServiceProvider.php:15-25`). Inline role checks everywhere.
- Visibility is **`assigned_to`/`created_by`-based**, not mailbox-based: `EmailController.php:31-41,195-197,293-294,313-314`, `ChatController.php:30-32`, `attachment()` L432-440. **Admins & sales managers see every email/chat in the workspace.**
- `UserManagementController`: `edit/update/destroy` load `CrmUser::findOrFail($id)` with **no workspace-membership check** (L141,163,269) → cross-workspace IDOR on users *and their mail credentials*; `destroy` uses legacy `role` (L275) while `edit/update` use the pivot.
- `testConnection()` L232-260: user-controlled `imap_host/port` (SSRF/port-probe), hardcoded `/imap/ssl/novalidate-cert`, returns raw `imap_last_error()` to the client; credentials posted before the user record exists (`users/create.blade.php:766-782`).

### 2.6 Credential UI
- `users/create.blade.php`: `email_user` L570, `email_pass` L584; hidden Hostinger host/port L624-627; **mailbox email/password mirrored into the CRM login `email`/`password` fields** (L435-436, L572, `syncPassword` L702-704) — login = mailbox by construction.
- `users/edit.blade.php:316` renders stored plaintext `email_pass` as an input value.

---

## 3. Email data model

### 3.1 `crm_emails` (lead/inquiry table; legacy base + 25 ALTER migrations)
- Model `app/CrmEmail.php`: `SoftDeletes` (L10), workspace global scope (L98-109), JSON casts (L116-124), relations `messages()` L131, `latestMessage()` L192, `inquiryAttachments()` L204, `statusLogs`, `salesOrder`, `estimateTickets`, `rejectionLog`, `workspace`. N+1 hazard: `customer_type` accessor (L224-236) runs `exists()` per row.
- Base (legacy) columns from fillable: `product_name, client_name, client_email, client_phone, length, width, height, unit, stock, color, coating, quantity, file_url, message, subject, ip_address, country, is_spam, spam_reason, status, linkedin/twitter/facebook/instagram_url, social_investigated_at, assigned_to, assigned_by, assigned_at, portal_password, timestamps, deleted_at`.
- Key migrated columns: `imap_message_id string null **UNIQUE**` + `source default 'form'` (`2026_05_08_120135:17-18`); `is_rejected` bool indexed (`2026_07_14_000001`); `workspace_id` FK + `external_lead_id` + UNIQUE(`workspace_id`,`external_lead_id`) (`2026_07_16_000001:66-70`); `created_by` indexed (`2026_07_23_000008:14`); `follow_up_count` (`2026_09_14_000003`); many estimate/order/invoice columns (see migrations list in `database/migrations/*crm_emails*`).
- Indexes: `crm_emails_workspace_inbox_index(workspace_id,is_spam,is_rejected,status,assigned_to)`, `..._estimate_index`, `..._created_index` (`2026_07_29_000001:10-12`); `crm_emails_status_marked_idx` (`2026_08_26_000001:37`). **No FULLTEXT.**
- **Existence checklist vs spec:** `email_account_id` ✗ · `message_id` ✗ (only `imap_message_id`, **never written by any code** → always NULL) · `in_reply_to` ✗ · `references` ✗ · `thread_id` ✗ · `folder` ✗ · `is_read` ✗ (only on `crm_messages`) · `is_starred` ✗ · `received_at` ✗ · `sent_at` ✗ · `assigned_to` ✓ · `created_by` ✓ · `is_spam`/`is_rejected`/`deleted_at` ✓.

### 3.2 `crm_messages` (thread messages; legacy, no create migration)
- Model `app/CrmMessage.php:13-21`: `crm_email_id, sender_type ('client'|'admin'|'agent'), crm_user_id, message_body (plain text, HTML stripped), message_id (UNIQUE, `2026_07_20_000003:14-15`), attachments (JSON array of relative public paths), is_read`.
- **Missing for a mail client:** subject, from/to/cc/bcc, date/received_at, in_reply_to/references, UID/UIDVALIDITY, folder, HTML body, headers, account id. Read state is **thread-global** (set for everyone on `show` L401-404 and every `getMessages` poll L1023-1026).

### 3.3 Attachments
- Outgoing: saved to **web root** `crm_attachments/{inquiry_id}/{time}_{name}` via `webDocumentPath()` (`EmailController.php:882-895, 2042-2047`); authorized route `crm.attachments.show` exists (`routes/web.php:46-49`, `attachment()` L428-453) **but `show.blade.php:2197-2199` links with `asset()`**, bypassing it.
- Inbound (IMAP): **attachments are not parsed or saved at all.**
- `inquiry_attachments` (`2026_07_23_000004:11-23`, model `app/InquiryAttachment.php`, workspace-scoped) is for design tickets, not mail.

### 3.4 Live data (local Docker DB, read-only)
| Metric | Value |
|---|---|
| `crm_emails` rows | 722 |
| `crm_messages` rows | 71 |
| `crm_users` / with mailbox creds | 38 / 28 |
| `imap_message_id` NULL / `''` / duplicate groups | 676 / 0 / 0 |
| Emails with `assigned_to` / distinct assignees / assignees lacking creds | 140 / 11 / 0 |
| Emails unassigned | 582 |
| By origin | `MyBox Website` 400, `form` 205, **`imap` 46**, `whatsapp` 13, `website` 13, `manual_offline_order` 14, `LabelPouches` 11, `crm_inquiry` 5, `Contact Page` 4, `call` 3, `walk_in` 2, `manual` 1 |

Implications: a NULL-safe unique index on message-id is safe; the 140 assigned emails can be linked to their assignee's single legacy mailbox; the 582 unassigned (mostly website leads) have **no mailbox provenance** and should be left unlinked (they are CRM leads, not mail).

---

## 4. Existing email workflow

### 4.1 Receive
```
 Website form ─► POST /api/crm/leads (CrmLeadController) ─► crm_emails (status New, source, workspace_id)
                                                                     ▲
 Client replies by email ─► agent's IMAP INBOX                        │ match In-Reply-To/References against
        │                                                             │ crm_emails.imap_message_id (always NULL → dead)
        ▼                                                             │ or crm_messages.message_id (sender admin/agent)
 crm:fetch-emails / crm:imap-daemon  ── per CrmUser with creds ──────┘
   • INBOX only, SINCE 2 days, UID > Cache['crm_imap_last_uid_'+sha1(email)], cap 100   (FetchImapEmails.php:94-104)
   • cursor advanced in finally → failed message never retried                           (:322-323)
   • system/bounce filters (:153-237); dedupe by Message-ID (:242-255)
   • MATCH → CrmMessage(sender_type=client, plain text, no crm_user_id/is_read) + status 'Client Replied' (:292-306)
   • NO MATCH → skipped, not imported                                                     (:307-312)
   • daemon forces \Seen on the real mailbox even for skipped mail                        (ImapDaemon.php:127-189)
 Triggers: untracked cron → crm:imap-daemon (4 polls × 5s, flock per user :59-64)
           browser GET /crm/chat-sync → Artisan::call('crm:fetch-emails --user --mark-read') inside the HTTP request (ChatController.php:51-75)
```

### 4.2 Send (reply / follow-up)
```
 show.blade.php / chats UI ─► POST /crm/email/{id}/message (crm.messages.send) ─► EmailController::sendMessage (:865-1012)
   1. validate body/attachments(10MB whitelist)/cc/bcc/subject (:867-874)
   2. save attachments under web root (:882-895)
   3. CrmMessage(sender_type=admin, is_read=true) BEFORE sending (:898-905)
   4. app()->terminating(): Mail::to(client)->send(new ClientMessage)   ← GLOBAL mailer, From = MAIL_FROM_ADDRESS (ClientMessage.php:73-76)
        Message-ID <crm-{uniqid}@host> (:49-58); In-Reply-To/References = own Message-ID (self-referential, :63-65,79-86); no Reply-To
   5. store generated Message-ID on crm_messages (:948-950); appendToImapSent() copies a rebuilt MIME (without In-Reply-To/References,
      buildSentMimeCopy :2129-2164) into the AGENT's IMAP Sent folder, falling back to the shared env mailbox (:2068-2072)
   6. status → 'Responded' (:975-992)
 forward() (:853-864) references \App\Mail\ForwardedInquiry — class does not exist → Forward is broken.
```

### 4.3 Search / status
- Inbox search: 12-column `LIKE %term%` on `crm_emails` (`EmailController.php:216-232`); message bodies not searched; no FULLTEXT.
- `crm_emails.status` values: `New, Viewed, Responded, Client Replied, Qualified Lead, Order Done, Closed, Rejected` (protected set at `EmailController.php:979`, `FetchImapEmails.php:300`); plus `production_status`, `estimate_status`, `payment_status`, `is_spam`, `is_rejected`, `shipping_stage`.

---

## 5. Routes relevant to the new module (`routes/web.php`)
- Public-ish: `:46-49` `GET crm_attachments/{inquiry}/{filename}` (auth:crm, crm.ip).
- CRM group (`:59` auth:crm+crm.ip, `:63` crm.workspace): inbox `:154`, inquiries `:155-161,168`, spam `:169`, rejected `:170`, assign/bulk `:171-173,182`, `email/{id}` show `:174`, **`:180` POST message send**, **`:181` GET messages fetch**, chats `:193-195` (`chats`, `chat-list`, `chat-sync`), status/estimate ops `:198-207`; `:210` `crm.admin_manager` → users resource + `users/test-connection` `:211`; `:400-401` change password.
- Workspace-slug-gated groups exist at `:90` and `:123` (pattern for module gating).

---

## 6. Frontend

### 6.1 Stack
- **No build pipeline in use**: Vite scaffolding exists (`package.json`, `vite.config.js`) but `resources/css/app.css` is empty and the layout has no `@vite` call. All CRM views are plain Blade with inline `<style>`/`<script>`; CSS is fully custom (no Bootstrap/Tailwind).
- Layout assets (`resources/views/crm/layout.blade.php`): Google Fonts DM Sans/Inter (L12-14), **Font Awesome 5.15.3** (L16, use `fas fa-*`), daterangepicker CSS (L27); jQuery/moment/daterangepicker loaded **unpinned "latest"** at L1950-1952 (before `#crm-page-scripts` L1971).
- Rich text: **TinyMCE 6.8.4 self-hosted** at `public/tinymce/`, used only by `crm/emails/show.blade.php` with a lazy loader (`ensureTinymce`, L3778). CKEditor 4.15.1 remains at `public/ckeditor/` (referenced only by `crm/auth/change_password.blade.php` and old admin layouts) — do not reintroduce.

### 6.2 Layout & design system
- Sections: `title`, `styles` (raw CSS injected into the layout's first `<style>` at L781 and swapped on nav), `header_actions`, `content`, `scripts` (into `#crm-page-scripts` L1971-1973).
- Tokens (`:root` L35-51, workspace-switched): `--primary-purple` (#6c5ce7 / Al Massa #f45a24), `--primary-hover`, `--primary-soft`, `--primary-rgb`, `--primary-shadow`, `--text-dark`, `--text-gray`, `--card-bg`, `--sidebar-bg`, `--border-radius-base:16px`, `--success-green`, `--danger-red`, `--sidebar-width:205px`. `body` is `100dvh; overflow:hidden; display:flex`; `.main-area` is `flex:1; overflow-y:auto; padding:1rem 1.25rem 2rem`.
- Sidebar item pattern L960-1492 (`.nav-item[.active]`, `.nav-count`, `.nav-right .arrow`), role gating via `@if($__navUser->isX())`, cached counts via `$__sidebarCount()` L914-920, active state via `routeIs()` + `setActiveNav()` L1826-1842 (longest-prefix pathname match).
- Globals: `showToast(msg,'success'|'error')` L1733, `customConfirm(title,text,cb,btnText,btnClass)` L1757, `crmEsc()` L1552, `loadScriptOnce/loadCssOnce` L1641/1654, `toggleSidebar()` L1660, online ping every 10s L1784, global image auto-compress on file inputs L2025-2060 (opt-out `data-nocompress`).

### 6.3 AJAX partial navigation — rules for every new page script (`layout.blade.php:1806-1946`)
Any `<a href="/crm/...">` click is intercepted (`isPartialNavLink` L1813-1824; opt-out `data-no-ajax-nav`), the page is fetched with `X-Requested-With`, `.main-area` innerHTML is replaced, the first head `<style>` is swapped, and **only inline `script:not([src])`** from `.main-area` + `#crm-page-scripts` are re-appended to `<body>` (`runPageScripts` L1852-1874), then `crm:page-loaded` fires. Consequences (all verified by past incidents in this repo):
1. `<script src>`/`<link>` in a page are ignored → lazy-load with `loadScriptOnce`/`loadCssOnce` or an `ensureX()` loader.
2. `DOMContentLoaded` never re-fires → init immediately.
3. Top-level `let`/`const` throw "already declared" on the 2nd visit and abort the script → use `var`/`function` or an IIFE with explicit `window.*` exports for inline handlers.
4. Intervals/listeners stack → keep ids/flags on `window.__xxx`, clear before re-creating, self-stop when the page root is gone, bind document/window listeners once.
5. Links to JSON endpoints must not be `<a href>`; downloads need `data-no-ajax-nav`.
6. The layout's listing auto-refresh (L1687-1731) targets paths containing `inbox|spam|leads|logs|show-orders|team-performance` and swaps `.content-card` — choose a route prefix like `/crm/mail` and avoid `.content-card` in the mail UI.
7. `/crm/email/{id}` already exists; a new prefix must not collide with `setActiveNav` prefix matching.

### 6.4 Reusable pieces for the 3-pane client
- **Live Chat shell** `resources/views/crm/chats/index.blade.php`: `.main-area{padding:0;overflow:hidden}` + hidden `.top-bar` override (L6-10); `.chat-container#app > .chat-list-sidebar(380px) + .chat-main` (L298-391); `.chat-item/.chat-avatar/.chat-info/.chat-meta/.chat-badge` rows (L556-569); `.chat-empty` empty state; `.search-chat`; `#emailMetaModal` subject/cc/bcc modal (L396-425, identical copy in show L2940-2960); mobile `.chat-active` single-pane toggle + `#mobileBackBtn` (L265-294, L830-839); AJAX-safe polling idioms (L474-478, L845-857).
- **Composer & attachments** (`crm/emails/show.blade.php`): `#chat-form` L2248-2298, TinyMCE config + `window.getReplyEditor`/`ensureTinymce`/`bootReplyEditor` L3761-3871, window-scoped attachment helpers `addReplyAttachments/removeReplyAttachment/handleFileSelect/setupReplyDropZone/setupReplyDropOverlay` L3194-3328 with `.attachment-chip*`/`.reply-drop-overlay` CSS L425-466.
- **Inbox list idioms** (`crm/emails/index.blade.php`): filter card + debounced live search with `AbortController` (L878-957), bulk action bar (L648-678), status pill colours (L579-601), pagination styling from layout L529-577.
- Modals: prefer the class-based `.ug-backdrop/.ug-dialog` pattern (`crm/partials/unsaved_guard.blade.php`) over inline-styled overlays; use `customConfirm` for destructive actions.
- Navigation slot: between **Chats (L1002-1012)** and **Estimate (L1016)**, with its own `routeIs('crm.mail.*')` and a `$__sidebarCount('mail_unread', …)` entry near L951.

---

## 7. Security findings & missing functionality

| # | Finding | Severity | Where |
|---|---|---|---|
| S1 | Mailbox passwords stored **plaintext**, not `$hidden`, rendered into HTML | Critical | `CrmUser.php:21-23`, `users/edit.blade.php:316`, `AuthController.php:40`, `UserManagementController.php:113,199` |
| S2 | **Login password = mailbox password**; sales roles authenticate only via IMAP; change-password ineffective for them | Critical (blocks multi-account) | `AuthController.php:39-41,100-105,145-178`, `users/create.blade.php:435-436,702-704` |
| S3 | **No Policies/Gates**; cross-workspace IDOR on user records + credentials | High | `AuthServiceProvider.php:15-25`, `UserManagementController.php:141,163,269` |
| S4 | `testConnection` accepts arbitrary host/port (SSRF/port-probe), returns raw IMAP errors, no ownership binding | High | `UserManagementController.php:232-260` |
| S5 | Outgoing attachments under web root + `asset()` links bypass the authorized route | High | `EmailController.php:2042-2047`, `show.blade.php:2197-2199` |
| S6 | No HTML sanitizer; message HTML is `strip_tags`'d on ingest today, but an Outlook-style client will render HTML bodies → stored XSS risk | High (for new UI) | composer (none) |
| S7 | Admins/sales managers see all mail in the workspace; sharing rule is `assigned_to`, not mailbox ownership | Medium (privacy requirement) | `EmailController.php:195,293,313`, `ChatController.php:30-32` |
| S8 | CLI never sets `CrmWorkspaceContext` → thread matching spans workspaces | Medium | `FetchImapEmails.php`, `ImapDaemon.php` |
| S9 | Shared-credential fallback: Sent copies may land in the shared env mailbox | Medium | `EmailController.php:2068-2072` |
| S10 | `email_user` + IMAP server error text logged; console prints `host:port as user` and body snippets | Low | `FetchImapEmails.php:75,87,297,320,329`, `ImapDaemon.php:185,191` |
| S11 | No throttling on auth/mail endpoints; `/novalidate-cert` everywhere | Low | `Kernel.php:70`, `AuthController.php:107`, `UserManagementController.php:245` |
| S12 | Unpinned "latest" CDN jQuery/moment | Low | `layout.blade.php:1950-1952` |

**Functional gaps vs spec:** no account entity; no per-mailbox SMTP; non-reply inbound discarded; no raw message/HTML/headers/attachments storage; `imap_message_id` never written (threading inert, outgoing In-Reply-To self-referential, Sent copy lacks thread headers); INBOX-only, cache-based cursor advanced on failure, no UIDVALIDITY, no folders, `\Seen` forced by daemon; thread-global read state; no stars/archive/trash/drafts; `LIKE`-only search without bodies; no scheduler/queue/worker service; Forward route broken (`ForwardedInquiry` missing); `FetchImapEmails`/`ImapDaemon` are duplicate code paths.

---

## 8. Proposed architecture (grounded in the audit)

**Principle:** additive. `crm_emails` stays the lead/inquiry table and all existing CRM flows keep working; the mail client lives in new `crm_mail_*` tables and links to leads.

### 8.1 Tables (Phase 1)
- **`crm_mail_accounts`** — `id, workspace_id (nullable, index), crm_user_id (FK crm_users cascade), email_address, display_name, provider (gmail|outlook|hostinger|custom), auth_type ('password' now; 'oauth2' reserved), imap_host, imap_port, imap_encryption, smtp_host, smtp_port, smtp_encryption, email_user, email_pass (encrypted cast), oauth_access_token/oauth_refresh_token/oauth_expires_at (nullable, encrypted; reserved), signature (text), is_active, is_default, sync_enabled, last_synced_at, last_sync_error (text), timestamps`. Indexes: `(crm_user_id, is_active)`, UNIQUE `(crm_user_id, email_address)`. Single default per user enforced in a transaction (+ optional generated-column unique).
- **`crm_mail_folders`** — `id, account_id FK, name, path (IMAP path), type (inbox|sent|drafts|archive|junk|trash|starred|custom), uidvalidity, last_uid, message_count, unread_count, timestamps`; UNIQUE `(account_id, path)`.
- **`crm_mail_messages`** — `id, account_id FK, folder_id FK, crm_email_id (nullable FK → crm_emails, set null), thread_id (nullable FK), uid, message_id (string, index), in_reply_to, references_header (text), subject, from_name, from_email, to_json, cc_json, bcc_json, reply_to, text_body (longtext), html_body (longtext, sanitized), snippet, has_attachments, is_read, is_starred, is_draft, is_outgoing, received_at, sent_at, headers_json (optional), timestamps`. Indexes: UNIQUE `(account_id, folder_id, uid)`, UNIQUE-nullable `(account_id, message_id)`, `(account_id, folder_id, received_at)`, `(account_id, is_read)`, FULLTEXT `(subject, text_body)` (MySQL 8).
- **`crm_mail_threads`** — `id, account_id FK, subject_norm, root_message_id, last_message_at, message_count, unread_count`.
- **`crm_mail_attachments`** — `id, message_id FK, original_name, mime_type, size, disk ('local'), path (private `storage/app/mail/{account}/{message}/…`), content_id (inline), timestamps`.
- **`crm_mail_drafts`** — or `is_draft` rows in `crm_mail_messages` (recommended: reuse messages table).
- **`crm_mail_sync_runs`** (optional) — `account_id, started_at, finished_at, status, error, imported_count`; concurrency via `Cache::lock('mail:sync:'.$accountId)` (Redis available) instead of file `flock`.
- **`crm_emails`**: add nothing except optional `mail_account_id` (nullable) for "lead first seen via this mailbox". `crm_messages` untouched (legacy chat/lead thread continues to work; later optionally mirrored).

### 8.2 Data migration (Phase 1, reversible)
1. For each of the 28 `crm_users` with non-empty `email_user`+`email_pass`: insert one `crm_mail_accounts` row (provider from host, `is_default=1`, `email_pass` encrypted), copying `imap_*/smtp_*/signature`. Legacy columns are **kept** (compatibility period).
2. Link the 140 assigned `crm_emails` to their assignee's account via `mail_account_id` only if that user has exactly one account; leave the 582 unassigned unlinked (website leads). Emit a report of unmapped rows.
3. No destructive step; `down()` drops the new tables / column only.

### 8.3 Services (Phases 2-4) — new files
- `app/Models/CrmMailAccount.php` (encrypted casts, `$hidden` secrets, workspace scope, `user()`), `CrmMailFolder`, `CrmMailMessage`, `CrmMailThread`, `CrmMailAttachment`.
- `app/Policies/CrmMailAccountPolicy.php`, `CrmMailMessagePolicy.php` + registration in `AuthServiceProvider` (**first policies in the codebase**).
- `app/Services/Mail/ProviderPresets.php` (gmail/outlook/hostinger/custom), `ImapClient.php` (thin wrapper over `ext-imap`: connect with `/ssl` or `/tls`, no `novalidate-cert` by default, folder discovery, UID search, header/structure parsing incl. nested multipart & charset, attachment extraction), `ImapSyncService.php` (per-account, per-folder UIDVALIDITY+UID cursor, lock, retry/backoff, dedupe by `(account,folder,uid)` then `message_id`, link to lead via header match + `client_email`), `Threader.php` (Message-ID/In-Reply-To/References; no subject-only grouping), `MailTransportFactory.php` (`Transport::fromDsn('smtp://user:pass@host:port?encryption=…')` → `Mail::mailer()` per account; From = account address; Reply-To = account), `OutgoingMailService.php` (idempotency key per send attempt, record message before send, reconcile ambiguous SMTP results, append Sent copy **with** thread headers), `HtmlSanitizer` (add `symfony/html-sanitizer` or `mews/purifier`).
- `app/Console/Commands/MailSyncCommand.php` (`crm:mail-sync {--account=}`) + `app/Jobs/SyncMailAccountJob.php`; schedule `everyMinute()->withoutOverlapping()` in `Kernel.php`; add a `worker` service to `docker-compose.yml` (`php artisan queue:work redis` + `schedule:work`) — currently absent.
- Controllers: `app/Http/Controllers/Crm/Mail/AccountController.php` (CRUD, test-connection bound to account owner, no arbitrary hosts beyond presets unless admin), `MailboxController.php` (folders, list, search, message, actions), `ComposeController.php` (send/reply/reply-all/forward/draft), `AttachmentController.php` (authorized download/preview from private disk).
- Routes: `routes/web.php` new group under prefix `/crm/mail` (names `crm.mail.*`), inside the `crm.workspace` group, with `throttle` on send/test-connection.
- Auth decoupling (prerequisite): `AuthController::login` stop writing `email_pass` (L39-41) and stop requiring IMAP for sales roles (L100-105) once each sales user has a bcrypt password (one-time backfill from the mailbox verification on next login or admin reset); `UserManagementController` stop mirroring mailbox creds into login; `users/edit.blade.php:316` stop rendering the password.

### 8.4 Frontend (Phase 5) — new files
- `resources/views/crm/mail/index.blade.php` (3-pane shell cloned from chats: left `accounts+folders`, middle list, right reading pane; `.main-area` override; mobile 3-step state class), `resources/views/crm/mail/partials/{sidebar,list,reader,compose,account_modal}.blade.php`, JSON endpoints for list/thread/actions.
- Reuse verbatim: TinyMCE loader/config + attachment helpers from `show.blade.php`, `#emailMetaModal` flow, `showToast`, `customConfirm`, `.chat-*` classes, design tokens. Follow §6.3 rules (IIFE/`var`, immediate init, `window.__mailPoll`, delegation on `#mail-app`, `data-no-ajax-nav` on downloads).
- Sidebar entry in `layout.blade.php` after Chats with `mail_unread` count.

### 8.5 File-by-file touch list (existing files)
| File | Change | Phase |
|---|---|---|
| `database/migrations/2026_10_xx_*` (new) | create `crm_mail_*` tables; add `crm_emails.mail_account_id`; data migration command | 1 |
| `app/CrmUser.php` | add `mailAccounts()` relation; add `email_pass` to `$hidden` | 1 |
| `app/Providers/AuthServiceProvider.php` | register mail policies | 2 |
| `app/Http/Controllers/Crm/AuthController.php` | decouple login from mailbox (L39-41, L90-125) | 2 |
| `app/Http/Controllers/Crm/UserManagementController.php` + `users/{create,edit}.blade.php` | remove credential mirroring/rendering; "Email accounts" link to new module; workspace check on target user | 2 |
| `app/Console/Kernel.php` | schedule `crm:mail-sync` | 3 |
| `docker-compose.yml` | add `worker` service (queue + schedule) | 3 |
| `app/Console/Commands/{FetchImapEmails,ImapDaemon}.php` | mark deprecated; route lead-reply linking through `ImapSyncService` | 3 |
| `app/Http/Controllers/Crm/EmailController.php` | `sendMessage`/`appendToImapSent` → `OutgoingMailService` with the lead's linked account (fallback: user's default account; **never** silently another mailbox); fix `forward()`; serve attachments via route | 4 |
| `app/Mail/ClientMessage.php` | accept account (From/Reply-To/signature), real In-Reply-To/References | 4 |
| `resources/views/crm/emails/show.blade.php:2197` | use `route('crm.attachments.show')` instead of `asset()` | 4 |
| `resources/views/crm/layout.blade.php` | sidebar item + unread count; pin CDN versions | 5 |
| `routes/web.php` | `/crm/mail` group; throttle | 2-5 |
| `composer.json` | add `symfony/html-sanitizer` (or `mews/purifier`); declare `ext-imap` | 2 |
| `tests/Feature/Mail*` (new) | account CRUD/ownership, sync idempotency (mocked IMAP), threading, send routing, attachment authz | 8 |

---

## 9. Migration risks & rollback strategy

| Risk | Mitigation |
|---|---|
| Migrations table out of sync → `artisan migrate` fails on legacy files | Run each new migration with `docker compose exec -T app php artisan migrate --force --path=database/migrations/<file>.php`; guard with `Schema::hasTable/hasColumn` (repo convention) |
| No base migrations for `crm_users/crm_emails/crm_messages` → unknown exact types | Inspect with `SHOW CREATE TABLE` before FK creation; use `unsignedBigInteger` to match `make_id_auto_increment` (`2026_06_09_105849:16`) |
| Local host PHP 7.4 | Run artisan/phpunit only inside the `app` container |
| Tests hit the real DB | Use `DB::beginTransaction()/rollBack()` pattern; consider a dedicated `crm_l10_test` DB in `phpunit.xml` before Phase 8 |
| Encrypting credentials depends on `APP_KEY` | Back up `APP_KEY` before migration; losing it = unrecoverable mailbox passwords |
| Login decoupling could lock out sales users | Two-step: (a) on next successful IMAP login also set bcrypt (already happens at L39) and stop writing `email_pass`; (b) flip `verifyImap` to local hash only after all active sales users have logged in once (query `password` not null) or admin resets |
| Per-account SMTP From must equal the authenticated user on Hostinger (`ClientMessage.php` comment) | Per-account transport inherently satisfies this; keep global mailer only for system notifications |
| Sent-folder duplication once Sent is synced | Dedupe by Message-ID on import; mark locally sent rows `is_outgoing` with the same Message-ID |
| Daemon vs new sync running concurrently | Phase 3 cut-over: disable cron for `crm:imap-daemon` the same deploy the scheduler entry goes live; both use lead-linking so no data loss |
| Large HTML bodies / attachments growth | `longtext` + private disk; retention policy TBD |
| Unmapped legacy emails (582) | Left unlinked by design; report emitted by the data-migration command |

**Rollback:** all schema changes are additive (new tables + one nullable column) → `migrate:rollback --path` drops them; legacy `crm_users.email_*` columns and the old fetch commands remain functional during the compatibility period; feature-flag the sidebar entry/routes (`config('crm.mail_client_enabled')`) so the UI can be hidden instantly; keep newly received mail by never dropping `crm_mail_*` tables in an emergency rollback (disable sync + hide UI instead).

---

## 10. Open decisions for the product owner
1. **OAuth2 (Gmail/M365) — now, later, or never?** Spec says "where required"; prior answer was password/app-password only. Recommendation: Phase 2 password/app-password with presets; schema reserves `auth_type` + token columns; OAuth as a later phase.
2. **Login decoupling timing:** do it in Phase 2 (required for N accounts/user) — confirm acceptable one-time coordination with sales users.
3. **Should the legacy `Chats` page remain** as the lead-thread view, with the new `/crm/mail` client as a separate module (recommended), or be replaced?
4. **Admin visibility:** spec requires private mailboxes; today admins see everything. Confirm admins get *no* access to other users' mailboxes (recommended) vs an explicit "shared with admin" toggle per account.
5. **Attachment retention & size caps** for inbound mail (private disk vs `s3`).
6. **Queue worker hosting:** add a `worker` container (recommended) vs keep cron-driven commands.

---

## Appendix A — Audit method
Three parallel read-only sweeps (users/auth/credentials; backend pipeline; frontend) plus direct inspection of `composer.*`, `docker/*`, `docker-compose.yml`, `phpunit.xml`, `config/*`, `.env` driver keys (no secrets read), and read-only SQL counts against the local Docker MySQL. `head`/`awk` are unavailable in the local shell (use `sed -n`/Read).
