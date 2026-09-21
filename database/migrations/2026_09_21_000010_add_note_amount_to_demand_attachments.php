<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demand_request_attachments', function (Blueprint $table) {
            $table->string('note', 255)->nullable()->after('name');
            $table->decimal('amount', 14, 2)->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('demand_request_attachments', function (Blueprint $table) {
            $table->dropColumn(['note', 'amount']);
        });
    }
};
