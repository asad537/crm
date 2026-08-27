<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductionSupervisorToProductionJobs extends Migration
{
    public function up()
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('production_jobs', 'production_supervisor_id')) {
                $table->unsignedBigInteger('production_supervisor_id')->nullable()->after('production_manager_id');
                $table->foreign('production_supervisor_id')->references('id')->on('crm_users')->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('production_jobs', 'production_supervisor_id')) {
                $table->dropForeign(['production_supervisor_id']);
                $table->dropColumn('production_supervisor_id');
            }
        });
    }
}
