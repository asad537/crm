<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demand_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('demand_requests', 'vat_percentage')) {
                $table->decimal('vat_percentage', 5, 2)->default(0)->after('estimated_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('demand_requests', function (Blueprint $table) {
            if (Schema::hasColumn('demand_requests', 'vat_percentage')) {
                $table->dropColumn('vat_percentage');
            }
        });
    }
};
