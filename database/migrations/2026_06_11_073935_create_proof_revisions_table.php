<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProofRevisionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('proof_revisions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('custom_project_id')->nullable(); // custom_projects table id uses bigint
            $table->unsignedBigInteger('crm_email_id')->nullable(); // linked to crm_emails for CRM web workflow
            $table->integer('version_number')->default(1);
            $table->string('file_path');
            $table->text('feedback_notes')->nullable(); // notes from customer describing needed corrections
            $table->string('status')->default('pending'); // pending, approved, revision_needed
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('custom_project_id')->references('id')->on('custom_projects')->onDelete('cascade');
            $table->foreign('crm_email_id')->references('id')->on('crm_emails')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('crm_users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('proof_revisions');
    }
}
