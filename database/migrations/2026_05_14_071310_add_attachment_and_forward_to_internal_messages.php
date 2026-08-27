<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAttachmentAndForwardToInternalMessages extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('crm_internal_messages', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('message_body');
            $table->string('attachment_name')->nullable()->after('attachment_path');
            $table->boolean('is_forwarded')->default(0)->after('attachment_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('crm_internal_messages', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'attachment_name', 'is_forwarded']);
        });
    }
}
