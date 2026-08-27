<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInquiryAttachmentsTable extends Migration
{
    public function up()
    {
        Schema::create('inquiry_attachments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            $table->unsignedBigInteger('crm_email_id')->index();
            $table->unsignedBigInteger('design_ticket_id')->nullable()->index();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('stage', 30)->default('sales');
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inquiry_attachments');
    }
}
