<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_manual_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_manual_orders', 'cca_sent_to')) {
                $table->string('cca_sent_to')->nullable()->after('paypal_send_count');
            }
            if (!Schema::hasColumn('crm_manual_orders', 'cca_sent_at')) {
                $table->timestamp('cca_sent_at')->nullable()->after('cca_sent_to');
            }
            if (!Schema::hasColumn('crm_manual_orders', 'cca_send_count')) {
                $table->unsignedInteger('cca_send_count')->default(0)->after('cca_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('crm_manual_orders', function (Blueprint $table) {
            foreach (['cca_sent_to', 'cca_sent_at', 'cca_send_count'] as $col) {
                if (Schema::hasColumn('crm_manual_orders', $col)) $table->dropColumn($col);
            }
        });
    }
};
