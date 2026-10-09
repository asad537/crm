# Mail Client — Server Deployment (step by step)

Ye file **production server** par chalane ke liye hai. Har step tarteeb se. Pehla deploy ~15 minute.
(Detail/architecture ke liye `docs/email-system/deploy.md` dekhein.)

---

## 0. Pehle backup (lazmi)
```bash
# DB backup
mysqldump -u <db_user> -p <db_name> > ~/crm_backup_$(date +%F_%H%M).sql

# APP_KEY backup (mailbox passwords isi se encrypted hain — ye kho gaya to passwords recover nahi honge)
grep '^APP_KEY=' .env > ~/crm_app_key_backup.txt && chmod 600 ~/crm_app_key_backup.txt
```

## 1. Code + dependencies
```bash
cd /path/to/crm_l10
git pull origin main
composer install --no-dev --optimize-autoloader --ignore-platform-req=php
php artisan config:clear
php artisan view:clear
```
> `--ignore-platform-req=php` is liye ke lock file mein `laravel/pint` PHP 8.3 maangta hai, runtime 8.2 hai (sirf dev tool hai).

## 2. `.env` mein ye lines (agar nahi hain to add karein)
```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
CRM_IMAP_LOGIN_FALLBACK=true
APP_URL=https://your-crm-domain.com
```
> `APP_URL` sahi hona chahiye — "Forgot password" ke email links isi se bante hain.
> Redis zaroori hai (locks, cache, queue). Docker setup mein `redis` service pehle se hai.

## 3. Database migrations (sirf ye do files, `--path` ke saath)
```bash
php artisan migrate --force --path=database/migrations/2026_10_09_000001_create_crm_mail_tables.php
php artisan migrate --force --path=database/migrations/2026_10_09_000002_add_login_audit_columns_to_crm_users.php
```
> Plain `php artisan migrate` **na chalayein** — is project ki migrations table purani files ke saath sync nahi hai.

## 4. Purane mailboxes ko naye system mein laana
```bash
php artisan crm:mail-migrate-legacy-accounts --dry-run   # report dekhein
php artisan crm:mail-migrate-legacy-accounts             # apply (encrypted accounts banenge, leads link honge)
```
> Legacy columns (`crm_users.email_*`) ko haath nahi lagta. Dobara chalana safe hai.

## 5. Config cache + permissions
```bash
php artisan config:cache
php artisan route:cache
php artisan view:clear
chown -R www-data:www-data storage bootstrap/cache   # ya jo bhi web user ho
```
> Mail attachments `storage/app/mail/` aur lead attachments `storage/app/crm_attachments/` mein private store hote hain.

## 6. Nginx (lead attachments ke liye fallback)
`docker/nginx.conf` mein ye block pehle se hai; agar server par apni nginx config hai to is block ko `location ~* \.(jpg|...)` wale block se **pehle** add karein:
```nginx
location ^~ /crm_attachments/ {
    try_files $uri /index.php?$query_string;
}
```
```bash
nginx -t && nginx -s reload
```

## 7. Background worker start karein (receive isi se hota hai)

### Option A — Docker (recommended, project ka compose use karein)
```bash
docker compose up -d --build worker
docker compose logs -f worker      # "Watching mailboxes every 3s [shard i/7]" dikhna chahiye
```

### Option B — Supervisor (bina Docker)
`/etc/supervisor/conf.d/crm-mail.conf`:
```ini
[program:crm-mail-watch]
command=/usr/bin/php /path/to/crm_l10/artisan crm:mail-sync --watch --interval=3 --shard=%(process_num)s/7
process_name=%(program_name)s_%(process_num)02d
numprocs=7
directory=/path/to/crm_l10
autostart=true
autorestart=true
user=www-data
stopasgroup=true
killasgroup=true
stdout_logfile=/var/log/crm-mail-watch.log
stderr_logfile=/var/log/crm-mail-watch.err.log

[program:crm-queue]
command=/usr/bin/php /path/to/crm_l10/artisan queue:work redis --sleep=1 --tries=2 --timeout=300
process_name=%(program_name)s_%(process_num)02d
numprocs=2
directory=/path/to/crm_l10
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/log/crm-queue.log
```
```bash
supervisorctl reread && supervisorctl update && supervisorctl status
```

### Purana daemon band karein
Jahan bhi `crm:imap-daemon` / `crm:fetch-emails` cron/supervisor mein chal raha ho use **disable** kar dein (naya sync lead replies bhi khud link karta hai; dono chalne se IMAP traffic double hota hai).

## 8. Check karein ke sab chal raha hai
```bash
php artisan crm:mail-sync --account=<id> --since=7 --cap=50 --types=inbox   # ek account ka manual sync, "ok" aana chahiye
php artisan crm:auth-audit                                                   # login audit report
tail -f storage/logs/laravel.log | grep -E "Mail sync|IMAP open failed"      # errors yahan aate hain
```
UI: **Mail** → mailbox list → Inbox bhar jaye → ek message khol kar **Reply** → Sent mein copy aaye → Gmail se jawab dein → ~5s mein wapas Inbox + lead conversation mein.

## 9. Users ko kya batana hai
- Jin users ka mailbox password purana/ghalat tha (sync mein "login rejected" dikhega): **Mail → mailbox → ✎ Edit → naya password → Save**.
- Login ab local password se hai; **"Forgot password?"** login page par hai.
- Admin/Sales Manager doosron ke mailboxes **read-only** dekh sakte hain; reply sirf mailbox ka owner karta hai.

## 10. Kuch hafte baad (login step B complete)
```bash
php artisan crm:auth-audit --days=30
```
Jab ye "✔ Safe to complete step B" kahe → `.env` mein `CRM_IMAP_LOGIN_FALLBACK=false` → `php artisan config:cache`.

## Rollback (agar zaroorat pade)
```bash
php artisan crm:mail-migrate-legacy-accounts --rollback   # sirf tab tak safe jab tak mail sync na hui ho
php artisan migrate:rollback --path=database/migrations/2026_10_09_000002_add_login_audit_columns_to_crm_users.php
php artisan migrate:rollback --path=database/migrations/2026_10_09_000001_create_crm_mail_tables.php
# worker band: docker compose stop worker  /  supervisorctl stop crm-mail-watch:* crm-queue:*
```
Code rollback: `git checkout <purana commit>` + `composer install` + `php artisan config:cache`. Purane lead flows isi dauran bhi chalte rehte hain.
