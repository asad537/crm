<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJobStartOnToDesignJobCards extends Migration
{
    public function up()
    {
        Schema::table('design_job_cards', function (Blueprint $table) {
            $table->date('job_start_on')->nullable()->after('job_date');
        });
    }

    public function down()
    {
        Schema::table('design_job_cards', function (Blueprint $table) {
            $table->dropColumn('job_start_on');
        });
    }
}
