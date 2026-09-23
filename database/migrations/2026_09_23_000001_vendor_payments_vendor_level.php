<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_purchase_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('vendor_id')->nullable()->after('workspace_id');
            $table->index('vendor_id');
        });
        // Vendor-level payments have no specific purchase.
        DB::statement('ALTER TABLE vendor_purchase_payments MODIFY vendor_purchase_id BIGINT UNSIGNED NULL');

        // Backfill vendor_id from existing purchase-linked payments.
        DB::statement('UPDATE vendor_purchase_payments p JOIN vendor_purchases vp ON vp.id = p.vendor_purchase_id SET p.vendor_id = vp.vendor_id WHERE p.vendor_id IS NULL');
    }

    public function down(): void
    {
        Schema::table('vendor_purchase_payments', function (Blueprint $table) {
            $table->dropColumn('vendor_id');
        });
    }
};
