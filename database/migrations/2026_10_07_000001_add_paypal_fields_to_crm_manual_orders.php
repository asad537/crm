<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_manual_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('crm_manual_orders', 'paypal_invoice_id')) {
                $table->string('paypal_invoice_id')->nullable()->after('total');
            }
            if (!Schema::hasColumn('crm_manual_orders', 'paypal_invoice_number')) {
                $table->string('paypal_invoice_number')->nullable()->after('paypal_invoice_id');
            }
            if (!Schema::hasColumn('crm_manual_orders', 'paypal_invoice_status')) {
                $table->string('paypal_invoice_status')->nullable()->after('paypal_invoice_number');
            }
            if (!Schema::hasColumn('crm_manual_orders', 'paypal_invoice_url')) {
                $table->string('paypal_invoice_url', 500)->nullable()->after('paypal_invoice_status');
            }
            if (!Schema::hasColumn('crm_manual_orders', 'paypal_sent_to')) {
                $table->string('paypal_sent_to')->nullable()->after('paypal_invoice_url');
            }
            if (!Schema::hasColumn('crm_manual_orders', 'paypal_sent_at')) {
                $table->timestamp('paypal_sent_at')->nullable()->after('paypal_sent_to');
            }
            if (!Schema::hasColumn('crm_manual_orders', 'paypal_send_count')) {
                $table->unsignedInteger('paypal_send_count')->default(0)->after('paypal_sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('crm_manual_orders', function (Blueprint $table) {
            foreach (['paypal_invoice_id', 'paypal_invoice_number', 'paypal_invoice_status', 'paypal_invoice_url', 'paypal_sent_to', 'paypal_sent_at', 'paypal_send_count'] as $col) {
                if (Schema::hasColumn('crm_manual_orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
