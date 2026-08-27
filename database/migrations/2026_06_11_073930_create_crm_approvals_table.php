<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCrmApprovalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_approvals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('approvable_type');
            $table->unsignedBigInteger('approvable_id');
            $table->string('stage'); // sales_agent_review, customer_quote_review, artwork_proof_review, color_match_review, balance_payment_check
            $table->string('status')->default('pending'); // pending, approved, rejected, revision_requested
            $table->unsignedBigInteger('approver_id')->nullable(); // agent user who approved it (null if customer approved)
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id']);
            $table->foreign('approver_id')->references('id')->on('crm_users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crm_approvals');
    }
}
