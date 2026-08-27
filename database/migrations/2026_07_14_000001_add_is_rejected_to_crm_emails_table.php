<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsRejectedToCrmEmailsTable extends Migration
{
    public function up()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->boolean('is_rejected')->default(false)->after('is_spam')->index();
        });
    }

    public function down()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->dropColumn('is_rejected');
        });
    }
}
