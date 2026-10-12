<?php

namespace App\Console\Commands;

use App\CrmEmail;
use App\Services\CustomerOwnership;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * crm:customer-owners — apply "one customer, one CSR" to inquiries that are still unassigned:
 * every unassigned inquiry whose customer email already belongs to a sales agent is assigned
 * to that agent. Dry run by default; --apply writes.
 */
class CustomerOwnersApply extends Command
{
    protected $signature = 'crm:customer-owners {--apply : Write the assignments (default is a dry run)}';
    protected $description = 'Assign unassigned inquiries of known customers to the agent who owns that customer';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $n = 0;
        $q = CrmEmail::withoutGlobalScopes()->whereNull('assigned_to')->whereNotNull('client_email')->where('is_spam', false)->orderBy('id');
        foreach ($q->lazy(200) as $lead) {
            $owner = CustomerOwnership::ownerFor($lead->client_email, $lead->workspace_id ? (int) $lead->workspace_id : null, $lead->id);
            if (!$owner) continue;
            $n++;
            $this->line(sprintf('%s #%d %s → %s', $apply ? 'assign' : 'would assign', $lead->id, $lead->client_email, $owner->name));
            if (!$apply) continue;
            $lead->forceFill(['assigned_to' => $owner->id, 'assigned_by' => $owner->id, 'assigned_at' => now()])->saveQuietly();
            DB::table('crm_assignment_logs')->insert([
                'crm_email_id' => $lead->id, 'assigned_by' => $owner->id, 'assigned_to' => $owner->id,
                'note' => 'Auto-assigned: customer belongs to this agent.', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->info(($apply ? 'Assigned ' : 'Would assign ') . $n . ' inquiries.' . ($apply ? '' : ' Run with --apply to write.'));
        return self::SUCCESS;
    }
}
