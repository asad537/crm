<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Multiple products per inquiry. Each row is one product line with its own
 * specs + quantity options; the parent crm_emails row keeps the FIRST product's
 * fields for backward compatibility with all existing single-product code.
 */
class CreateCrmInquiryProductsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('crm_inquiry_products')) return;
        Schema::create('crm_inquiry_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('crm_email_id')->index();
            $table->string('product_name');
            $table->string('printing')->nullable();
            $table->decimal('length', 12, 3)->nullable();
            $table->decimal('width', 12, 3)->nullable();
            $table->decimal('height', 12, 3)->nullable();
            $table->string('unit', 30)->nullable();
            $table->string('finish_size')->nullable();
            $table->string('open_size')->nullable();
            $table->string('flat_size')->nullable();
            $table->string('stock')->nullable();
            $table->json('finishing_options')->nullable();
            $table->json('quantities')->nullable();
            $table->decimal('price_offered', 14, 2)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('crm_email_id')->references('id')->on('crm_emails')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('crm_inquiry_products');
    }
}
