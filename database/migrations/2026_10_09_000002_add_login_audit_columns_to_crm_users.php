<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* Login step B audit trail. Run with: php artisan migrate --force --path=database/migrations/<this file> */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_users', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_users', 'last_login_at')) $table->timestamp('last_login_at')->nullable();
            if (!Schema::hasColumn('crm_users', 'imap_login_fallback_at')) $table->timestamp('imap_login_fallback_at')->nullable();
            if (!Schema::hasColumn('crm_users', 'password_changed_at')) $table->timestamp('password_changed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('crm_users', function (Blueprint $table) {
            foreach (['last_login_at', 'imap_login_fallback_at', 'password_changed_at'] as $c) {
                if (Schema::hasColumn('crm_users', $c)) $table->dropColumn($c);
            }
        });
    }
};
