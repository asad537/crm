<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demand_request_payments', function (Blueprint $table) {
            if (!Schema::hasColumn('demand_request_payments', 'attachment_path')) {
                $table->string('attachment_path')->nullable()->after('note');
                $table->string('attachment_name')->nullable()->after('attachment_path');
                $table->string('attachment_mime')->nullable()->after('attachment_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('demand_request_payments', function (Blueprint $table) {
            if (Schema::hasColumn('demand_request_payments', 'attachment_path')) {
                $table->dropColumn(['attachment_path', 'attachment_name', 'attachment_mime']);
            }
        });
    }
};
