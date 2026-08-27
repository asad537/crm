<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePopularProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('popular_products', function (Blueprint $table) {
            $table->increments('id');
            $table->string('page_name', 255);
            $table->string('page_url', 255);
            $table->mediumText('page_desc');
            $table->string('m_title', 255)->nullable();
            $table->string('m_des', 255)->nullable();
            $table->string('m_tags', 255)->nullable();
            $table->string('preference', 255)->nullable();
            $table->integer('status')->nullable();
            $table->string('time', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('popular_products');
    }
}

