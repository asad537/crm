<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RestoreExtendedCrmFinishingOptions extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('crm_finishing_options')) return;

        // This is deliberately additive. Existing custom finishing options are never deleted.
        $groups = [
            'Box Type' => ['Folding Carton', 'Rigid Box', 'Corrugated Box', 'Mailer Box', 'Sleeve Box', 'Drawer Box', 'Magnetic Closure Box', 'Two-Piece Box', 'Tuck End Box', 'Auto-Lock Bottom Box', 'Display Box', 'Gift Box'],
            'Lamination' => ['Gloss', 'Matte', 'Soft Touch', 'Velvet', 'Gloss Gold', 'Gloss Silver', 'Silk', 'Anti-Scratch', 'Linen', 'Thermal', 'PET', 'BOPP', 'Sticker'],
            'Coating' => ['Spot UV', 'Drip-Off UV', 'Matte UV', 'Aqueous', 'Gloss UV', 'Flood UV', 'Raised Spot UV (3D UV)', 'Satin', 'Pearl', 'Varnish', 'Gloss Varnish', 'Matte Varnish', 'Soft Touch Coating'],
            'Foiling' => ['Gold', 'Silver', 'Copper', 'Holographic', 'Gold Foil', 'Silver Foil', 'Copper Foil', 'Rose Gold Foil', 'Holographic Foil', 'Rainbow Foil', 'Black Foil', 'White Foil', 'Custom Color Foil', 'Cold Foil', 'Hot Foil Stamping', 'Digital Foiling'],
            'Emboss / Deboss' => ['Embossing', 'Debossing', 'Blind Embossing', 'Blind Debossing', 'Registered Embossing', 'Multi-Level', 'Sculptured', 'Combination', 'Foil Embossing', 'Texture Embossing'],
            'Die Cutting' => ['Standard', 'Window', 'Perforation', 'Custom Shape', 'Kiss Cut', 'Half Cut', 'Through Cut', 'Creasing', 'Scoring', 'Corner Rounding'],
            'Folding' => ['Bi-Fold', 'Tri-Fold', 'Z Fold', 'Gate Fold', 'Roll Fold', 'Double Parallel', 'Accordion', 'French', 'Cross'],
            'Gluing' => ['Tuck End', 'One Side', 'Auto Lock Bottom', 'Side Seam', 'Straight Line', 'Crash Lock Bottom', '4 Corner', '6 Corner', 'Double Wall'],
            'Window Patching' => ['PVC', 'PVC Window', 'PET Window', 'Frosted Window', 'Clear Window', 'Die Cut Window', 'Window Patching'],
            'Special Effects' => ['Velvet', 'Glitter UV', 'Glitter Foil', 'Sand', 'Leather', 'Linen', 'Metallic Ink', 'Fluorescent Ink', 'Thermochromic', 'Glow in Dark', 'Scented', 'Scratch-Off', 'High Build UV', 'Cast & Cure', 'Soft Feel', 'Rubber'],
            'Handles' => ['Metal', 'Ribbon', 'Draw String', 'Satin Ribbon', 'Cotton', 'Rope', 'Twisted Paper', 'Plastic', 'Die Cut Handle'],
            'Inserts' => ['Grey Foam', 'EVA Foam', 'Cardboard', 'Plastic Tray', 'Blister', 'Velvet Pasting', 'Foam', 'Sponge', 'Paper', 'Silk Lining', 'Velvet Lining'],
            'Closure' => ['Magnetic Closure', 'Ribbon Closure', 'Velcro', 'Elastic', 'Metal Button', 'Snap Button'],
            'Special Printing' => ['Pantone', 'CMYK', '0/0', '4/0', '0/4', '4/4', 'Metallic Ink', 'White Ink', 'Neon Ink', 'Double-Sided', 'Inside', 'Outside', 'Inside & Outside'],
            'Assembly' => ['Flat Packed', 'Pre-Glued', 'Auto Lock Bottom', 'Snap Lock Bottom', 'Tuck Top', 'Tuck End', 'Magnetic', 'Rigid Box Assembly'],
            'E-Flute' => ['Brown', 'Black', 'White'],
            'Soft Box Pasting' => ['Single Side', 'Crash Auto Bottom', 'Other'],
            'Double Box Pasting' => ['Corrugations', 'Sheet to Sheet', 'Hard Rigid Box Pasting'],
        ];

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
                        ['workspace_id' => $workspaceId, 'parent_name' => $parentIndex, 'child_name' => $child],
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
        // Restoration is additive; rollback must not delete customer-created records.
    }
}
