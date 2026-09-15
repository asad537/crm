<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('vendor_purchase_items', 'extra')) {
            Schema::table('vendor_purchase_items', function (Blueprint $table) {
                $table->json('extra')->nullable()->after('vat_percentage');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vendor_purchase_items', 'extra')) {
            Schema::table('vendor_purchase_items', function (Blueprint $table) {
                $table->dropColumn('extra');
            });
        }
    }
};
