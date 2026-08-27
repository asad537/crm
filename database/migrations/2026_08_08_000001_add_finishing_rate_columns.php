<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Finishing rate columns. Some finishing operations are size-tiered:
 *   rate            = the "small" rate (piece fits within threshold_short × threshold_long)
 *   rate_large      = the "large" rate (piece bigger than the threshold)
 *   threshold_short = short side of the size threshold (e.g. 18 in)
 *   threshold_long  = long side  of the size threshold (e.g. 23 in)
 * Additive & nullable — only finishing rate rows use them.
 */
class AddFinishingRateColumns extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('crm_estimation_rate_matrices')) return;
        Schema::table('crm_estimation_rate_matrices', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_estimation_rate_matrices', 'rate_large')) {
                $table->decimal('rate_large', 14, 4)->nullable()->after('rate');
            }
            if (!Schema::hasColumn('crm_estimation_rate_matrices', 'threshold_short')) {
                $table->decimal('threshold_short', 8, 2)->nullable()->after('rate_large');
            }
            if (!Schema::hasColumn('crm_estimation_rate_matrices', 'threshold_long')) {
                $table->decimal('threshold_long', 8, 2)->nullable()->after('threshold_short');
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('crm_estimation_rate_matrices')) return;
        Schema::table('crm_estimation_rate_matrices', function (Blueprint $table) {
            foreach (['threshold_long', 'threshold_short', 'rate_large'] as $c) {
                if (Schema::hasColumn('crm_estimation_rate_matrices', $c)) $table->dropColumn($c);
            }
        });
    }
}
