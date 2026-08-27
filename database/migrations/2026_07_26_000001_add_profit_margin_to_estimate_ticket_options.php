<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfitMarginToEstimateTicketOptions extends Migration
{
    public function up()
    {
        Schema::table('estimate_ticket_options', function (Blueprint $table) {
            if (!Schema::hasColumn('estimate_ticket_options', 'profit_margin_percentage')) {
                $table->decimal('profit_margin_percentage', 5, 2)->nullable()->after('discounted_price');
            }
        });
    }

    public function down()
    {
        Schema::table('estimate_ticket_options', function (Blueprint $table) {
            if (Schema::hasColumn('estimate_ticket_options', 'profit_margin_percentage')) {
                $table->dropColumn('profit_margin_percentage');
            }
        });
    }
}
