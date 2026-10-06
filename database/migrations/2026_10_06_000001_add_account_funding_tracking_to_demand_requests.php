<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demand_requests', function (Blueprint $table) {
            // Existing approved demands retain their historical budget-based balance.
            $table->boolean('account_funding_tracked')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('demand_requests', function (Blueprint $table) {
            $table->dropColumn('account_funding_tracked');
        });
    }
};
