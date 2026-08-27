<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-paper-type weight formula. The estimator calculator computes:
 *   weight = (L * W * GSM / weight_divisor) / weight_sheets  ... per sheet
 * so each paper type can carry its own formula (e.g. 15500 / 100, or 31000 / 500).
 * Additive & nullable — used only by paper_type_rate rows.
 */
class AddWeightToPaperTypeRates extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('crm_estimation_rate_matrices')) return;
        Schema::table('crm_estimation_rate_matrices', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_estimation_rate_matrices', 'weight_divisor')) {
                $table->decimal('weight_divisor', 14, 2)->nullable()->after('rate');
            }
            if (!Schema::hasColumn('crm_estimation_rate_matrices', 'weight_sheets')) {
                $table->unsignedInteger('weight_sheets')->nullable()->after('weight_divisor');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('crm_estimation_rate_matrices')) return;
        Schema::table('crm_estimation_rate_matrices', function (Blueprint $table) {
            if (Schema::hasColumn('crm_estimation_rate_matrices', 'weight_sheets')) $table->dropColumn('weight_sheets');
            if (Schema::hasColumn('crm_estimation_rate_matrices', 'weight_divisor')) $table->dropColumn('weight_divisor');
        });
    }
}
