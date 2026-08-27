<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEstimatorSpecFieldsToEstimateTickets extends Migration
{
    public function up()
    {
        Schema::table('estimate_tickets', function (Blueprint $table) {
            $table->string('printing')->nullable()->after('product_style');
            $table->string('finish_size')->nullable()->after('printing');
            $table->string('flat_size')->nullable()->after('finish_size');
            $table->string('shipping')->nullable()->after('stock');
            $table->string('weight')->nullable()->after('shipping');
        });
    }

    public function down()
    {
        Schema::table('estimate_tickets', function (Blueprint $table) {
            $table->dropColumn(['printing', 'finish_size', 'flat_size', 'shipping', 'weight']);
        });
    }
}
