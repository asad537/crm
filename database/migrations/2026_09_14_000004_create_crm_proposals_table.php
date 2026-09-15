<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('crm_proposals')) return;

        Schema::create('crm_proposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            $table->unsignedBigInteger('crm_email_id')->nullable()->index(); // source inquiry, when raised from an inquiry
            $table->string('subject');
            $table->string('client_name')->nullable();
            $table->string('product_name')->nullable();
            $table->string('quantity')->nullable();
            $table->string('size')->nullable();
            $table->text('comment')->nullable();
            $table->string('server_path')->nullable();
            $table->unsignedBigInteger('assigned_designer_id')->nullable()->index();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('status')->default('requested'); // requested -> in_progress -> change_requested -> completed
            $table->text('change_request_note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_proposals');
    }
};
