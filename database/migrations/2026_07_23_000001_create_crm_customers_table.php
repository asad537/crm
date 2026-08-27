<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrmCustomersTable extends Migration
{
    public function up()
    {
        Schema::create('crm_customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('phone', 60)->nullable();
            $table->string('email')->nullable();
            $table->string('country', 100)->nullable();
            $table->text('billing_address')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('tax_number', 100)->nullable();
            $table->string('currency', 10)->default('AED');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('crm_customers');
    }
}
