<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddQcRejectionCommentsToDesignJobCards extends Migration
{
    public function up()
    {
        Schema::table('design_job_cards', function (Blueprint $table) {
            $table->text('qc_rejection_comments')->nullable()->after('qc_comments');
        });

        // Keep older rejected-job notes available in the new dedicated field.
        DB::table('design_job_cards')
            ->where('qc_result', 'rejected')
            ->whereNotNull('qc_comments')
            ->update(['qc_rejection_comments' => DB::raw('qc_comments')]);
    }

    public function down()
    {
        Schema::table('design_job_cards', function (Blueprint $table) {
            $table->dropColumn('qc_rejection_comments');
        });
    }
}
