<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillMyboxQuoteWorkspace extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('crm_workspaces') || !Schema::hasColumn('crm_emails', 'workspace_id')) {
            return;
        }

        $workspaceId = DB::table('crm_workspaces')
            ->where('slug', 'my-box-printing')
            ->value('id');

        if (!$workspaceId) {
            return;
        }

        DB::table('crm_emails')
            ->whereNull('workspace_id')
            ->update([
                'workspace_id' => $workspaceId,
                'source' => DB::raw("COALESCE(source, 'MyBox Website')"),
            ]);
    }

    public function down()
    {
        // Data repair is intentionally not reversed.
    }
}
