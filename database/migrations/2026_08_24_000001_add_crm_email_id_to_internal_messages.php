<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCrmEmailIdToInternalMessages extends Migration
{
    public function up()
    {
        Schema::table('crm_internal_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_internal_messages', 'crm_email_id')) {
                $table->unsignedBigInteger('crm_email_id')->nullable()->index()->after('receiver_id');
            }
        });
    }

    public function down()
    {
        Schema::table('crm_internal_messages', function (Blueprint $table) {
            if (Schema::hasColumn('crm_internal_messages', 'crm_email_id')) {
                $table->dropColumn('crm_email_id');
            }
        });
    }
}
