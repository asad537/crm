<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddVatPercentageToVendorPurchasesTable extends Migration
{
    public function up()
    {
        Schema::table('vendor_purchases', function (Blueprint $table) {
            $table->decimal('vat_percentage', 5, 2)->default(5)->after('subtotal');
        });

        DB::table('vendor_purchases')->where('subtotal', '>', 0)->update([
            'vat_percentage' => DB::raw('ROUND((tax_amount / subtotal) * 100, 2)'),
        ]);
    }

    public function down()
    {
        Schema::table('vendor_purchases', function (Blueprint $table) {
            $table->dropColumn('vat_percentage');
        });
    }
}
