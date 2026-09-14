<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrmWebsitesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('crm_websites')) {
            Schema::create('crm_websites', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name')->unique();
                $table->string('color', 16)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }
    public function down() { Schema::dropIfExists('crm_websites'); }
}
