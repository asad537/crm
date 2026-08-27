<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTeamLeadReviewToEstimates extends Migration
{
    public function up()
    {
        Schema::table('estimate_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('estimate_tickets', 'currency')) $table->string('currency', 10)->default('USD');
            if (!Schema::hasColumn('estimate_tickets', 'team_lead_id')) $table->unsignedBigInteger('team_lead_id')->nullable()->index();
            if (!Schema::hasColumn('estimate_tickets', 'team_lead_notes')) $table->text('team_lead_notes')->nullable();
            if (!Schema::hasColumn('estimate_tickets', 'team_lead_reviewed_at')) $table->timestamp('team_lead_reviewed_at')->nullable();
        });
        Schema::table('estimate_ticket_options', function (Blueprint $table) {
            if (!Schema::hasColumn('estimate_ticket_options', 'offer_price')) $table->decimal('offer_price', 12, 2)->nullable();
            if (!Schema::hasColumn('estimate_ticket_options', 'offer_unit_price')) $table->decimal('offer_unit_price', 12, 4)->nullable();
        });
    }

    public function down()
    {
        Schema::table('estimate_ticket_options', function (Blueprint $table) {
            $table->dropColumn(['offer_price', 'offer_unit_price']);
        });
        Schema::table('estimate_tickets', function (Blueprint $table) {
            $table->dropColumn(['currency', 'team_lead_id', 'team_lead_notes', 'team_lead_reviewed_at']);
        });
    }
}
