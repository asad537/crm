<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Outlook-style mail client — Phase 1 schema (additive, reversible)
|--------------------------------------------------------------------------
| crm_emails stays the lead/inquiry table. Real mailbox data lives in the
| crm_mail_* tables below and links back to a lead via crm_mail_messages
| .crm_email_id (nullable). Every create is guarded with hasTable()/hasColumn()
| because this project's migrations table is out of sync — run with:
|   php artisan migrate --force --path=database/migrations/<this file>
| All FK targets are BIGINT UNSIGNED (crm_users.id, crm_emails.id,
| crm_workspaces.id) — verified against the live schema. crm_messages.id is a
| plain INT, so it is intentionally NOT referenced.
*/
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('crm_mail_accounts')) {
            Schema::create('crm_mail_accounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->unsignedBigInteger('crm_user_id');
                $table->string('email_address');
                $table->string('display_name')->nullable();
                $table->string('provider', 32)->default('custom');      // gmail|outlook|hostinger|custom
                $table->string('auth_type', 16)->default('password');   // password | oauth2 (reserved)
                $table->string('imap_host')->nullable();
                $table->unsignedSmallInteger('imap_port')->default(993);
                $table->string('imap_encryption', 8)->default('ssl');   // ssl|tls|none
                $table->string('smtp_host')->nullable();
                $table->unsignedSmallInteger('smtp_port')->default(587);
                $table->string('smtp_encryption', 8)->default('tls');
                $table->string('email_user')->nullable();
                $table->text('email_pass')->nullable();                 // encrypted cast on the model
                $table->text('oauth_access_token')->nullable();         // reserved, encrypted
                $table->text('oauth_refresh_token')->nullable();        // reserved, encrypted
                $table->timestamp('oauth_expires_at')->nullable();
                $table->text('signature')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_default')->default(false);
                $table->boolean('sync_enabled')->default(true);
                $table->boolean('share_with_admin')->default(true);     // admins get read-only visibility
                $table->boolean('migrated_from_legacy')->default(false); // created by crm:mail-migrate-legacy-accounts
                $table->timestamp('last_synced_at')->nullable();
                $table->text('last_sync_error')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['crm_user_id', 'email_address'], 'crm_mail_accounts_user_email_unique');
                $table->index(['crm_user_id', 'is_active'], 'crm_mail_accounts_user_active_index');
                $table->foreign('crm_user_id')->references('id')->on('crm_users')->onDelete('cascade');
                $table->foreign('workspace_id')->references('id')->on('crm_workspaces')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('crm_mail_folders')) {
            Schema::create('crm_mail_folders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id');
                $table->string('name');
                $table->string('path');                                  // IMAP path, e.g. INBOX.Sent
                $table->string('type', 16)->default('custom');           // inbox|sent|drafts|archive|junk|trash|custom
                $table->unsignedBigInteger('uidvalidity')->nullable();
                $table->unsignedBigInteger('last_uid')->default(0);
                $table->unsignedInteger('message_count')->default(0);
                $table->unsignedInteger('unread_count')->default(0);
                $table->timestamps();

                $table->unique(['account_id', 'path'], 'crm_mail_folders_account_path_unique');
                $table->foreign('account_id')->references('id')->on('crm_mail_accounts')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('crm_mail_threads')) {
            Schema::create('crm_mail_threads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id');
                $table->string('subject_norm')->nullable()->index();
                $table->string('root_message_id')->nullable();
                $table->timestamp('last_message_at')->nullable()->index();
                $table->unsignedInteger('message_count')->default(0);
                $table->unsignedInteger('unread_count')->default(0);
                $table->timestamps();

                $table->foreign('account_id')->references('id')->on('crm_mail_accounts')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('crm_mail_messages')) {
            Schema::create('crm_mail_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('account_id');
                $table->unsignedBigInteger('folder_id');
                $table->unsignedBigInteger('thread_id')->nullable();
                $table->unsignedBigInteger('crm_email_id')->nullable()->index(); // link to lead (crm_emails)
                $table->unsignedBigInteger('uid')->nullable();
                $table->string('message_id')->nullable();
                $table->string('in_reply_to')->nullable();
                $table->text('references_header')->nullable();
                $table->text('subject')->nullable();
                $table->string('from_name')->nullable();
                $table->string('from_email')->nullable();
                $table->json('to_json')->nullable();
                $table->json('cc_json')->nullable();
                $table->json('bcc_json')->nullable();
                $table->string('reply_to')->nullable();
                $table->longText('text_body')->nullable();
                $table->longText('html_body')->nullable();               // stored sanitized
                $table->string('snippet', 500)->nullable();
                $table->boolean('has_attachments')->default(false);
                $table->boolean('is_read')->default(false);
                $table->boolean('is_starred')->default(false);
                $table->boolean('is_draft')->default(false);
                $table->boolean('is_outgoing')->default(false);
                $table->timestamp('received_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->json('headers_json')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['account_id', 'folder_id', 'uid'], 'crm_mail_messages_account_folder_uid_unique');
                $table->index(['account_id', 'message_id'], 'crm_mail_messages_account_msgid_index');
                $table->index(['account_id', 'folder_id', 'received_at'], 'crm_mail_messages_folder_received_index');
                $table->index(['account_id', 'is_read'], 'crm_mail_messages_account_read_index');
                $table->index('from_email', 'crm_mail_messages_from_email_index');
                $table->foreign('account_id')->references('id')->on('crm_mail_accounts')->onDelete('cascade');
                $table->foreign('folder_id')->references('id')->on('crm_mail_folders')->onDelete('cascade');
                $table->foreign('thread_id')->references('id')->on('crm_mail_threads')->onDelete('set null');
                $table->foreign('crm_email_id')->references('id')->on('crm_emails')->onDelete('set null');
            });

            // FULLTEXT for subject/body search (InnoDB, MySQL 8). Non-fatal if unsupported.
            try {
                DB::statement('ALTER TABLE crm_mail_messages ADD FULLTEXT crm_mail_messages_fulltext (subject, text_body)');
            } catch (\Throwable $e) {
                // leave LIKE-based search as the fallback
            }
        }

        if (!Schema::hasTable('crm_mail_attachments')) {
            Schema::create('crm_mail_attachments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('message_id');
                $table->string('original_name');
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->string('disk', 32)->default('local');          // private: storage/app
                $table->string('path');
                $table->string('content_id')->nullable();               // for inline (cid:) images
                $table->boolean('is_inline')->default(false);
                $table->timestamps();

                $table->foreign('message_id')->references('id')->on('crm_mail_messages')->onDelete('cascade');
            });
        }

        if (Schema::hasTable('crm_emails') && !Schema::hasColumn('crm_emails', 'mail_account_id')) {
            Schema::table('crm_emails', function (Blueprint $table) {
                $table->unsignedBigInteger('mail_account_id')->nullable()->after('assigned_to')->index();
                $table->foreign('mail_account_id')->references('id')->on('crm_mail_accounts')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('crm_emails') && Schema::hasColumn('crm_emails', 'mail_account_id')) {
            Schema::table('crm_emails', function (Blueprint $table) {
                $table->dropForeign(['mail_account_id']);
                $table->dropColumn('mail_account_id');
            });
        }
        Schema::dropIfExists('crm_mail_attachments');
        Schema::dropIfExists('crm_mail_messages');
        Schema::dropIfExists('crm_mail_threads');
        Schema::dropIfExists('crm_mail_folders');
        Schema::dropIfExists('crm_mail_accounts');
    }
};
