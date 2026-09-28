<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAlMassaOrderPayments extends Migration
{
    public function up()
    {
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->decimal('order_paid_opening_amount', 16, 2)->nullable();
        });

        Schema::create('crm_order_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('crm_email_id');
            $table->unsignedBigInteger('workspace_id');
            $table->decimal('amount', 16, 2);
            $table->date('paid_at');
            $table->string('method', 100)->nullable();
            $table->string('reference', 255)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('crm_email_id')->references('id')->on('crm_emails')->onDelete('cascade');
            $table->foreign('workspace_id')->references('id')->on('crm_workspaces')->onDelete('cascade');
            $table->index(['workspace_id', 'crm_email_id']);
        });

        $workspaceId = DB::table('crm_workspaces')->where('slug', 'mybox-packaging-app')->value('id');
        if (!$workspaceId) {
            return;
        }

        // Preserve the financial state of legacy Paid invoices as an opening balance,
        // without inventing a dated receipt or changing other workspaces.
        DB::table('crm_emails')
            ->where('workspace_id', $workspaceId)
            ->whereRaw("LOWER(COALESCE(payment_status, '')) = ?", ['paid'])
            ->where('status', 'Order Done')
            ->orderBy('id')
            ->chunkById(200, function ($orders) {
                foreach ($orders as $order) {
                    $creditApproved = DB::table('sales_orders')
                        ->where('crm_email_id', $order->id)
                        ->where('payment_term', 'credit')
                        ->exists();
                    if ($creditApproved) {
                        continue;
                    }
                    $items = DB::table('crm_order_items')->where('crm_email_id', $order->id);
                    $subtotal = $items->exists()
                        ? (float) $items->sum('line_total')
                        : (float) $order->order_price * (float) $order->order_quantity;
                    $total = round($subtotal * (1 + (float) ($order->vat_percentage ?? 0) / 100), 2);
                    DB::table('crm_emails')->where('id', $order->id)
                        ->update(['order_paid_opening_amount' => $total]);
                }
            });
    }

    public function down()
    {
        Schema::dropIfExists('crm_order_payments');
        Schema::table('crm_emails', function (Blueprint $table) {
            $table->dropColumn('order_paid_opening_amount');
        });
    }
}
