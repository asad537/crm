<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSiteSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_settings', function (Blueprint $table) {

            $table->bigIncrements('id');
            $table->string('site_name')->nullable();
            $table->string('site_logo')->nullable();
            $table->string('site_favicon')->nullable();
            $table->string('site_phone')->nullable();
            $table->string('site_email')->nullable();
            $table->string('site_address')->nullable();
             $table->string('postal_code')->nullable();
            $table->string('region')->nullable();
            $table->string('addressCountry')->nullable();
            $table->string('site_locality')->nullable();
            $table->string('open_hours')->nullable();
            $table->string('whatsapp_number')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('site_settings');
    }
}
