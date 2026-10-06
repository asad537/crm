<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PrepareDesignJobsForStatusImport extends Migration
{
    public function up()
    {
        Schema::table('design_jobs', function (Blueprint $table) {
            $table->unsignedBigInteger('designer_id')->nullable()->change();
            $table->string('client_name')->nullable()->after('title');
        });

        Schema::create('design_job_number_sequences', function (Blueprint $table) {
            $table->unsignedBigInteger('workspace_id')->primary();
            $table->string('prefix', 20);
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('design_job_number_sequences');
        Schema::table('design_jobs', function (Blueprint $table) {
            $table->dropColumn('client_name');
        });
        // Imported jobs may intentionally have no designer. Do not make this
        // column NOT NULL again without first assigning those jobs.
    }
}
