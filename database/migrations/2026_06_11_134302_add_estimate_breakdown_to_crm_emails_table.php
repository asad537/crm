<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEstimateBreakdownToCrmEmailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->json('estimate_breakdown')->nullable()->after('estimated_price');
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
            $table->dropColumn('estimate_breakdown');
        });
    }
}
