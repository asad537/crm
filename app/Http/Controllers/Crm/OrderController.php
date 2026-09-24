<?php

namespace App\Http\Controllers\Crm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CrmManualOrder;
use App\CrmEmail;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /** Orders are handled by the sales side + admin. */
    private function guard()
    {
        $user = Auth::guard('crm')->user();
        if (!$user || (!$user->isAdmin() && !$user->isSalesManager() && !$user->isSales())) {
            abort(403);
        }
        return $user;
    }

    public function index()
    {
        $user = $this->guard();
        $query = CrmManualOrder::with('creator')->latest();
        if ($user->isSales()) {
            $query->where('created_by', $user->id);
        }
        return view('crm.orders.manual_index', ['orders' => $query->get()]);
    }

    public function create(Request $request)
    {
        $user = $this->guard();

        // Optionally prefill from a source inquiry.
        $inquiry = null;
        if ($request->filled('inquiry')) {
            $inquiry = CrmEmail::find($request->input('inquiry'));
        }

        return view('crm.orders.form', [
            'order' => null,
            'inquiry' => $inquiry,
            'prefill' => $this->prefillFromInquiry($inquiry, $user),
        ]);
    }

    public function edit($id)
    {
        $this->guard();
        $order = CrmManualOrder::findOrFail($id);
        return view('crm.orders.form', [
            'order' => $order,
            'inquiry' => null,
            'prefill' => [],
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->guard();
        $order = new CrmManualOrder();
        $this->fillFromRequest($order, $request, $user);
        $order->created_by = $user->id;
        $order->save();

        if ($request->input('_action') === 'save_send') {
            return redirect()->route('crm.orders.manual.index')->with('success', 'Order saved. (Email sending can be wired to your mail setup.)');
        }
        return redirect()->route('crm.orders.manual.index')->with('success', 'Order created successfully.');
    }

    public function update(Request $request, $id)
    {
        $user = $this->guard();
        $order = CrmManualOrder::findOrFail($id);
        $this->fillFromRequest($order, $request, $user);
        $order->save();

        return redirect()->route('crm.orders.manual.index')->with('success', 'Order updated.');
    }

    public function destroy($id)
    {
        $this->guard();
        CrmManualOrder::findOrFail($id)->delete();
        return redirect()->route('crm.orders.manual.index')->with('success', 'Order deleted.');
    }

    public function pdf($id)
    {
        $this->guard();
        $order = CrmManualOrder::findOrFail($id);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('crm.orders.pdf', ['order' => $order])->setPaper('a4');
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="order-' . ($order->invoice_number ?: $order->id) . '.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    private function prefillFromInquiry($inquiry, $user)
    {
        if (!$inquiry) {
            return ['sales_person' => $user->name, 'user_name' => $user->name];
        }
        return [
            'user_name' => $user->name,
            'sales_person' => $user->name,
            'enquiry_number' => $inquiry->workflow_number ?: $inquiry->id,
            'currency' => $inquiry->invoice_currency ?: 'USD',
            'billing_name' => $inquiry->client_name,
            'billing_phone' => $inquiry->client_phone,
            'crm_email_id' => $inquiry->id,
            'box_style' => $inquiry->product_name,
            'finishing' => is_array($inquiry->custom_specs['Finishing Options'] ?? null)
                ? implode(', ', $inquiry->custom_specs['Finishing Options'])
                : '',
        ];
    }

    private function fillFromRequest(CrmManualOrder $order, Request $request, $user)
    {
        $data = $request->validate([
            'user_name' => 'nullable|string|max:190',
            'enquiry_number' => 'nullable|string|max:120',
            'invoice_status' => 'nullable|in:paid,unpaid',
            'invoice_number' => 'nullable|string|max:120',
            'website' => 'nullable|string|max:190',
            'currency' => 'nullable|string|max:12',
            'invoice_date' => 'nullable|date',
            'customer_id' => 'nullable|string|max:120',
            'payment_term' => 'nullable|string|max:190',
            'sales_person' => 'nullable|string|max:190',
            'shipping_method' => 'nullable|string|max:120',
            'shipping_term' => 'nullable|string|max:120',
            'payment_term_via' => 'nullable|string|max:120',
            'additional_info' => 'nullable|string|max:5000',
            'crm_email_id' => 'nullable|integer',
            'package_price' => 'nullable|numeric',
            'rush_charges' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'billing' => 'nullable|array',
            'shipping' => 'nullable|array',
            'items' => 'nullable|array',
        ]);

        // Normalise line items and compute totals server-side.
        $items = [];
        $subTotal = 0;
        foreach ((array) $request->input('items', []) as $row) {
            $qty = (float) ($row['qty'] ?? 0);
            $unitPrice = (float) ($row['unit_price'] ?? 0);
            $otherCharges = (float) ($row['other_charges'] ?? 0);
            $lineTotal = ($qty * $unitPrice) + $otherCharges;
            // Skip completely empty rows.
            if (empty($row['box_style']) && $qty == 0 && $unitPrice == 0 && empty($row['stock'])) continue;
            $items[] = [
                'box_style' => $row['box_style'] ?? '',
                'stock' => $row['stock'] ?? '',
                'color' => $row['color'] ?? '',
                'length' => $row['length'] ?? '',
                'width' => $row['width'] ?? '',
                'height' => $row['height'] ?? '',
                'unit' => $row['unit'] ?? '',
                'finishing' => $row['finishing'] ?? '',
                'additional_info' => $row['additional_info'] ?? '',
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'other_charges' => $otherCharges,
                'line_total' => round($lineTotal, 2),
            ];
            $subTotal += $lineTotal;
        }

        $packagePrice = (float) ($data['package_price'] ?? 0);
        $rushCharges = (float) ($data['rush_charges'] ?? 0);
        $discount = (float) ($data['discount'] ?? 0);
        $total = $subTotal + $packagePrice + $rushCharges - $discount;

        $order->fill([
            'user_name' => $data['user_name'] ?? null,
            'enquiry_number' => $data['enquiry_number'] ?? null,
            'invoice_status' => $data['invoice_status'] ?? 'unpaid',
            'invoice_number' => $data['invoice_number'] ?? null,
            'website' => $data['website'] ?? null,
            'currency' => $data['currency'] ?? 'USD',
            'invoice_date' => $data['invoice_date'] ?? null,
            'customer_id' => $data['customer_id'] ?? null,
            'payment_term' => $data['payment_term'] ?? null,
            'sales_person' => $data['sales_person'] ?? null,
            'shipping_method' => $data['shipping_method'] ?? null,
            'shipping_term' => $data['shipping_term'] ?? null,
            'payment_term_via' => $data['payment_term_via'] ?? null,
            'additional_info' => $data['additional_info'] ?? null,
            'crm_email_id' => $data['crm_email_id'] ?? $order->crm_email_id,
            'billing' => $request->input('billing', []),
            'shipping' => $request->input('shipping', []),
            'line_items' => $items,
            'sub_total' => round($subTotal, 2),
            'package_price' => round($packagePrice, 2),
            'rush_charges' => round($rushCharges, 2),
            'discount' => round($discount, 2),
            'total' => round($total, 2),
        ]);
    }
}
