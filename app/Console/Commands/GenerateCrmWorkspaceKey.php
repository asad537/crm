<?php

namespace App\Console\Commands;

use App\CrmWorkspace;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateCrmWorkspaceKey extends Command
{
    protected $signature = 'crm:workspace-key {workspace : Workspace slug}';
    protected $description = 'Generate a new API key for a CRM workspace';

    public function handle()
    {
        $workspace = CrmWorkspace::where('slug', $this->argument('workspace'))->first();
        if (!$workspace) {
            $this->error('CRM workspace not found.');
            return 1;
        }

        $plainKey = 'crm_' . Str::random(48);
        $workspace->update(['api_key_hash' => hash('sha256', $plainKey)]);

        $this->warn('Copy this key now; it cannot be shown again:');
        $this->line($plainKey);
        return 0;
    }
}
