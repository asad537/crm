<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCtpPlateNameToCrmEstimationRateMatrices extends Migration
{
    public function up()
    {
        Schema::table('crm_estimation_rate_matrices', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_estimation_rate_matrices', 'ctp_plate_name')) {
                $table->string('ctp_plate_name', 100)->nullable()->after('name');
            }
        });
    }

    public function down()
    {
        Schema::table('crm_estimation_rate_matrices', function (Blueprint $table) {
            if (Schema::hasColumn('crm_estimation_rate_matrices', 'ctp_plate_name')) {
                $table->dropColumn('ctp_plate_name');
            }
        });
    }
}
