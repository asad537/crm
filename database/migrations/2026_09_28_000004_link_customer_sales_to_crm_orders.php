<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LinkCustomerSalesToCrmOrders extends Migration
{
    public function up()
    {
        Schema::table('customer_sales', function (Blueprint $table) {
            $table->unsignedBigInteger('crm_email_id')->nullable()->unique();
        });
    }

    public function down()
    {
        Schema::table('customer_sales', function (Blueprint $table) {
            $table->dropUnique(['crm_email_id']);
            $table->dropColumn('crm_email_id');
        });
    }
}
