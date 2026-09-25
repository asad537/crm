<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demand_request_items', function (Blueprint $table) {
            // Planned payer for this line: Company (pays directly) or Account (accountant pays).
            if (!Schema::hasColumn('demand_request_items', 'pay_by')) {
                $table->string('pay_by', 20)->default('Company')->after('estimated_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('demand_request_items', function (Blueprint $table) {
            if (Schema::hasColumn('demand_request_items', 'pay_by')) {
                $table->dropColumn('pay_by');
            }
        });
    }
};
