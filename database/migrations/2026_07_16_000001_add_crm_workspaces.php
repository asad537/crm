<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddCrmWorkspaces extends Migration
{
    public function up()
    {
        Schema::create('crm_workspaces', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('api_key_hash', 64)->nullable()->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $defaultId = DB::table('crm_workspaces')->insertGetId([
            'name' => 'My Box Printing',
            'slug' => 'my-box-printing',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $appWorkspaceId = DB::table('crm_workspaces')->insertGetId([
            'name' => 'Al Massa Packaging',
            'slug' => 'mybox-packaging-app',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Schema::create('crm_user_workspace', function (Blueprint $table) {
            $table->unsignedBigInteger('crm_user_id');
            $table->unsignedBigInteger('workspace_id');
            $table->string('role', 50);
            $table->timestamps();
            $table->primary(['crm_user_id', 'workspace_id']);
            $table->foreign('crm_user_id')->references('id')->on('crm_users')->onDelete('cascade');
            $table->foreign('workspace_id')->references('id')->on('crm_workspaces')->onDelete('cascade');
        });

        DB::table('crm_users')->orderBy('id')->chunk(200, function ($users) use ($defaultId, $now) {
            $rows = [];
            foreach ($users as $user) {
                $rows[] = [
                    'crm_user_id' => $user->id,
                    'workspace_id' => $defaultId,
                    'role' => $user->role,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            if ($rows) DB::table('crm_user_workspace')->insert($rows);
        });
        $adminRows = DB::table('crm_users')->where('role', 'admin')->get()->map(function ($user) use ($appWorkspaceId, $now) {
            return ['crm_user_id' => $user->id, 'workspace_id' => $appWorkspaceId, 'role' => 'admin', 'created_at' => $now, 'updated_at' => $now];
        })->all();
        if ($adminRows) DB::table('crm_user_workspace')->insert($adminRows);

        Schema::table('crm_emails', function (Blueprint $table) {
            $table->unsignedBigInteger('workspace_id')->nullable()->after('id');
            $table->string('external_lead_id')->nullable()->after('workspace_id');
            $table->index('workspace_id');
            $table->unique(['workspace_id', 'external_lead_id'], 'crm_email_workspace_external_unique');
            $table->foreign('workspace_id')->references('id')->on('crm_workspaces');
        });
        DB::table('crm_emails')->whereNull('workspace_id')->update(['workspace_id' => $defaultId]);
    }

    public function down()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->dropForeign(['workspace_id']);
            $table->dropUnique('crm_email_workspace_external_unique');
            $table->dropIndex(['workspace_id']);
            $table->dropColumn(['workspace_id', 'external_lead_id']);
        });
        Schema::dropIfExists('crm_user_workspace');
        Schema::dropIfExists('crm_workspaces');
    }
}
