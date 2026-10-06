<?php

namespace App\Console\Commands;

use App\CrmWorkspace;
use App\DesignJob;
use App\DesignJobCard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDesignJobStatus extends Command
{
    protected $signature = 'design-jobs:import-status {file} {--workspace=} {--dry-run}';
    protected $description = 'Import sparse design jobs from a reviewed Job Status Update JSON file';

    public function handle()
    {
        $workspaceId = (int) $this->option('workspace');
        $workspace = CrmWorkspace::find($workspaceId);
        if (!$workspace) {
            $this->error('A valid --workspace ID is required.');
            return 1;
        }
        $path = $this->argument('file');
        if (!is_file($path) || !is_readable($path)) {
            $this->error('Import file is not readable.');
            return 1;
        }

        try {
            $rows = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($rows) || !$rows) {
                throw new \RuntimeException('Import file has no jobs.');
            }
            $numbers = [];
            $largest = 0;
            foreach ($rows as $row) {
                if (!is_array($row) || !preg_match('/^AMS-(\d{4,})$/', $row['job_number'] ?? '', $match)
                    || empty($row['title']) || !array_key_exists('details', $row)
                    || !in_array($row['status'] ?? null, array_keys(DesignJob::STATUSES), true)
                    || (!empty($row['production_stage'])
                        && !in_array($row['production_stage'], array_keys(DesignJob::STAGES), true))) {
                    throw new \RuntimeException('Invalid job record in import file.');
                }
                if (isset($numbers[$row['job_number']])) {
                    throw new \RuntimeException('Duplicate job number: ' . $row['job_number']);
                }
                $numbers[$row['job_number']] = true;
                $largest = max($largest, (int) $match[1]);
                $existing = DesignJob::where('job_number', $row['job_number'])->first();
                if ($existing && (int) $existing->workspace_id !== $workspaceId) {
                    throw new \RuntimeException($row['job_number'] . ' belongs to another workspace.');
                }
            }

            $existingCount = DesignJob::where('workspace_id', $workspaceId)
                ->whereIn('job_number', array_keys($numbers))->count();
            $this->info(sprintf('%s: %d rows, %d already present, %d to add; last number AMS-%04d.',
                $workspace->name, count($rows), $existingCount, count($rows) - $existingCount, $largest));
            if ($this->option('dry-run')) {
                return 0;
            }

            DB::transaction(function () use ($rows, $workspaceId, $largest) {
                foreach ($rows as $row) {
                    if (DesignJob::where('job_number', $row['job_number'])->exists()) {
                        continue;
                    }
                    $job = DesignJob::create([
                        'job_number' => $row['job_number'],
                        'workspace_id' => $workspaceId,
                        'designer_id' => null,
                        'title' => $row['title'],
                        'client_name' => $row['client_name'] ?? null,
                        'details' => $row['details'],
                        'status' => $row['status'],
                        'production_stage' => $row['production_stage'] ?? null,
                        'status_updated_at' => now(),
                        'receive_date' => $row['received_date'] ?? null,
                        'due_date' => $row['due_date'] ?? null,
                    ]);
                    DesignJobCard::create([
                        'design_job_id' => $job->id,
                        'job_no' => $job->job_number,
                        'product' => $job->title,
                        'job_date' => $row['received_date'] ?? null,
                        'section_choices' => [
                            '__active_step' => 0,
                            '__completed_step' => 0,
                            '__active_label' => 'Job Header',
                        ],
                    ]);
                }

                $sequence = DB::table('design_job_number_sequences')
                    ->where('workspace_id', $workspaceId)->lockForUpdate()->first();
                if ($sequence && $sequence->prefix !== 'AMS') {
                    throw new \RuntimeException('Workspace already uses a different job-number prefix.');
                }
                if ($sequence) {
                    DB::table('design_job_number_sequences')->where('workspace_id', $workspaceId)
                        ->update(['last_number' => max((int) $sequence->last_number, $largest), 'updated_at' => now()]);
                } else {
                    DB::table('design_job_number_sequences')->insert([
                        'workspace_id' => $workspaceId,
                        'prefix' => 'AMS',
                        'last_number' => $largest,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
            $this->info('Import complete. Future generated numbers continue after the last imported number.');
            return 0;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }
}
