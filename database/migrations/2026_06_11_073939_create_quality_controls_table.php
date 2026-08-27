<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQualityControlsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quality_controls', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('production_order_id')->nullable(); // production_orders table id uses bigint
            $table->unsignedBigInteger('crm_email_id')->nullable(); // linked to crm_emails for CRM web workflow
            $table->unsignedBigInteger('qc_agent_id');
            $table->boolean('dimension_passed')->default(false);
            $table->boolean('fold_color_passed')->default(false);
            $table->boolean('quantity_passed')->default(false);
            $table->boolean('glue_strength_passed')->default(false);
            $table->boolean('barcode_scan_passed')->default(false);
            $table->boolean('packaging_passed')->default(false);
            $table->text('notes')->nullable();
            $table->string('photo_defect_path')->nullable();
            $table->timestamps();

            $table->foreign('production_order_id')->references('id')->on('production_orders')->onDelete('cascade');
            $table->foreign('crm_email_id')->references('id')->on('crm_emails')->onDelete('cascade');
            $table->foreign('qc_agent_id')->references('id')->on('crm_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quality_controls');
    }
}
