<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalesOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('crm_email_id');
            $table->unsignedBigInteger('sales_agent_id');
            
            $table->string('payment_term')->default('50_advance'); // credit, 100_deposit, 50_advance
            $table->integer('credit_days')->nullable(); // 15, 30, 45, 60
            
            $table->string('payment_status')->default('pending'); // pending, approved, received
            $table->string('status')->default('pending_payment'); // pending_payment, pending_artwork, in_design, design_approved
            
            $table->string('artwork_file_path')->nullable();
            $table->text('design_notes')->nullable();
            
            $table->timestamps();

            $table->foreign('crm_email_id')->references('id')->on('crm_emails')->onDelete('cascade');
            $table->foreign('sales_agent_id')->references('id')->on('crm_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_orders');
    }
}
