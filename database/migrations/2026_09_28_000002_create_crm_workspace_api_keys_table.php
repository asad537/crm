<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrmWorkspaceApiKeysTable extends Migration
{
    public function up()
    {
        Schema::create('crm_workspace_api_keys', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('workspace_id');
            $table->string('name', 100);
            $table->string('key_hash', 64)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('workspace_id')->references('id')->on('crm_workspaces')->onDelete('cascade');
            $table->unique(['workspace_id', 'name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('crm_workspace_api_keys');
    }
}
