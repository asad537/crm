<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('demand_request_payments', 'pay_type')) {
            Schema::table('demand_request_payments', function (Blueprint $table) {
                // "Account" = paid through company account (needs reconciliation) | "Direct" = company paid directly
                $table->string('pay_type', 20)->default('Account')->after('item_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('demand_request_payments', 'pay_type')) {
            Schema::table('demand_request_payments', function (Blueprint $table) {
                $table->dropColumn('pay_type');
            });
        }
    }
};
