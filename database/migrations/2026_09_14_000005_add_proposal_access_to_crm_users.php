<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('crm_users', 'proposal_access')) {
            Schema::table('crm_users', function (Blueprint $table) {
                $table->boolean('proposal_access')->default(false)->after('role');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('crm_users', 'proposal_access')) {
            Schema::table('crm_users', function (Blueprint $table) {
                $table->dropColumn('proposal_access');
            });
        }
    }
};
