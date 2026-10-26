<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('crm_purchase_invoice_items', 'category')) {
            Schema::table('crm_purchase_invoice_items', function (Blueprint $table) {
                $table->string('category', 80)->nullable()->after('name');   // what was bought: Raw Material, Office Supplies...
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('crm_purchase_invoice_items', 'category')) {
            Schema::table('crm_purchase_invoice_items', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
};
