<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateVendorPurchaseItemsTable extends Migration
{
    public function up()
    {
        Schema::create('vendor_purchase_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('vendor_purchase_id')->index();
            $table->unsignedInteger('position')->default(1);
            $table->string('category', 100);
            $table->string('item_name');
            $table->string('material')->nullable();
            $table->string('specification')->nullable();
            $table->string('size', 100)->nullable();
            $table->string('gsm', 50)->nullable();
            $table->string('color', 100)->nullable();
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 30);
            $table->decimal('unit_price', 12, 4);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();

            $table->foreign('vendor_purchase_id')
                ->references('id')->on('vendor_purchases')->onDelete('cascade');
        });

        // Preserve all existing single-product purchases as their first line item.
        DB::table('vendor_purchases')->orderBy('id')->chunk(250, function ($purchases) {
            $now = now();
            $rows = [];
            foreach ($purchases as $purchase) {
                $rows[] = [
                    'vendor_purchase_id' => $purchase->id,
                    'position' => 1,
                    'category' => $purchase->category,
                    'item_name' => $purchase->item_name,
                    'material' => $purchase->material,
                    'specification' => $purchase->specification,
                    'size' => $purchase->size,
                    'gsm' => $purchase->gsm,
                    'color' => $purchase->color,
                    'quantity' => $purchase->quantity,
                    'unit' => $purchase->unit,
                    'unit_price' => $purchase->unit_price,
                    'line_total' => $purchase->subtotal,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($rows) DB::table('vendor_purchase_items')->insert($rows);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_purchase_items');
    }
}
