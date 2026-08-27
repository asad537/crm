<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeedOrderedCrmFinishingOptions extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('crm_finishing_options')) return;

        Schema::table('crm_finishing_options', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_finishing_options', 'parent_sort_order')) {
                $table->unsignedInteger('parent_sort_order')->default(1000)->after('child_name');
            }
            if (!Schema::hasColumn('crm_finishing_options', 'child_sort_order')) {
                $table->unsignedInteger('child_sort_order')->default(1000)->after('parent_sort_order');
            }
        });

        $groups = [
            'Box Type' => ['Folding Carton','Rigid Box','Corrugated Box','Mailer Box','Sleeve Box','Drawer Box','Magnetic Closure Box','Two-Piece Box','Tuck End Box','Auto-Lock Bottom Box','Display Box','Gift Box'],
            'Lamination' => ['Gloss Gold','Gloss Silver','Matte','Soft Touch','Silk','Anti-Scratch','Velvet','Linen','Thermal','PET','BOPP'],
            'Coating' => ['Aqueous','Gloss UV','Matte UV','Flood UV','Spot UV','Raised Spot UV (3D UV)','Drip-Off UV','Satin','Pearl','Varnish','Gloss Varnish','Matte Varnish','Soft Touch Coating'],
            'Filling' => ['Gold Foil','Silver Foil','Copper Foil','Rose Gold Foil','Holographic Foil','Rainbow Foil','Black Foil','White Foil','Custom Color Foil','Cold Foil','Hot Foil Stamping','Digital Foiling'],
            'Emboss / Deboss' => ['Embossing','Blind Embossing','Registered Embossing','Multi-Level','Sculptured','Combination','Debossing','Foil Embossing','Texture Embossing'],
            'Die Cutting' => ['Standard','Custom Shape','Kiss Cut','Window','Perforation','Half Cut','Through Cut','Creasing','Scoring','Corner Rounding'],
            'Folding' => ['Bi-Fold','Tri-Fold','Z Fold','Gate Fold','Roll Fold','Double Parallel','Accordion','French','Cross'],
            'Gluing' => ['Side Seam','Straight Line','Crash Lock Bottom','Auto Lock Bottom','4 Corner','6 Corner','Double Wall'],
            'Window' => ['PVC Window','PET Window','Frosted Window','Clear Window','Die Cut Window','Window Patching'],
            'Special Effects' => ['Glitter UV','Glitter Foil','Sand','Leather','Linen','Metallic Ink','Fluorescent Ink','Thermochromic','Glow in Dark','Scented','Scratch-Off','High Build UV','Cast & Cure','Soft Feel','Rubber'],
            'Inserts' => ['Foam','EVA Foam','Sponge','Paper','Cardboard','Plastic Tray','Blister','Silk Lining','Velvet Lining'],
            'Closure' => ['Magnetic Closure','Ribbon Closure','Velcro','Elastic','Metal Button','Snap Button'],
            'Handles' => ['Ribbon','Satin Ribbon','Cotton','Rope','Twisted Paper','Plastic','Die Cut Handle'],
            'Special Printing' => ['Pantone','CMYK','Metallic Ink','White Ink','Neon Ink','Double-Sided','Inside','Outside','Inside & Outside'],
            'Assembly' => ['Flat Packed','Pre-Glued','Auto Lock Bottom','Snap Lock Bottom','Tuck Top','Tuck End','Magnetic','Rigid Box Assembly'],
            'E-Flute' => ['Brown','Black','White'],
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
                        ['parent_sort_order' => $parentOrder, 'child_sort_order' => ($childIndex + 1) * 10, 'updated_at' => $now, 'created_at' => $now]
                    );
                }
            }
        }

        // Merge the former parent name without creating a duplicate group.
        DB::table('crm_finishing_options')->where('parent_name', 'Embossing')->get()->each(function ($option) use ($now) {
            DB::table('crm_finishing_options')->updateOrInsert(
                ['workspace_id' => $option->workspace_id, 'parent_name' => 'Emboss / Deboss', 'child_name' => $option->child_name],
                ['parent_sort_order' => 50, 'child_sort_order' => $option->child_sort_order ?: 1000, 'created_by' => $option->created_by, 'updated_at' => $now, 'created_at' => $option->created_at ?: $now]
            );
        });
        DB::table('crm_finishing_options')->where('parent_name', 'Embossing')->delete();
    }

    public function down()
    {
        if (!Schema::hasTable('crm_finishing_options')) return;
        Schema::table('crm_finishing_options', function (Blueprint $table) {
            if (Schema::hasColumn('crm_finishing_options', 'child_sort_order')) $table->dropColumn('child_sort_order');
            if (Schema::hasColumn('crm_finishing_options', 'parent_sort_order')) $table->dropColumn('parent_sort_order');
        });
    }
}
