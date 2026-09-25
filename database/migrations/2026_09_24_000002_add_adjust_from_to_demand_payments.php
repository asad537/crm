<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demand_request_payments', function (Blueprint $table) {
            // When set, this payment settled an item using another category's cash
            // (a cross-category adjustment) — holds the SOURCE category name.
            if (!Schema::hasColumn('demand_request_payments', 'adjust_from')) {
                $table->string('adjust_from', 120)->nullable()->after('category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('demand_request_payments', function (Blueprint $table) {
            if (Schema::hasColumn('demand_request_payments', 'adjust_from')) {
                $table->dropColumn('adjust_from');
            }
        });
    }
};
