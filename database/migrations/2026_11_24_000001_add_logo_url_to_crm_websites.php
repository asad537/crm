<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_websites', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_websites', 'logo_url')) {
                $table->string('logo_url', 500)->nullable()->after('color');
            }
        });
    }

    public function down(): void
    {
        Schema::table('crm_websites', function (Blueprint $table) {
            if (Schema::hasColumn('crm_websites', 'logo_url')) {
                $table->dropColumn('logo_url');
            }
        });
    }
};
