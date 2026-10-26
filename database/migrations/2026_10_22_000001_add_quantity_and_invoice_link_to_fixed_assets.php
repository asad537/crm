<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_fixed_assets', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_fixed_assets', 'quantity')) {
                $table->decimal('quantity', 12, 3)->default(1)->after('name');
            }
            if (!Schema::hasColumn('crm_fixed_assets', 'unit_price')) {
                $table->decimal('unit_price', 14, 2)->nullable()->after('quantity');
            }
            // Set when the asset is billed on a supplier invoice (Purchase Invoices tab) and paid through it.
            if (!Schema::hasColumn('crm_fixed_assets', 'purchase_invoice_id')) {
                $table->unsignedBigInteger('purchase_invoice_id')->nullable()->index()->after('payment_method');
            }
        });
        DB::table('crm_fixed_assets')->whereNull('unit_price')->update(['unit_price' => DB::raw('amount')]);

        if (!Schema::hasColumn('crm_purchase_invoice_items', 'fixed_asset_id')) {
            Schema::table('crm_purchase_invoice_items', function (Blueprint $table) {
                $table->unsignedBigInteger('fixed_asset_id')->nullable()->index()->after('purchase_invoice_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('crm_purchase_invoice_items', 'fixed_asset_id')) {
            Schema::table('crm_purchase_invoice_items', function (Blueprint $table) {
                $table->dropColumn('fixed_asset_id');
            });
        }
        Schema::table('crm_fixed_assets', function (Blueprint $table) {
            foreach (['purchase_invoice_id', 'unit_price', 'quantity'] as $column) {
                if (Schema::hasColumn('crm_fixed_assets', $column)) $table->dropColumn($column);
            }
        });
    }
};
