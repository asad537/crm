<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demand_request_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('demand_request_payments', 'category')) {
                $table->string('category', 120)->nullable()->after('item_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('demand_request_payments', function (Blueprint $table) {
            if (Schema::hasColumn('demand_request_payments', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
