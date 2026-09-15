<?php

namespace App\Support;

use App\VendorPurchase;
use Illuminate\Support\Carbon;

class VendorPurchaseReminders
{
    /**
     * Unpaid/partial vendor invoices whose due date is within the next 12 HOURS (or already
     * overdue), for the current workspace. Powers the header bell and its auto-refresh endpoint.
     * due_date is date-only, so "12h before" effectively means from midday the day before.
     */
    public static function current(): array
    {
        $today = Carbon::today();
        $tomorrow = $today->copy()->addDay();
        $threshold = Carbon::now()->addHours(12); // start alerting 12h before the due date

        try {
            $reminders = VendorPurchase::query()
                ->whereNotNull('due_date')
                ->whereIn('payment_status', ['Unpaid', 'Partial'])
                ->where('balance_amount', '>', 0)
                ->where('due_date', '<=', $threshold)
                ->orderBy('due_date')
                ->limit(15)
                ->get(['id', 'vendor_name', 'due_date', 'balance_amount', 'invoice_number', 'currency', 'payment_status']);
        } catch (\Throwable $e) {
            $reminders = collect();
        }

        $items = [];
        $signatures = [];
        $overdue = 0;
        foreach ($reminders as $r) {
            $d = $r->due_date;
            if (!$d) {
                $bucket = 'soon';
                $label = 'Due soon';
            } elseif ($d->lt($today)) {
                $bucket = 'od';
                $label = 'Overdue ' . $today->diffInDays($d) . 'd';
                $overdue++;
            } elseif ($d->isSameDay($today)) {
                $bucket = 'today';
                $label = 'Due today';
            } else {
                $bucket = 'soon';
                $label = 'Due ' . ($d->isSameDay($tomorrow) ? 'tomorrow' : $d->format('d M'));
            }
            $signatures[] = $r->id . ':' . $bucket;
            $items[] = [
                'id' => $r->id,
                'vendor' => $r->vendor_name ?: 'Vendor',
                'invoice' => $r->invoice_number,
                'currency' => $r->currency,
                'balance' => number_format((float) $r->balance_amount, 2),
                'due' => optional($d)->format('d M'),
                'label' => $label,
                'cls' => $bucket,
                'url' => route('crm.vendor_purchases.edit', $r->id),
            ];
        }

        return [
            'count' => count($items),
            'overdue' => $overdue,
            'signatures' => $signatures,
            'items' => $items,
        ];
    }
}
