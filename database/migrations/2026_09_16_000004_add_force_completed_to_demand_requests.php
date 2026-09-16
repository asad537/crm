<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('demand_requests', 'force_completed')) {
            Schema::table('demand_requests', function (Blueprint $table) {
                $table->boolean('force_completed')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('demand_requests', 'force_completed')) {
            Schema::table('demand_requests', function (Blueprint $table) {
                $table->dropColumn('force_completed');
            });
        }
    }
};
