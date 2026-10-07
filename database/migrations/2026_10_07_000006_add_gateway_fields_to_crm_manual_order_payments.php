<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_manual_order_payments', function (Blueprint $table) {
            foreach ([
                'gateway' => fn () => $table->string('gateway', 40)->nullable()->after('note'),
                'gateway_transaction_id' => fn () => $table->string('gateway_transaction_id', 100)->nullable()->after('gateway'),
                'gateway_auth_code' => fn () => $table->string('gateway_auth_code', 40)->nullable()->after('gateway_transaction_id'),
                'card_type' => fn () => $table->string('card_type', 40)->nullable()->after('gateway_auth_code'),
                'card_last4' => fn () => $table->string('card_last4', 4)->nullable()->after('card_type'),
                'gateway_response' => fn () => $table->text('gateway_response')->nullable()->after('card_last4'),
            ] as $col => $add) {
                if (!Schema::hasColumn('crm_manual_order_payments', $col)) $add();
            }
        });
    }

    public function down(): void
    {
        Schema::table('crm_manual_order_payments', function (Blueprint $table) {
            foreach (['gateway', 'gateway_transaction_id', 'gateway_auth_code', 'card_type', 'card_last4', 'gateway_response'] as $col) {
                if (Schema::hasColumn('crm_manual_order_payments', $col)) $table->dropColumn($col);
            }
        });
    }
};
