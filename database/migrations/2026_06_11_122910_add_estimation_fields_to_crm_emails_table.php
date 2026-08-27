<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEstimationFieldsToCrmEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->unsignedBigInteger('estimator_id')->nullable();
            $table->string('estimate_status')->nullable();
            $table->string('estimated_price')->nullable();
            $table->text('estimator_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->dropColumn(['estimator_id', 'estimate_status', 'estimated_price', 'estimator_notes']);
        });
    }
}
