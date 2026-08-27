<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPlateAndFacilityToSalesOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->boolean('is_plate_created')->default(false)->after('prepress_notes');
            $table->unsignedBigInteger('production_facility_id')->nullable()->after('is_plate_created');

            $table->foreign('production_facility_id')->references('id')->on('production_facilities')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropForeign(['production_facility_id']);
            $table->dropColumn(['is_plate_created', 'production_facility_id']);
        });
    }
}
