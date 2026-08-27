<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVendorPurchasesTable extends Migration
{
    public function up()
    {
        Schema::create('vendor_purchases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('vendor_name');
            $table->string('vendor_phone', 50)->nullable();
            $table->string('vendor_email')->nullable();
            $table->date('purchase_date');
            $table->date('due_date')->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->string('category', 100);
            $table->string('item_name');
            $table->string('material')->nullable();
            $table->string('specification')->nullable();
            $table->string('size', 100)->nullable();
            $table->string('gsm', 50)->nullable();
            $table->string('color', 100)->nullable();
            $table->decimal('quantity', 12, 2);
            $table->string('unit', 30);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_amount', 12, 2)->default(0);
            $table->string('payment_status', 20)->default('Unpaid');
            $table->string('payment_method', 50)->nullable();
            $table->string('currency', 10)->default('USD');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('workspace_id')->references('id')->on('crm_workspaces')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('crm_users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('vendor_purchases');
    }
}
