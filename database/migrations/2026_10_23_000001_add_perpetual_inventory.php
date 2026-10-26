<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perpetual inventory from the day this runs: stock bought on a purchase invoice is an asset (Inventory), stock used
 * on a job is Cost of Sales, wastage is a loss. Old bills stay as they were (expensed); the stock on hand today is
 * taken onto the books once as opening stock (see InventoryAccounting::start).
 */
class AddPerpetualInventory extends Migration
{
    public function up()
    {
        Schema::table('crm_inventory_movements', function (Blueprint $table) {
            // bill | opening | expensed | job | wastage | damage | expired | count — what the movement means for the books.
            if (!Schema::hasColumn('crm_inventory_movements', 'reason')) $table->string('reason', 20)->nullable()->after('type');
            if (!Schema::hasColumn('crm_inventory_movements', 'purchase_invoice_id')) $table->unsignedBigInteger('purchase_invoice_id')->nullable()->index()->after('vendor_purchase_id');
            // Accounting date (a bill's stock is dated on the bill); null = the day it was entered.
            if (!Schema::hasColumn('crm_inventory_movements', 'entry_date')) $table->date('entry_date')->nullable()->after('purchase_invoice_id');
        });
        Schema::table('crm_purchase_invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_purchase_invoice_items', 'inventory_item_id')) $table->unsignedBigInteger('inventory_item_id')->nullable()->index()->after('fixed_asset_id');
        });

        $workspaces = DB::table('crm_inventory_items')->whereNotNull('workspace_id')->distinct()->pluck('workspace_id');
        foreach ($workspaces as $workspaceId) {
            \App\Services\InventoryAccounting::start((int) $workspaceId);
        }
    }

    public function down()
    {
        Schema::table('crm_purchase_invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('crm_purchase_invoice_items', 'inventory_item_id')) $table->dropColumn('inventory_item_id');
        });
        Schema::table('crm_inventory_movements', function (Blueprint $table) {
            foreach (['reason', 'purchase_invoice_id', 'entry_date'] as $column) {
                if (Schema::hasColumn('crm_inventory_movements', $column)) $table->dropColumn($column);
            }
        });
        DB::table('crm_settings')->where('key', \App\Services\InventoryAccounting::KEY)->delete();
    }
}
