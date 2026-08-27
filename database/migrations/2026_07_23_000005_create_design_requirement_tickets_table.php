<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDesignRequirementTicketsTable extends Migration
{
    public function up()
    {
        Schema::create('design_requirement_tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            $table->string('ticket_number')->unique();
            $table->unsignedBigInteger('crm_email_id')->index();
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->unsignedBigInteger('claimed_by')->nullable()->index();
            $table->unsignedBigInteger('estimate_ticket_id')->nullable()->index();
            $table->string('status', 30)->default('new')->index();
            $table->text('quantities')->nullable();
            $table->string('open_size')->nullable();
            $table->string('unit', 20)->nullable();
            $table->text('designer_notes')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('forwarded_at')->nullable();
            $table->timestamps();
            $table->index(['workspace_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('design_requirement_tickets');
    }
}
