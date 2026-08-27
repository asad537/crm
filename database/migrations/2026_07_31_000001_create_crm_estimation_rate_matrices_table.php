<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrmEstimationRateMatricesTable extends Migration
{
    public function up()
    {
        Schema::create('crm_estimation_rate_matrices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('workspace_id')->nullable()->index();
            $table->string('type', 50); // paper_gsm, printing_color, ctp_plate
            $table->string('paper_size', 50)->nullable(); // e.g. 22*33, 25*36
            $table->string('gsm', 30)->nullable(); // e.g. 250, 300, 350
            $table->string('thickness_unit', 30)->nullable(); // PT, Micron, mm
            $table->string('name', 100)->nullable(); // e.g. CMYK, Spot Color, 25x36 Plate
            $table->decimal('rate', 12, 4)->default(0);
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('crm_estimation_rate_matrices');
    }
}
