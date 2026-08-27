<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCrmRejectionLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_rejection_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('crm_email_id'); // crm_emails table id uses bigint
            $table->string('rejection_reason'); // e.g. Price, Delivery, Specs, Competitor, Budget
            $table->unsignedBigInteger('retention_agent_id')->nullable();
            $table->string('status')->default('pending'); // pending, offered_options, resolved_interested, lost_quote
            $table->text('offered_options')->nullable(); // JSON or text of discounts / free qty / better terms offered
            $table->text('follow_up_notes')->nullable();
            $table->timestamps();

            $table->foreign('crm_email_id')->references('id')->on('crm_emails')->onDelete('cascade');
            $table->foreign('retention_agent_id')->references('id')->on('crm_users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('crm_rejection_logs');
    }
}
