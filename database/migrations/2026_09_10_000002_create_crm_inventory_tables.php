<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inventory: stock items (paper/material batches) + a movement ledger.
 * Buy 1000 sheets → a "in" movement raises the item balance; using paper against a
 * job → an "out" movement lowers it. The item keeps a running `quantity` for speed,
 * the ledger keeps the full per-job history.
 */
class CreateCrmInventoryTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('crm_inventory_items')) {
            Schema::create('crm_inventory_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->string('name');                       // e.g. "Art Card 300gsm 20x30"
                $table->string('category')->nullable();       // Paper, Ink, Board, Other
                $table->string('paper_size')->nullable();     // 20x30
                $table->string('gsm')->nullable();
                $table->string('stock_type')->nullable();     // Art Card / Kraft / ...
                $table->string('unit', 30)->default('sheets'); // sheets / kg / rolls
                $table->decimal('quantity', 16, 3)->default(0);      // running balance
                $table->decimal('reorder_level', 16, 3)->default(0); // low-stock threshold
                $table->decimal('unit_cost', 14, 4)->nullable();
                $table->string('currency', 8)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('crm_inventory_movements')) {
            Schema::create('crm_inventory_movements', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('workspace_id')->nullable()->index();
                $table->unsignedBigInteger('inventory_item_id')->index();
                $table->enum('type', ['in', 'out', 'adjust']);
                $table->decimal('quantity', 16, 3);           // always positive; type says direction
                $table->decimal('balance_after', 16, 3)->nullable();
                $table->unsignedBigInteger('job_id')->nullable();     // design_jobs.id
                $table->string('job_number')->nullable();
                $table->unsignedBigInteger('vendor_purchase_id')->nullable();
                $table->decimal('unit_cost', 14, 4)->nullable();
                $table->string('reference')->nullable();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->foreign('inventory_item_id')->references('id')->on('crm_inventory_items')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('crm_inventory_movements');
        Schema::dropIfExists('crm_inventory_items');
    }
}
