<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('vendor_purchase_items', 'expense_type')) {
            Schema::table('vendor_purchase_items', function (Blueprint $table) {
                $table->string('expense_type', 60)->nullable()->after('category');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vendor_purchase_items', 'expense_type')) {
            Schema::table('vendor_purchase_items', function (Blueprint $table) {
                $table->dropColumn('expense_type');
            });
        }
    }
};
