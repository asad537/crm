<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCompletedFinishingStagesToProductionJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            $table->json('completed_finishing_stages')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            $table->dropColumn('completed_finishing_stages');
        });
    }
}
