<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demand_request_items', function (Blueprint $table) {
            if (!Schema::hasColumn('demand_request_items', 'vendor_invoice_no')) {
                $table->string('vendor_invoice_no', 120)->nullable()->after('vendor_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('demand_request_items', function (Blueprint $table) {
            if (Schema::hasColumn('demand_request_items', 'vendor_invoice_no')) {
                $table->dropColumn('vendor_invoice_no');
            }
        });
    }
};
