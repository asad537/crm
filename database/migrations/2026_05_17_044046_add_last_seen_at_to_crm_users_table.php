<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLastSeenAtToCrmUsersTable extends Migration
{
    public function up()
    {
        Schema::table('crm_users', function (Blueprint $table) {
            $table->timestamp('last_seen_at')->nullable()->after('role');
        });
    }

    public function down()
    {
        Schema::table('crm_users', function (Blueprint $table) {
            $table->dropColumn('last_seen_at');
        });
    }
}
