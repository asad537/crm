<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedCrmPastingFinishingOptions extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('crm_finishing_options')) return;

        $groups = [
            'Soft Box Pasting' => [
                'Single Side',
                'Crash Auto Bottom',
                'Other',
            ],
            'Double Box Pasting' => [
                'Corrugations',
                'Sheet to Sheet',
                'Hard Rigid Box Pasting',
            ],
        ];

        $workspaceIds = Schema::hasTable('crm_workspaces')
            ? DB::table('crm_workspaces')->pluck('id')->all()
            : [];
        if (empty($workspaceIds)) $workspaceIds = [null];

        $now = date('Y-m-d H:i:s');
        foreach ($workspaceIds as $workspaceId) {
            foreach ($groups as $parentIndex => $children) {
                $parentOrder = $parentIndex === 'Soft Box Pasting' ? 85 : 86;
                foreach ($children as $childIndex => $child) {
                    DB::table('crm_finishing_options')->updateOrInsert(
                        [
                            'workspace_id' => $workspaceId,
                            'parent_name' => $parentIndex,
                            'child_name' => $child,
                        ],
                        [
                            'parent_sort_order' => $parentOrder,
                            'child_sort_order' => ($childIndex + 1) * 10,
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );
                }
            }
        }
    }

    public function down()
    {
        if (!Schema::hasTable('crm_finishing_options')) return;

        DB::table('crm_finishing_options')
            ->whereIn('parent_name', ['Soft Box Pasting', 'Double Box Pasting'])
            ->delete();
    }
}
