<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_mail_messages', function (Blueprint $table) {
            // unread count per folder (mail sync + folder list) without touching the big mail rows
            $table->index(['folder_id', 'is_read'], 'crm_mail_messages_folder_read_index');
            // starred / starred-unread counts for the mail sidebar
            $table->index(['account_id', 'is_starred', 'is_read'], 'crm_mail_messages_account_star_index');
        });
    }

    public function down(): void
    {
        Schema::table('crm_mail_messages', function (Blueprint $table) {
            $table->dropIndex('crm_mail_messages_folder_read_index');
            $table->dropIndex('crm_mail_messages_account_star_index');
        });
    }
};
