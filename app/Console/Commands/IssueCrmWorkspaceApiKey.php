<?php

namespace App\Console\Commands;

use App\CrmWorkspace;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IssueCrmWorkspaceApiKey extends Command
{
    protected $signature = 'crm:issue-api-key {workspace : Workspace slug} {name : Integration name}';
    protected $description = 'Issue an additional API key without rotating the existing workspace key';

    public function handle()
    {
        $workspace = CrmWorkspace::where('slug', $this->argument('workspace'))
            ->where('is_active', true)->first();

        if (!$workspace) {
            $this->error('Active CRM workspace not found.');
            return 1;
        }

        $name = $this->argument('name');
        if (DB::table('crm_workspace_api_keys')
            ->where('workspace_id', $workspace->id)
            ->where('name', $name)->exists()) {
            $this->error('An API key with that name already exists for this workspace.');
            return 1;
        }

        $key = 'crm_' . Str::random(48);
        DB::table('crm_workspace_api_keys')->insert([
            'workspace_id' => $workspace->id,
            'name' => $name,
            'key_hash' => hash('sha256', $key),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->warn('Copy this key now; it cannot be shown again:');
        $this->line($key);
        return 0;
    }
}
