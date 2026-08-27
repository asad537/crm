<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedPopularCrmFinishingOptions extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('crm_finishing_options')) return;

        $groups = [
            'Lamination' => ['Gloss', 'Matte', 'Soft Touch', 'Velvet'],
            'Coating' => ['Spot UV', 'Drip-Off UV', 'Matte UV'],
            'Foiling' => ['Gold', 'Silver', 'Copper', 'Holographic'],
            'Emboss / Deboss' => ['Embossing', 'Debossing', 'Blind Embossing', 'Blind Debossing'],
            'Die Cutting' => ['Standard', 'Window', 'Perforation'],
            'Gluing' => ['Tuck End', 'One Side', 'Auto Lock Bottom'],
            'Window Patching' => ['PVC'],
            'Special Effects' => ['Velvet'],
            'Handles' => ['Metal', 'Ribbon', 'Draw String'],
            'Inserts' => ['Grey Foam', 'EVA Foam', 'Cardboard', 'Plastic Tray', 'Blister', 'Velvet Pasting'],
            'Closure' => ['Magnetic Closure'],
            'Special Printing' => ['Pantone', 'CMYK', '0/0', '4/0', '0/4', '4/4'],
        ];

        // Normalize former category spellings before replacing the popular lists.
        DB::table('crm_finishing_options')->where('parent_name', 'Filling')->update(['parent_name' => 'Foiling']);
        DB::table('crm_finishing_options')->where('parent_name', 'Window')->update(['parent_name' => 'Window Patching']);

        $workspaceIds = Schema::hasTable('crm_workspaces')
            ? DB::table('crm_workspaces')->pluck('id')->all()
            : [];
        if (empty($workspaceIds)) $workspaceIds = [null];

        $now = date('Y-m-d H:i:s');
        foreach ($workspaceIds as $workspaceId) {
            foreach ($groups as $parentIndex => $children) {
                $parentOrder = (array_search($parentIndex, array_keys($groups), true) + 1) * 10;
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

        // Keep all other database-managed groups below Popular Finishing.
        DB::table('crm_finishing_options')
            ->whereNotIn('parent_name', array_keys($groups))
            ->where('parent_sort_order', '<', 500)
            ->update(['parent_sort_order' => DB::raw('parent_sort_order + 500')]);
    }

    public function down()
    {
        // Seed migrations intentionally retain normalized finishing data.
    }
}
