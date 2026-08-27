<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddQcInspectorToProductionJobs extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('production_jobs', 'qc_inspector_id')) {
            Schema::table('production_jobs', function (Blueprint $table) {
                $table->unsignedBigInteger('qc_inspector_id')->nullable()->after('press_operator_id');
                $table->foreign('qc_inspector_id')->references('id')->on('crm_users')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('production_jobs', 'qc_inspector_id')) {
            Schema::table('production_jobs', function (Blueprint $table) {
                $table->dropForeign(['qc_inspector_id']);
                $table->dropColumn('qc_inspector_id');
            });
        }
    }
}
