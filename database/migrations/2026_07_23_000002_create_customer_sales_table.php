<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerSalesTable extends Migration
{
    public function up()
    {
        Schema::create('customer_sales', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('customer_id')->index();
            $table->string('order_number', 100);
            $table->date('order_date');
            $table->date('due_date')->nullable();
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 15, 2)->default(1);
            $table->string('unit', 30)->default('Pieces');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('balance_amount', 15, 2)->default(0);
            $table->string('currency', 10)->default('AED');
            $table->string('payment_status', 30)->default('Unpaid');
            $table->string('order_status', 30)->default('Draft');
            $table->string('payment_method', 50)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'order_date']);
            $table->foreign('customer_id')->references('id')->on('crm_customers')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_sales');
    }
}
