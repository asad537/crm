<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_manual_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_manual_orders', 'invoice_sent_to')) {
                $table->string('invoice_sent_to')->nullable()->after('cca_send_count');
            }
            if (!Schema::hasColumn('crm_manual_orders', 'invoice_sent_at')) {
                $table->timestamp('invoice_sent_at')->nullable()->after('invoice_sent_to');
            }
            if (!Schema::hasColumn('crm_manual_orders', 'invoice_send_count')) {
                $table->unsignedInteger('invoice_send_count')->default(0)->after('invoice_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('crm_manual_orders', function (Blueprint $table) {
            foreach (['invoice_sent_to', 'invoice_sent_at', 'invoice_send_count'] as $col) {
                if (Schema::hasColumn('crm_manual_orders', $col)) $table->dropColumn($col);
            }
        });
    }
};
