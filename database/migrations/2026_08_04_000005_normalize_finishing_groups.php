<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NormalizeFinishingGroups extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('crm_finishing_options')) return;

        DB::transaction(function () {
            $legacyRows = DB::table('crm_finishing_options')
                ->whereRaw('BINARY parent_name = ?', ['E FLUTE'])
                ->get();

            foreach ($legacyRows as $row) {
                DB::table('crm_finishing_options')->updateOrInsert(
                    [
                        'workspace_id' => $row->workspace_id,
                        'parent_name' => 'E-Flute',
                        'child_name' => $row->child_name,
                    ],
                    [
                        'parent_sort_order' => 650,
                        'child_sort_order' => $row->child_sort_order ?: 1000,
                        'created_by' => $row->created_by,
                        'created_at' => $row->created_at ?: date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                );
            }

            DB::table('crm_finishing_options')
                ->whereRaw('BINARY parent_name = ?', ['E FLUTE'])
                ->delete();

            $popularParents = [
                'Lamination', 'Coating', 'Foiling', 'Emboss / Deboss',
                'Die Cutting', 'Gluing', 'Window Patching', 'Special Effects',
                'Handles', 'Inserts', 'Closure', 'Special Printing',
            ];
            foreach ($popularParents as $index => $parent) {
                DB::table('crm_finishing_options')
                    ->where('parent_name', $parent)
                    ->update(['parent_sort_order' => ($index + 1) * 10]);
            }

            $moreParents = [
                'Box Type', 'Folding', 'Assembly', 'E-Flute',
                'Soft Box Pasting', 'Double Box Pasting',
            ];
            foreach ($moreParents as $index => $parent) {
                DB::table('crm_finishing_options')
                    ->where('parent_name', $parent)
                    ->update(['parent_sort_order' => 500 + (($index + 1) * 10)]);
            }
        });
    }

    public function down()
    {
        // Canonical naming and ordering are intentionally retained.
    }
}
