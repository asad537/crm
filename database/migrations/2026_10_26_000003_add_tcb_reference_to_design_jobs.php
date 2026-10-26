<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The TCB reference number entered next to the Job No on the job card. */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('design_jobs', 'tcb_reference')) {
            Schema::table('design_jobs', function (Blueprint $table) {
                $table->string('tcb_reference', 100)->nullable()->index()->after('job_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('design_jobs', 'tcb_reference')) {
            Schema::table('design_jobs', function (Blueprint $table) {
                $table->dropColumn('tcb_reference');
            });
        }
    }
};
