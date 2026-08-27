<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrmDeletionLogsTable extends Migration
{
    public function up()
    {
        Schema::create('crm_deletion_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_role')->nullable();
            $table->string('entity_type', 60)->index();        // invoice | vendor_purchase | vendor
            $table->unsignedBigInteger('entity_id')->index();
            $table->string('entity_label')->nullable();        // e.g. invoice number, vendor name
            $table->json('snapshot')->nullable();              // key details of deleted row
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('crm_deletion_logs');
    }
}
