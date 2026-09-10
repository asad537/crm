<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInquiryProductToEstimateTickets extends Migration
{
    public function up()
    {
        if (Schema::hasTable('estimate_tickets') && !Schema::hasColumn('estimate_tickets', 'inquiry_product_id')) {
            Schema::table('estimate_tickets', function (Blueprint $table) {
                $table->unsignedBigInteger('inquiry_product_id')->nullable()->after('crm_email_id')->index();
            });
        }
    }
    public function down()
    {
        if (Schema::hasColumn('estimate_tickets', 'inquiry_product_id')) {
            Schema::table('estimate_tickets', function (Blueprint $table) { $table->dropColumn('inquiry_product_id'); });
        }
    }
}
