<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demand_request_items', function (Blueprint $table) {
            if (!Schema::hasColumn('demand_request_items', 'gsm')) {
                $table->string('gsm', 60)->nullable()->after('specification');
            }
            if (!Schema::hasColumn('demand_request_items', 'vendor_name')) {
                $table->string('vendor_name', 150)->nullable()->after('gsm');
            }
        });
    }

    public function down(): void
    {
        Schema::table('demand_request_items', function (Blueprint $table) {
            foreach (['gsm', 'vendor_name'] as $c) {
                if (Schema::hasColumn('demand_request_items', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
