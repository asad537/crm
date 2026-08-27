<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
class AddVendorIdToVendorPurchases extends Migration { public function up(){ Schema::table('vendor_purchases',function(Blueprint $t){$t->unsignedBigInteger('vendor_id')->nullable()->after('created_by')->index();}); } public function down(){Schema::table('vendor_purchases',function(Blueprint $t){$t->dropColumn('vendor_id');});} }
