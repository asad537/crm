<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGluingTypeToProductionJobs extends Migration
{
    public function up()
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('production_jobs', 'gluing_type')) {
                $table->string('gluing_type')->nullable()->after('printing_method');
            }
        });
    }

    public function down()
    {
        Schema::table('production_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('production_jobs', 'gluing_type')) {
                $table->dropColumn('gluing_type');
            }
        });
    }
}
