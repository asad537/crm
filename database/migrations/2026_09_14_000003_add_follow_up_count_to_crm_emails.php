<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('crm_emails', 'follow_up_count')) {
            Schema::table('crm_emails', function (Blueprint $table) {
                $table->unsignedInteger('follow_up_count')->default(0)->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('crm_emails', 'follow_up_count')) {
            Schema::table('crm_emails', function (Blueprint $table) {
                $table->dropColumn('follow_up_count');
            });
        }
    }
};
