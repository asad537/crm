<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRejectionDetailsToCrmEmailsTable extends Migration
{
    public function up()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->text('rejection_note')->nullable()->after('is_rejected');
            $table->timestamp('rejected_at')->nullable()->after('rejection_note');
            $table->unsignedBigInteger('rejected_by')->nullable()->after('rejected_at');
        });
    }

    public function down()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->dropColumn(['rejection_note', 'rejected_at', 'rejected_by']);
        });
    }
}
