<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('design_job_card_materials') || Schema::hasColumn('design_job_card_materials', 'inventory_item_id')) return;
        Schema::table('design_job_card_materials', function (Blueprint $table) {
            $table->unsignedBigInteger('inventory_item_id')->nullable()->after('item')->index();
            $table->decimal('consumed_qty', 14, 3)->nullable()->after('qty'); // what was actually taken from stock
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('design_job_card_materials', 'inventory_item_id')) return;
        Schema::table('design_job_card_materials', function (Blueprint $table) {
            $table->dropColumn(['inventory_item_id', 'consumed_qty']);
        });
    }
};
