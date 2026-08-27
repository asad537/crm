<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFirstSheetFilePathToProductionJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            $table->string('first_sheet_file_path')->nullable()->after('status');
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
            $table->dropColumn('first_sheet_file_path');
        });
    }
}
