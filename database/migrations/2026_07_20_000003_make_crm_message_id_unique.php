<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeCrmMessageIdUnique extends Migration
{
    public function up()
    {
        DB::statement('DELETE newer FROM crm_messages newer INNER JOIN crm_messages older ON newer.message_id = older.message_id AND newer.id > older.id WHERE newer.message_id IS NOT NULL');

        Schema::table('crm_messages', function (Blueprint $table) {
            $table->unique('message_id', 'crm_messages_message_id_unique');
        });
    }

    public function down()
    {
        Schema::table('crm_messages', function (Blueprint $table) {
            $table->dropUnique('crm_messages_message_id_unique');
        });
    }
}
