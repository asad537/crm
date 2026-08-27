<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJobIdToVendorPurchases extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('vendor_purchases', 'job_id')) {
            Schema::table('vendor_purchases', function (Blueprint $table) {
                $table->string('job_id')->nullable()->after('invoice_number')->index();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('vendor_purchases', 'job_id')) {
            Schema::table('vendor_purchases', function (Blueprint $table) {
                $table->dropColumn('job_id');
            });
        }
    }
}
