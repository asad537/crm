<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttachmentToVendorPurchases extends Migration
{
    public function up()
    {
        Schema::table('vendor_purchases', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('notes');
            $table->string('attachment_name')->nullable()->after('attachment_path');
            $table->string('attachment_mime', 100)->nullable()->after('attachment_name');
        });
    }

    public function down()
    {
        Schema::table('vendor_purchases', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_mime']);
        });
    }
}
