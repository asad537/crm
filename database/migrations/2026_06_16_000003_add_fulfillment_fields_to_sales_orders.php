<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFulfillmentFieldsToSalesOrders extends Migration
{
    public function up()
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_orders', 'shipping_stage')) {
                $table->string('shipping_stage')->nullable()->after('status');
                $table->string('customer_type')->nullable()->after('shipping_stage');
                $table->string('shipping_carrier')->nullable()->after('customer_type');
                $table->string('tracking_number')->nullable()->after('shipping_carrier');
                $table->timestamp('balance_received_at')->nullable()->after('tracking_number');
                $table->timestamp('final_payment_received_at')->nullable()->after('balance_received_at');
                $table->timestamp('label_generated_at')->nullable()->after('final_payment_received_at');
                $table->timestamp('shipped_at')->nullable()->after('label_generated_at');
                $table->timestamp('delivered_at')->nullable()->after('shipped_at');
                $table->timestamp('final_invoice_sent_at')->nullable()->after('delivered_at');
                $table->timestamp('payment_posted_at')->nullable()->after('final_invoice_sent_at');
                $table->timestamp('order_completed_at')->nullable()->after('payment_posted_at');
                $table->timestamp('retention_follow_up_at')->nullable()->after('order_completed_at');
                $table->timestamp('reorder_reminder_at')->nullable()->after('retention_follow_up_at');
                $table->text('shipping_notes')->nullable()->after('reorder_reminder_at');
                $table->text('accounts_notes')->nullable()->after('shipping_notes');
            }
        });
    }

    public function down()
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sales_orders', 'shipping_stage')) {
                $table->dropColumn([
                    'shipping_stage',
                    'customer_type',
                    'shipping_carrier',
                    'tracking_number',
                    'balance_received_at',
                    'final_payment_received_at',
                    'label_generated_at',
                    'shipped_at',
                    'delivered_at',
                    'final_invoice_sent_at',
                    'payment_posted_at',
                    'order_completed_at',
                    'retention_follow_up_at',
                    'reorder_reminder_at',
                    'shipping_notes',
                    'accounts_notes',
                ]);
            }
        });
    }
}
