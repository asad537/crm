<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('vendor_purchase_items', 'vat_percentage')) {
            Schema::table('vendor_purchase_items', function (Blueprint $table) {
                $table->decimal('vat_percentage', 5, 2)->default(0)->after('line_total');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vendor_purchase_items', 'vat_percentage')) {
            Schema::table('vendor_purchase_items', function (Blueprint $table) {
                $table->dropColumn('vat_percentage');
            });
        }
    }
};
