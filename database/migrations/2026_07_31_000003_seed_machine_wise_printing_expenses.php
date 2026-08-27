<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedMachineWisePrintingExpenses extends Migration
{
    public function up()
    {
        $workspaces = DB::table('crm_workspaces')->pluck('id')->toArray();
        if (empty($workspaces)) {
            $workspaces = [1];
        }

        $now = now();

        $ctpPlates = [
            ['name' => '14X19 Plate', 'rate' => 600.00],
            ['name' => '20X29 Plate', 'rate' => 1000.00],
            ['name' => '28X40 Plate', 'rate' => 3000.00],
        ];

        $colorRates = [
            ['name' => 'CMYK (14X19)', 'ctp_plate_name' => '14X19 Plate', 'rate' => 600.00],
            ['name' => 'SPOT (14X19)', 'ctp_plate_name' => '14X19 Plate', 'rate' => 1000.00],
            ['name' => 'CMYK (20X29)', 'ctp_plate_name' => '20X29 Plate', 'rate' => 1000.00],
            ['name' => 'SPOT (20X29)', 'ctp_plate_name' => '20X29 Plate', 'rate' => 2000.00],
            ['name' => 'CMYK (28X40)', 'ctp_plate_name' => '28X40 Plate', 'rate' => 3000.00],
            ['name' => 'SPOT (28X40)', 'ctp_plate_name' => '28X40 Plate', 'rate' => 5000.00],
        ];

        foreach ($workspaces as $workspaceId) {
            // Seed CTP Plates
            foreach ($ctpPlates as $plate) {
                $exists = DB::table('crm_estimation_rate_matrices')
                    ->where('workspace_id', $workspaceId)
                    ->where('type', 'ctp_plate')
                    ->where('name', $plate['name'])
                    ->exists();

                if (!$exists) {
                    DB::table('crm_estimation_rate_matrices')->insert([
                        'workspace_id' => $workspaceId,
                        'type' => 'ctp_plate',
                        'name' => $plate['name'],
                        'rate' => $plate['rate'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }

            // Seed Printing Colors
            foreach ($colorRates as $color) {
                $exists = DB::table('crm_estimation_rate_matrices')
                    ->where('workspace_id', $workspaceId)
                    ->where('type', 'printing_color')
                    ->where('name', $color['name'])
                    ->exists();

                if (!$exists) {
                    DB::table('crm_estimation_rate_matrices')->insert([
                        'workspace_id' => $workspaceId,
                        'type' => 'printing_color',
                        'name' => $color['name'],
                        'ctp_plate_name' => $color['ctp_plate_name'],
                        'rate' => $color['rate'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down()
    {
        DB::table('crm_estimation_rate_matrices')
            ->whereIn('name', [
                '14X19 Plate', '20X29 Plate', '28X40 Plate',
                'CMYK (14X19)', 'SPOT (14X19)',
                'CMYK (20X29)', 'SPOT (20X29)',
                'CMYK (28X40)', 'SPOT (28X40)',
            ])->delete();
    }
}
