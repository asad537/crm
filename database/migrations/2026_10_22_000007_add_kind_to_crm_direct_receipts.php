<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // income = other income (P&L); on_account = a customer paid without picking an invoice (reduces what they owe).
        if (!Schema::hasColumn('crm_direct_receipts', 'kind')) {
            Schema::table('crm_direct_receipts', function (Blueprint $table) {
                $table->string('kind', 20)->default('income')->after('receipt_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('crm_direct_receipts', 'kind')) {
            Schema::table('crm_direct_receipts', function (Blueprint $table) {
                $table->dropColumn('kind');
            });
        }
    }
};
