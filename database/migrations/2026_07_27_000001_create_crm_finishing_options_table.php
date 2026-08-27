<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrmFinishingOptionsTable extends Migration
{
    public function up()
    {
        Schema::create('crm_finishing_options', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            $table->string('parent_name', 80);
            $table->string('child_name', 100);
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->unique(
                ['workspace_id', 'parent_name', 'child_name'],
                'crm_finishing_options_workspace_parent_child_unique'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('crm_finishing_options');
    }
}
