<?php

namespace Tests\Feature;

use App\CrmProposal;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CrmProposalSidebarCountTest extends TestCase
{
    public function test_active_proposal_count_respects_workspace_and_designer_visibility(): void
    {
        DB::beginTransaction();

        try {
            $workspaceId = 2;
            CrmProposal::withoutGlobalScopes()->where('workspace_id', $workspaceId)->delete();

            CrmProposal::create(['workspace_id' => $workspaceId, 'subject' => 'Open pool', 'status' => 'requested']);
            CrmProposal::create(['workspace_id' => $workspaceId, 'subject' => 'Mine', 'status' => 'requested', 'assigned_designer_id' => 101]);
            CrmProposal::create(['workspace_id' => $workspaceId, 'subject' => 'Another designer', 'status' => 'requested', 'assigned_designer_id' => 202]);
            CrmProposal::create(['workspace_id' => $workspaceId, 'subject' => 'Already open', 'status' => 'in_progress', 'assigned_designer_id' => 101]);
            CrmProposal::create(['workspace_id' => 1, 'subject' => 'Other workspace', 'status' => 'requested']);

            $admin = new class {
                public $id = 1;
                public function isAdmin() { return true; }
            };
            $designer = new class {
                public $id = 101;
                public function isAdmin() { return false; }
            };

            $this->assertSame(3, CrmProposal::activeTicketCountFor($admin, $workspaceId));
            $this->assertSame(2, CrmProposal::activeTicketCountFor($designer, $workspaceId));
        } finally {
            DB::rollBack();
        }
    }
}
