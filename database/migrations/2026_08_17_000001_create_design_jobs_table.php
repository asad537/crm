<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDesignJobsTable extends Migration
{
    public function up()
    {
        Schema::create('design_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('job_number')->unique();
            $table->unsignedBigInteger('workspace_id')->index();
            $table->unsignedBigInteger('estimate_ticket_id')->index();
            $table->unsignedBigInteger('designer_id')->index();
            $table->string('title');
            $table->text('details')->nullable();
            $table->string('status', 30)->default('new')->index();
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('design_jobs');
    }
}
