<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrencyToCrmEstimationRateMatrices extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('crm_estimation_rate_matrices', 'currency')) {
            Schema::table('crm_estimation_rate_matrices', function (Blueprint $table) {
                $table->string('currency', 3)->default('USD')->after('ctp_plate_name');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('crm_estimation_rate_matrices', 'currency')) {
            Schema::table('crm_estimation_rate_matrices', function (Blueprint $table) {
                $table->dropColumn('currency');
            });
        }
    }
}
