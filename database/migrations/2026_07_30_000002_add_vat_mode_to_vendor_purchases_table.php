<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVatModeToVendorPurchasesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('vendor_purchases', 'vat_mode')) {
            Schema::table('vendor_purchases', function (Blueprint $table) {
                $table->string('vat_mode', 20)->default('exclusive')->after('subtotal');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('vendor_purchases', 'vat_mode')) {
            Schema::table('vendor_purchases', function (Blueprint $table) {
                $table->dropColumn('vat_mode');
            });
        }
    }
}
