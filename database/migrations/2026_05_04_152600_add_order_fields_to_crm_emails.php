<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOrderFieldsToCrmEmails extends Migration
{
    public function up()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->decimal('order_price', 10, 2)->nullable()->after('quantity');
            $table->integer('order_quantity')->nullable()->after('order_price');
            $table->text('order_notes')->nullable()->after('order_quantity');
            $table->timestamp('order_marked_at')->nullable()->after('order_notes');
            $table->string('order_marked_by')->nullable()->after('order_marked_at');
        });
    }

    public function down()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->dropColumn(['order_price', 'order_quantity', 'order_notes', 'order_marked_at', 'order_marked_by']);
        });
    }
}
