<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddProductionFacilityToCrmUsers extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('crm_users', 'production_facility_id')) {
            return;
        }

        Schema::table('crm_users', function (Blueprint $table) {
            $table->unsignedBigInteger('production_facility_id')->nullable()->after('role');
            $table->foreign('production_facility_id')
                ->references('id')->on('production_facilities')->onDelete('set null');
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('crm_users', 'production_facility_id')) {
            return;
        }

        Schema::table('crm_users', function (Blueprint $table) {
            $table->dropForeign(['production_facility_id']);
            $table->dropColumn('production_facility_id');
        });
    }
}
