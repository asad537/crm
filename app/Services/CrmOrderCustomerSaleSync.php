<?php

namespace App\Services;

use App\CrmCustomer;
use App\CrmEmail;
use App\CustomerSale;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CrmOrderCustomerSaleSync
{
    public function sync(CrmEmail $order): ?CustomerSale
    {
        if (!$order->workspace || $order->workspace->slug !== 'mybox-packaging-app') {
            return null;
        }

        return DB::transaction(function () use ($order) {
            $sale = CustomerSale::where('workspace_id', $order->workspace_id)
                ->where('crm_email_id', $order->id)->first();

            if ($sale) {
                $customer = $sale->customer;
            } else {
                $email = strtolower(trim((string) $order->client_email));
                $customer = $email !== ''
                    ? CrmCustomer::where('workspace_id', $order->workspace_id)
                        ->whereRaw('LOWER(email) = ?', [$email])->orderBy('id')->first()
                    : null;
                if (!$customer) {
                    $customer = CrmCustomer::create([
                        'workspace_id' => $order->workspace_id,
                        'name' => $order->client_name ?: 'Customer',
                        'email' => $order->client_email,
                        'phone' => $order->client_phone,
                        'billing_address' => $order->billing_address,
                        'shipping_address' => $order->shipping_address,
                        'tax_number' => $order->customer_trn,
                        'currency' => $order->invoice_currency ?: 'AED',
                    ]);
                }
            }

            $order->load(['orderItems', 'orderPayments']);
            $subtotal = $order->orderItems->isNotEmpty()
                ? (float) $order->orderItems->sum('line_total')
                : (float) $order->order_price * (float) $order->order_quantity;
            $total = $order->orderInvoiceTotal();
            $paid = min($total, $order->orderReceivedTotal());
            $items = $order->orderItems->map(function ($item) {
                return $item->product_name.' ('.$item->quantity.' × '.$item->unit_price.')';
            })->implode(', ');
            $data = [
                'workspace_id' => $order->workspace_id,
                'customer_id' => $customer->id,
                'order_number' => $order->order_invoice_number ?: 'CRM-'.$order->id,
                'order_date' => Carbon::parse($order->order_marked_at ?: $order->created_at ?: now())->toDateString(),
                'item_name' => $order->product_name ?: 'Order',
                'description' => $items ?: $order->product_name,
                'quantity' => 1,
                'unit' => 'Units',
                'unit_price' => round($subtotal, 2),
                'subtotal' => round($subtotal, 2),
                'discount_amount' => 0,
                'tax_amount' => round($total - $subtotal, 2),
                'shipping_cost' => 0,
                'total_amount' => $total,
                'paid_amount' => $paid,
                'balance_amount' => max(0, round($total - $paid, 2)),
                'currency' => $order->invoice_currency ?: 'AED',
                'payment_status' => $order->orderPaymentLabel(),
                'order_status' => $order->salesOrder ? 'Confirmed' : 'Completed',
                'notes' => $order->order_notes,
            ];

            if ($sale) {
                $sale->update($data);
            } else {
                $sale = CustomerSale::create($data + [
                    'crm_email_id' => $order->id,
                    'created_by' => $order->assigned_to,
                ]);
            }

            return $sale;
        });
    }
}
