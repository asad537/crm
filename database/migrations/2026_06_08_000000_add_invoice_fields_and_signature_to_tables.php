<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInvoiceFieldsAndSignatureToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->text('billing_address')->nullable()->after('order_notes');
            $table->text('shipping_address')->nullable()->after('billing_address');
            $table->string('payment_status')->default('Unpaid')->after('shipping_address');
        });

        Schema::table('crm_users', function (Blueprint $table) {
            $table->text('signature')->nullable()->after('role');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->dropColumn(['billing_address', 'shipping_address', 'payment_status']);
        });

        Schema::table('crm_users', function (Blueprint $table) {
            $table->dropColumn('signature');
        });
    }
}
