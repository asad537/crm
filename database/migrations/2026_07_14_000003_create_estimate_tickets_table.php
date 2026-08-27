<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEstimateTicketsTable extends Migration
{
    public function up()
    {
        Schema::create('estimate_tickets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ticket_number')->unique();
            $table->unsignedBigInteger('crm_email_id')->nullable()->index();
            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->string('product_style');
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->string('unit', 20)->default('inches');
            $table->string('stock')->nullable();
            $table->string('colors')->nullable();
            $table->string('coating')->nullable();
            $table->text('requirements')->nullable();
            $table->text('attachments')->nullable();
            $table->unsignedBigInteger('estimator_id')->index();
            $table->unsignedBigInteger('requested_by')->index();
            $table->string('status', 30)->default('pending')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('estimate_ticket_options', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('estimate_ticket_id')->index();
            $table->unsignedInteger('quantity');
            $table->date('required_date')->nullable();
            $table->text('option_notes')->nullable();
            $table->decimal('total_price', 12, 2)->nullable();
            $table->decimal('unit_price', 12, 4)->nullable();
            $table->text('estimator_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('estimate_ticket_options');
        Schema::dropIfExists('estimate_tickets');
    }
}
