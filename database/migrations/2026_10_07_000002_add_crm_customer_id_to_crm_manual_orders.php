<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('crm_manual_orders', 'crm_customer_id')) {
            Schema::table('crm_manual_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('crm_customer_id')->nullable()->index()->after('crm_email_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('crm_manual_orders', 'crm_customer_id')) {
            Schema::table('crm_manual_orders', function (Blueprint $table) {
                $table->dropColumn('crm_customer_id');
            });
        }
    }
};
