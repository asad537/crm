<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiscountFieldsToEstimateTicketOptions extends Migration
{
    public function up()
    {
        Schema::table('estimate_ticket_options', function (Blueprint $table) {
            if (!Schema::hasColumn('estimate_ticket_options', 'discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)->default(0)->after('unit_price');
            }
            if (!Schema::hasColumn('estimate_ticket_options', 'discounted_price')) {
                $table->decimal('discounted_price', 12, 2)->nullable()->after('discount_percentage');
            }
        });
    }

    public function down()
    {
        Schema::table('estimate_ticket_options', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('estimate_ticket_options', 'discount_percentage')) {
                $columns[] = 'discount_percentage';
            }
            if (Schema::hasColumn('estimate_ticket_options', 'discounted_price')) {
                $columns[] = 'discounted_price';
            }
            if ($columns) {
                $table->dropColumn($columns);
            }
        });
    }
}
