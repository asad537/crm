<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddManualOrderInvoiceAndItems extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('crm_emails', 'order_invoice_number')) {
            Schema::table('crm_emails', function (Blueprint $table) {
                $table->string('order_invoice_number')->nullable()->after('invoice_currency');
            });
        }

        if (!Schema::hasTable('crm_order_items')) {
            Schema::create('crm_order_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('crm_email_id')->index();
                $table->string('product_name');
                $table->decimal('quantity', 12, 2)->default(0);
                $table->decimal('unit_price', 14, 4)->default(0);
                $table->decimal('line_total', 16, 2)->default(0);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('crm_order_items');
        if (Schema::hasColumn('crm_emails', 'order_invoice_number')) {
            Schema::table('crm_emails', function (Blueprint $table) {
                $table->dropColumn('order_invoice_number');
            });
        }
    }
}
