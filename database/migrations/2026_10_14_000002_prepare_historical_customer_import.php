<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up(): void
    {
        // Keep absent spreadsheet quantities absent, and retain fractional unit prices.
        DB::statement('ALTER TABLE customer_sales MODIFY quantity DECIMAL(15,2) NULL DEFAULT NULL, MODIFY unit_price DECIMAL(18,6) NOT NULL DEFAULT 0');
        Schema::table('customer_sales', function (Blueprint $table) {
            $table->string('import_source_key', 100)->nullable()->unique();
        });
        Schema::table('crm_income_entries', function (Blueprint $table) {
            $table->string('import_source_key', 100)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('crm_income_entries', fn (Blueprint $table) => $table->dropColumn('import_source_key'));
        Schema::table('customer_sales', fn (Blueprint $table) => $table->dropColumn('import_source_key'));
        // Restoring NOT NULL quantity would destroy the distinction between unknown and zero.
        // Leave the widened numeric columns intact on rollback.
    }
};
