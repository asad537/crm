<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('crm_fixed_assets', 'depreciation_rate')) {
            Schema::table('crm_fixed_assets', function (Blueprint $table) {
                $table->decimal('depreciation_rate', 5, 2)->default(0)->after('name');   // % of cost per year, straight-line
            });
        }
        // Assets are now just a name + depreciation rate; category is no longer asked for.
        DB::statement('ALTER TABLE crm_fixed_assets MODIFY category VARCHAR(80) NULL');
    }

    public function down(): void
    {
        DB::table('crm_fixed_assets')->whereNull('category')->update(['category' => 'Other']);
        DB::statement('ALTER TABLE crm_fixed_assets MODIFY category VARCHAR(80) NOT NULL');
        if (Schema::hasColumn('crm_fixed_assets', 'depreciation_rate')) {
            Schema::table('crm_fixed_assets', function (Blueprint $table) {
                $table->dropColumn('depreciation_rate');
            });
        }
    }
};
