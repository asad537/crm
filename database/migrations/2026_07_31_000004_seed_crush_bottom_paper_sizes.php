<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedCrushBottomPaperSizes extends Migration
{
    public function up()
    {
        $workspaces = DB::table('crm_workspaces')->pluck('id')->toArray();
        if (empty($workspaces)) {
            $workspaces = [1];
        }

        $now = now();

        $paperSizes = [
            '20X30' => 0.40,
            '22X28' => 0.42,
            '25X30' => 0.48,
            '23X36' => 0.52,
            '25X36' => 0.55,
            '27X34' => 0.60,
            '28X40' => 0.68,
        ];

        $gsms = ['210', '250', '300', '350'];

        foreach ($workspaces as $workspaceId) {
            foreach ($paperSizes as $size => $baseRate) {
                foreach ($gsms as $gsmIndex => $gsm) {
                    $rate = $baseRate + ($gsmIndex * 0.05);

                    $exists = DB::table('crm_estimation_rate_matrices')
                        ->where('workspace_id', $workspaceId)
                        ->where('type', 'paper_gsm')
                        ->where('paper_size', $size)
                        ->where('gsm', $gsm)
                        ->exists();

                    if (!$exists) {
                        DB::table('crm_estimation_rate_matrices')->insert([
                            'workspace_id' => $workspaceId,
                            'type' => 'paper_gsm',
                            'paper_size' => $size,
                            'gsm' => $gsm,
                            'rate' => round($rate, 4),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }

    public function down()
    {
        DB::table('crm_estimation_rate_matrices')
            ->whereIn('paper_size', ['20X30', '22X28', '25X30', '23X36', '25X36', '27X34', '28X40'])
            ->delete();
    }
}
