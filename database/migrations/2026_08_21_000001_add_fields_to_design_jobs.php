<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddFieldsToDesignJobs extends Migration
{
    public function up()
    {
        Schema::table('design_jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('design_jobs', 'estimate_number')) {
                $table->string('estimate_number')->nullable()->after('estimate_ticket_id');
            }
            if (!Schema::hasColumn('design_jobs', 'estimated_delivery_date')) {
                $table->date('estimated_delivery_date')->nullable()->after('status_updated_at');
            }
        });

        // Estimate ticket becomes optional (user may type an estimate number manually).
        try {
            DB::statement('ALTER TABLE design_jobs MODIFY estimate_ticket_id BIGINT UNSIGNED NULL');
        } catch (\Throwable $e) {
            // ignore if already nullable / driver mismatch
        }

        // Migrate legacy statuses to the new production workflow keys.
        $map = [
            'new' => 'designing',
            'proof_sent' => 'mockup',
            'revision' => 'designing',
            'approved' => 'packing',
            'completed' => 'delivered',
        ];
        foreach ($map as $old => $new) {
            DB::table('design_jobs')->where('status', $old)->update(['status' => $new]);
        }
    }

    public function down()
    {
        Schema::table('design_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('design_jobs', 'estimate_number')) {
                $table->dropColumn('estimate_number');
            }
            if (Schema::hasColumn('design_jobs', 'estimated_delivery_date')) {
                $table->dropColumn('estimated_delivery_date');
            }
        });
    }
}
