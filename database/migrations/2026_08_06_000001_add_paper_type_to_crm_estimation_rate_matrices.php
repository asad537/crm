<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a paper_type column so admins can set a separate paper rate per paper type
 * (Bleach Card / Art Card / Kraft Card / Art Paper) for the same size + GSM.
 * Additive & nullable — existing rates keep working (treated as "any type").
 */
class AddPaperTypeToCrmEstimationRateMatrices extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('crm_estimation_rate_matrices')) return;
        Schema::table('crm_estimation_rate_matrices', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_estimation_rate_matrices', 'paper_type')) {
                $table->string('paper_type', 60)->nullable()->after('type');
            }
        });
    }

    public function down()
    {
        if (Schema::hasTable('crm_estimation_rate_matrices') && Schema::hasColumn('crm_estimation_rate_matrices', 'paper_type')) {
            Schema::table('crm_estimation_rate_matrices', function (Blueprint $table) {
                $table->dropColumn('paper_type');
            });
        }
    }
}
