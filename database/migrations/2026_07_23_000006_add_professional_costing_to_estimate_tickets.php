<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddProfessionalCostingToEstimateTickets extends Migration
{
    public function up()
    {
        Schema::table('estimate_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('estimate_tickets', 'lamination')) $table->string('lamination')->nullable();
            if (!Schema::hasColumn('estimate_tickets', 'die_cutting')) $table->string('die_cutting')->nullable();
            if (!Schema::hasColumn('estimate_tickets', 'gluing')) $table->string('gluing')->nullable();
            if (!Schema::hasColumn('estimate_tickets', 'shipping_region')) $table->string('shipping_region')->nullable();
            if (!Schema::hasColumn('estimate_tickets', 'cost_breakdown')) $table->text('cost_breakdown')->nullable();
            if (!Schema::hasColumn('estimate_tickets', 'waste_material_percentage')) $table->decimal('waste_material_percentage', 8, 2)->default(0);
            if (!Schema::hasColumn('estimate_tickets', 'waste_material_amount')) $table->decimal('waste_material_amount', 12, 2)->default(0);
            if (!Schema::hasColumn('estimate_tickets', 'estimator_notes')) $table->text('estimator_notes')->nullable();
        });

        DB::statement('ALTER TABLE estimate_tickets MODIFY estimator_id BIGINT UNSIGNED NULL');
    }

    public function down()
    {
        Schema::table('estimate_tickets', function (Blueprint $table) {
            $table->dropColumn([
                'lamination', 'die_cutting', 'gluing', 'shipping_region',
                'cost_breakdown', 'waste_material_percentage',
                'waste_material_amount', 'estimator_notes',
            ]);
        });
    }
}
