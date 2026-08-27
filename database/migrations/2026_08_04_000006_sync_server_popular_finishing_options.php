<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncServerPopularFinishingOptions extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('crm_finishing_options')) {
            return;
        }

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

        $workspaceIds = Schema::hasTable('crm_workspaces')
            ? DB::table('crm_workspaces')->pluck('id')->all()
            : [];

        if (empty($workspaceIds)) {
            $workspaceIds = [null];
        }

        DB::transaction(function () use ($groups, $workspaceIds) {
            $now = date('Y-m-d H:i:s');

            foreach ($workspaceIds as $workspaceId) {
                foreach ($groups as $parentIndex => $children) {
                    $parentOrder = (array_search($parentIndex, array_keys($groups), true) + 1) * 10;

                    // Additive only: ensure the popular children exist. We intentionally do
                    // NOT delete other children so the full finishing lists are preserved.
                    foreach ($children as $childIndex => $child) {
                        $existingQuery = DB::table('crm_finishing_options')
                            ->where('parent_name', $parentIndex)
                            ->where('child_name', $child);

                        if (is_null($workspaceId)) {
                            $existingQuery->whereNull('workspace_id');
                        } else {
                            $existingQuery->where('workspace_id', $workspaceId);
                        }

                        $existing = $existingQuery->first();
                        $values = [
                            'parent_sort_order' => $parentOrder,
                            'child_sort_order' => ($childIndex + 1) * 10,
                            'updated_at' => $now,
                        ];

                        if ($existing) {
                            DB::table('crm_finishing_options')->where('id', $existing->id)->update($values);
                        } else {
                            DB::table('crm_finishing_options')->insert(array_merge($values, [
                                'workspace_id' => $workspaceId,
                                'parent_name' => $parentIndex,
                                'child_name' => $child,
                                'created_at' => $now,
                            ]));
                        }
                    }
                }
            }
        });
    }

    public function down()
    {
        // The live table was backed up before this targeted synchronization.
    }
}
