<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDesignJobCardAttachmentsTable extends Migration
{
    public function up()
    {
        Schema::create('design_job_card_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('design_job_card_id');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 150)->nullable();
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->foreign('design_job_card_id', 'djc_attachments_card_fk')
                ->references('id')->on('design_job_cards')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('design_job_card_attachments');
    }
}
