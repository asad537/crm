<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrmInquiryNotesTable extends Migration
{
    public function up()
    {
        Schema::create('crm_inquiry_notes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('crm_email_id')->index();
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_role')->nullable();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('crm_inquiry_notes');
    }
}
