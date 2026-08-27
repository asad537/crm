<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ScopeCrmWorkspaceModules extends Migration
{
    public function up()
    {
        $defaultId = DB::table('crm_workspaces')->where('slug', 'my-box-printing')->value('id');
        foreach (['estimate_tickets', 'crm_internal_messages'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('workspace_id')->nullable()->after('id')->index();
                $table->foreign('workspace_id')->references('id')->on('crm_workspaces');
            });
            DB::table($tableName)->whereNull('workspace_id')->update(['workspace_id' => $defaultId]);
        }
    }

    public function down()
    {
        foreach (['crm_internal_messages', 'estimate_tickets'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['workspace_id']);
                $table->dropColumn('workspace_id');
            });
        }
    }
}
