<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportMyboxAppData extends Command
{
    protected $signature = 'mybox:import-app-data {--file= : JSON export file; reads STDIN when omitted}';

    protected $description = 'Upsert MyBox mobile app projects, dielines and mockups into the CRM database.';

    public function handle(): int
    {
        $payload = $this->option('file')
            ? @file_get_contents($this->option('file'))
            : stream_get_contents(STDIN);

        $data = json_decode($payload ?: '', true);
        if (!is_array($data)) {
            $this->error('Invalid MyBox app data export.');
            return self::FAILURE;
        }

        $tables = ['users', 'custom_projects', 'dielines', 'mockups', 'sample_orders', 'production_orders'];
        foreach ($tables as $table) {
            $rows = $data[$table] ?? [];
            if (!is_array($rows) || !$rows) {
                continue;
            }

            foreach (array_chunk($rows, 100) as $chunk) {
                DB::table($table)->upsert($chunk, ['id']);
            }

            $this->line("{$table}: " . count($rows));
        }

        return self::SUCCESS;
    }
}
