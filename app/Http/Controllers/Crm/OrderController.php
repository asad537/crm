<?php

namespace App\Http\Controllers\Crm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CrmManualOrder;
use App\CrmManualOrderPayment;
use App\CrmEmail;
use App\CrmCustomer;
use App\Services\PaypalInvoiceService;
use App\Services\VaultGatewayService;
use App\Services\ManualOrderCardCharge;
use App\Services\PortalAccountService;
use App\Mail\OrderCcaMail;
use App\Mail\ManualOrderInvoiceMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

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

    public function index(Request $request)
    {
        $user = $this->guard();

        // Base scope: sales agents only see their own orders. (Workspace scope is global.)
        $base = CrmManualOrder::query();
        if ($user->isSales()) {
            $base->where('created_by', $user->id);
        }

        // Customer dropdown options — built from existing orders (billing name, else customer id).
        $customers = (clone $base)->with('customer')->get()
            ->map(function ($o) {
                return trim((string) (optional($o->customer)->name ?: (data_get($o->billing, 'name') ?: $o->customer_id)));
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $query = (clone $base)->with(['creator', 'customer', 'payments'])->latest();

        // Search: invoice #, customer (billing name / customer id / user), or order / enquiry ID.
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('customer_id', 'like', "%{$search}%")
                  ->orWhere('enquiry_number', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('billing', 'like', "%{$search}%")
                  ->orWhere('id', $search);
            });
        }

        // Status filter (paid / unpaid).
        if ($request->filled('status')) {
            $query->where('invoice_status', $request->input('status'));
        }

        // Customer filter (match billing name or customer id).
        if ($request->filled('customer')) {
            $customer = $request->input('customer');
            $query->where(function ($q) use ($customer) {
                $q->where('customer_id', $customer)
                  ->orWhere('billing', 'like', "%{$customer}%");
            });
        }

        // Date range on invoice date (fall back to created_at when invoice date is missing).
        if ($request->filled('start_date')) {
            $query->whereRaw('COALESCE(invoice_date, DATE(created_at)) >= ?', [Carbon::parse($request->input('start_date'))->toDateString()]);
        }
        if ($request->filled('end_date')) {
            $query->whereRaw('COALESCE(invoice_date, DATE(created_at)) <= ?', [Carbon::parse($request->input('end_date'))->toDateString()]);
        }

        $orders = $query->get();
        $this->autoSyncPaypal($orders);

        return view('crm.orders.manual_index', [
            'orders' => $orders,
            'customers' => $customers,
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
                'customer' => $request->input('customer', ''),
                'start_date' => $request->input('start_date', ''),
                'end_date' => $request->input('end_date', ''),
            ],
        ]);
    }

    /**
     * Quietly refresh PayPal status for unpaid orders that have an open PayPal invoice.
     * Throttled per order (every 3 minutes) and capped per page load so the list stays fast.
     */
    private function autoSyncPaypal($orders): void
    {
        $svc = new PaypalInvoiceService();
        if (!$svc->isConfigured()) return;
        $done = 0;
        foreach ($orders as $order) {
            if ($done >= 8) break;
            if (!$order->paypal_invoice_id || $order->invoice_status === 'paid') continue;
            if (in_array($order->paypal_invoice_status, ['PAID', 'MARKED_AS_PAID', 'CANCELLED', 'REFUNDED'], true)) continue;
            $key = 'crm:paypal-sync:' . $order->id;
            if (\Illuminate\Support\Facades\Cache::has($key)) continue;
            \Illuminate\Support\Facades\Cache::put($key, 1, now()->addMinutes(3));
            try { $order->syncPaypal($svc); $done++; }
            catch (\Throwable $e) { Log::warning('PayPal auto-sync failed for order #' . $order->id . ': ' . $e->getMessage()); }
        }
    }

    /** Manual "Sync PayPal Status" action. */
    public function syncPaypal($id)
    {
        $this->guard();
        $order = CrmManualOrder::findOrFail($id);
        if (!$order->paypal_invoice_id) {
            return redirect()->route('crm.orders.manual.index')->with('error', 'No PayPal request has been sent for this order yet.');
        }
        try {
            $status = $order->syncPaypal();
            \Illuminate\Support\Facades\Cache::put('crm:paypal-sync:' . $order->id, 1, now()->addMinutes(3));
        } catch (\Throwable $e) {
            return redirect()->route('crm.orders.manual.index')->with('error', 'PayPal status check failed: ' . $e->getMessage());
        }
        $label = $order->invoice_number ? 'TCB-' . $order->invoice_number : '#' . $order->id;
        $cur = strtoupper($order->currency ?: 'USD');
        $msg = "PayPal invoice for {$label} is {$status}.";
        $msg .= $order->invoice_status === 'paid' ? ' Order is now fully PAID.' : " Balance due: {$cur} " . number_format($order->balanceDue(), 2) . '.';
        return redirect()->route('crm.orders.manual.index')->with('success', $msg);
    }

    /**
     * PayPal webhook (INVOICING.INVOICE.PAID etc). We never trust the payload itself:
     * we only take the invoice id and re-fetch the invoice from PayPal's API.
     */
    public function paypalWebhook(Request $request)
    {
        $invoiceId = (string) ($request->input('resource.invoice.id') ?: $request->input('resource.id') ?: '');
        if ($invoiceId === '' || !preg_match('/^INV2-[A-Z0-9-]+$/', $invoiceId)) {
            return response()->json(['ok' => false, 'reason' => 'no invoice id'], 200);
        }
        $order = CrmManualOrder::withoutGlobalScopes()->where('paypal_invoice_id', $invoiceId)->first();
        if (!$order) {
            return response()->json(['ok' => false, 'reason' => 'unknown invoice'], 200);
        }
        try {
            $status = $order->syncPaypal();
        } catch (\Throwable $e) {
            Log::warning('PayPal webhook sync failed for ' . $invoiceId . ': ' . $e->getMessage());
            return response()->json(['ok' => false], 200);
        }
        return response()->json(['ok' => true, 'status' => $status, 'order_status' => $order->invoice_status]);
    }

    public function create(Request $request)
    {
        $user = $this->guard();

        // Optionally prefill from a source inquiry.
        $inquiry = null;
        if ($request->filled('inquiry')) {
            $inquiry = CrmEmail::find($request->input('inquiry'));
        }

        $prefill = $this->prefillFromInquiry($inquiry, $user);

        // If the inquiry's client already exists in the Customers tab, preselect them.
        if ($inquiry && $inquiry->client_email) {
            $match = CrmCustomer::whereRaw('LOWER(email) = ?', [strtolower(trim($inquiry->client_email))])->first();
            if ($match) $prefill['crm_customer_id'] = $match->id;
        }

        return view('crm.orders.form', [
            'order' => null,
            'inquiry' => $inquiry,
            'prefill' => $prefill,
            'customers' => $this->customerOptions(),
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
            'customers' => $this->customerOptions(),
        ]);
    }

    /** Customers-tab records for the "Select Customer" dropdown (current workspace only). */
    private function customerOptions()
    {
        return CrmCustomer::orderBy('name')
            ->get(['id', 'name', 'company_name', 'phone', 'email', 'country', 'billing_address', 'shipping_address', 'currency']);
    }

    public function store(Request $request)
    {
        $user = $this->guard();
        $order = new CrmManualOrder();
        $this->fillFromRequest($order, $request, $user);
        $order->created_by = $user->id;
        $order->save();

        // Give the customer a portal login (emailed) so they can view and pay this invoice online.
        $portalNote = '';
        [$account, $sentTo, $portalError] = app(PortalAccountService::class)->ensureForOrder($order, true, $user);
        if ($sentTo) {
            $portalNote = " Portal login details emailed to {$sentTo}.";
        } elseif ($portalError) {
            Log::warning('Portal login not sent for manual order #' . $order->id . ': ' . $portalError);
            $portalNote = ' (Portal login not sent: ' . $portalError . ')';
        }

        if ($request->input('_action') === 'save_send') {
            return $this->deliverInvoice($order, $user, null, 'Order created.' . $portalNote . ' ');
        }
        return redirect()->route('crm.orders.manual.index')->with('success', 'Order created successfully.' . $portalNote);
    }

    /** Actions -> Send Login Details: (re)send the customer's portal credentials with a fresh password. */
    public function sendPortalLogin(Request $request, $id)
    {
        $user = $this->guard();
        $order = CrmManualOrder::findOrFail($id);
        if ($user->isSales() && (int) $order->created_by !== (int) $user->id) {
            abort(403);
        }
        $email = trim((string) $request->input('email'));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $billing = (array) ($order->billing ?: []);
            if (($billing['email'] ?? '') !== $email) { $billing['email'] = $email; $order->billing = $billing; $order->save(); }
        }
        [$account, $sentTo, $error] = app(PortalAccountService::class)->ensureForOrder($order, true, $user, true);
        if ($error) {
            return redirect()->route('crm.orders.manual.index')->with('error', $error);
        }
        return redirect()->route('crm.orders.manual.index')->with('success', "Portal login details (new password) sent to {$sentTo}. Login page: " . route('invoice_portal.login'));
    }

    public function update(Request $request, $id)
    {
        $user = $this->guard();
        $order = CrmManualOrder::findOrFail($id);
        $this->fillFromRequest($order, $request, $user);
        $order->save();

        if ($request->input('_action') === 'save_send') {
            return $this->deliverInvoice($order, $user, null, 'Order updated. ');
        }
        return redirect()->route('crm.orders.manual.index')->with('success', 'Order updated.');
    }

    /** Email the invoice PDF to the customer (Actions -> Send Invoice). */
    public function sendInvoice(Request $request, $id)
    {
        $user = $this->guard();
        $order = CrmManualOrder::findOrFail($id);
        if ($user->isSales() && (int) $order->created_by !== (int) $user->id) {
            abort(403);
        }
        return $this->deliverInvoice($order, $user, trim((string) $request->input('email')) ?: null);
    }

    /** Shared invoice-email flow for the Send Invoice action and "Save & Send Email". */
    private function deliverInvoice(CrmManualOrder $order, $user, ?string $emailOverride = null, string $prefix = '')
    {
        $email = $emailOverride ?: $order->customerEmail();
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->route('crm.orders.manual.index')
                ->with('error', $prefix . 'No valid customer email on this order. Open Edit and fill in the Billing Email first.');
        }

        try {
            $adminEmails = \App\CrmUser::inWorkspace(null, ['admin'])->pluck('email')->toArray();
            $cc = array_values(array_unique(array_filter(array_merge($adminEmails, ['support@myboxprinting.com']), function ($e) use ($email) {
                return $e && strcasecmp($e, $email) !== 0;
            })));
            Mail::to($email)->cc($cc)->send(new ManualOrderInvoiceMail($order, $user));
        } catch (\Throwable $e) {
            Log::error('Invoice send failed for manual order #' . $order->id . ': ' . $e->getMessage());
            return redirect()->route('crm.orders.manual.index')->with('error', $prefix . 'Failed to send invoice: ' . $e->getMessage());
        }

        $billing = (array) ($order->billing ?: []);
        if (($billing['email'] ?? '') !== $email) {
            $billing['email'] = $email;
            $order->billing = $billing;
        }
        $order->invoice_sent_to = $email;
        $order->invoice_sent_at = now();
        $order->invoice_send_count = (int) $order->invoice_send_count + 1;
        $order->save();

        $label = $order->invoice_number ? 'TCB-' . $order->invoice_number : '#' . $order->id;
        return redirect()->route('crm.orders.manual.index')->with('success', $prefix . "Invoice {$label} sent to {$email}.");
    }

    public function destroy($id)
    {
        $this->guard();
        CrmManualOrder::findOrFail($id)->delete();
        return redirect()->route('crm.orders.manual.index')->with('success', 'Order deleted.');
    }

    /**
     * Create a PayPal invoice for the order and have PayPal email the customer a payment request.
     */
    public function sendPaypalRequest(Request $request, $id)
    {
        $user = $this->guard();
        $order = CrmManualOrder::findOrFail($id);

        // Sales agents can only act on their own orders.
        if ($user->isSales() && (int) $order->created_by !== (int) $user->id) {
            abort(403);
        }

        $email = trim((string) $request->input('email')) ?: $order->customerEmail();
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->route('crm.orders.manual.index')
                ->with('error', 'No valid customer email on this order. Open Edit and fill in "Billing Email" first.');
        }

        $paypal = new PaypalInvoiceService();
        if (!$paypal->isConfigured()) {
            return redirect()->route('crm.orders.manual.index')
                ->with('error', 'PayPal is not connected yet. Add PAYPAL_CLIENT_ID and PAYPAL_SECRET to the .env file.');
        }

        try {
            $result = $paypal->sendRequest($order, $email);
        } catch (\Throwable $e) {
            Log::error('PayPal request failed for manual order #' . $order->id . ': ' . $e->getMessage());
            return redirect()->route('crm.orders.manual.index')
                ->with('error', 'PayPal request failed: ' . $e->getMessage());
        }

        // Remember the email used so it is prefilled next time (and shows on the order form).
        $billing = (array) ($order->billing ?: []);
        if (($billing['email'] ?? '') !== $email) {
            $billing['email'] = $email;
            $order->billing = $billing;
        }

        $order->paypal_invoice_id = $result['id'];
        $order->paypal_invoice_number = $result['number'];
        $order->paypal_invoice_status = $result['status'];
        $order->paypal_invoice_url = $result['url'];
        $order->paypal_sent_to = $email;
        $order->paypal_sent_at = now();
        $order->paypal_send_count = (int) $order->paypal_send_count + 1;
        $order->save();

        $label = $order->invoice_number ? 'TCB-' . $order->invoice_number : '#' . $order->id;
        $amount = strtoupper($order->currency ?: 'USD') . ' ' . number_format((float) $order->total, 2);
        $modeNote = $paypal->mode() === 'sandbox' ? ' [PayPal sandbox]' : '';

        return redirect()->route('crm.orders.manual.index')
            ->with('success', "PayPal payment request for {$amount} (invoice {$label}) sent to {$email}.{$modeNote}");
    }

    /**
     * Email the customer a Credit Card Authorization (CCA) form PDF for this order.
     */
    public function sendCca(Request $request, $id)
    {
        $user = $this->guard();
        $order = CrmManualOrder::findOrFail($id);
        if ($user->isSales() && (int) $order->created_by !== (int) $user->id) {
            abort(403);
        }

        $email = trim((string) $request->input('email')) ?: $order->customerEmail();
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return redirect()->route('crm.orders.manual.index')
                ->with('error', 'No valid customer email on this order. Open Edit and fill in the Billing Email first.');
        }

        try {
            $adminEmails = \App\CrmUser::inWorkspace(null, ['admin'])->pluck('email')->toArray();
            $cc = array_values(array_unique(array_filter(array_merge($adminEmails, ['support@myboxprinting.com']), function ($e) use ($email) {
                return $e && strcasecmp($e, $email) !== 0;
            })));
            Mail::to($email)->cc($cc)->send(new OrderCcaMail($order, $user));
        } catch (\Throwable $e) {
            Log::error('CCA send failed for manual order #' . $order->id . ': ' . $e->getMessage());
            return redirect()->route('crm.orders.manual.index')->with('error', 'Failed to send CCA form: ' . $e->getMessage());
        }

        $billing = (array) ($order->billing ?: []);
        if (($billing['email'] ?? '') !== $email) {
            $billing['email'] = $email;
            $order->billing = $billing;
        }
        $order->cca_sent_to = $email;
        $order->cca_sent_at = now();
        $order->cca_send_count = (int) $order->cca_send_count + 1;
        $order->save();

        $label = $order->invoice_number ? 'TCB-' . $order->invoice_number : '#' . $order->id;
        return redirect()->route('crm.orders.manual.index')
            ->with('success', "Credit Card Authorization form for invoice {$label} sent to {$email}.");
    }

    /** Record a payment against the order; marks it paid once the total is covered. */
    public function storePayment(Request $request, $id)
    {
        $user = $this->guard();
        $order = CrmManualOrder::findOrFail($id);
        if ($user->isSales() && (int) $order->created_by !== (int) $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|gt:0',
            'paid_at' => 'required|date|before_or_equal:today',
            'method' => 'nullable|string|max:100',
            'reference' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:2000',
        ]);

        $amount = round((float) $data['amount'], 2);
        $balance = $order->balanceDue();
        if ($amount > $balance + 0.009) {
            return redirect()->route('crm.orders.manual.index')
                ->with('error', 'Payment of ' . number_format($amount, 2) . ' exceeds the remaining balance of ' . number_format($balance, 2) . '.');
        }

        $order->payments()->create([
            'workspace_id' => $order->workspace_id,
            'amount' => $amount,
            'paid_at' => $data['paid_at'],
            'method' => $data['method'] ?? null,
            'reference' => $data['reference'] ?? null,
            'note' => $data['note'] ?? null,
            'created_by' => $user->id,
        ]);
        $order->refreshPaymentStatus();

        $label = $order->invoice_number ? 'TCB-' . $order->invoice_number : '#' . $order->id;
        $cur = strtoupper($order->currency ?: 'USD');
        $msg = "Payment of {$cur} " . number_format($amount, 2) . " recorded for {$label}.";
        $msg .= $order->invoice_status === 'paid' ? ' Order is now fully PAID.' : " Balance due: {$cur} " . number_format($order->balanceDue(), 2) . '.';
        return redirect()->route('crm.orders.manual.index')->with('success', $msg);
    }

    /** Charge the customer's card through the Vault gateway and record the payment. */
    public function chargeCard(Request $request, $id)
    {
        $user = $this->guard();
        $order = CrmManualOrder::findOrFail($id);
        if ($user->isSales() && (int) $order->created_by !== (int) $user->id) {
            abort(403);
        }

        $data = $request->validate([
            'amount' => 'required|numeric|gt:0',
            'card_name' => 'nullable|string|max:120',
            'card_number' => ['required', 'regex:/^[\d\s-]{12,23}$/'],
            'card_exp' => ['required', 'regex:/^\s*(0[1-9]|1[0-2])\s*\/?\s*(\d{2}|\d{4})\s*$/'],
            'card_cvv' => ['required', 'regex:/^\d{3,4}$/'],
        ], [
            'card_number.regex' => 'Enter a valid card number.',
            'card_exp.regex' => 'Enter the expiry as MM/YY.',
            'card_cvv.regex' => 'Enter the 3 or 4 digit CVV.',
        ]);

        $number = preg_replace('/\D+/', '', $data['card_number']);
        if (!ManualOrderCardCharge::luhn($number)) {
            return redirect()->route('crm.orders.manual.index')->with('error', 'The card number is not valid. Please check and try again.');
        }
        $exp = ManualOrderCardCharge::normalizeExpiry($data['card_exp']);

        $label = $order->invoice_number ? 'TCB-' . $order->invoice_number : '#' . $order->id;
        $cur = strtoupper($order->currency ?: 'USD');
        $amount = round((float) $data['amount'], 2);

        try {
            $result = app(ManualOrderCardCharge::class)->charge($order, $amount, [
                'number' => $number, 'exp' => $exp, 'cvv' => $data['card_cvv'], 'name' => $data['card_name'] ?? null,
            ], $user->id, 'via Vault (CRM)');
        } catch (\Throwable $e) {
            Log::error('Vault charge failed for manual order #' . $order->id . ': ' . $e->getMessage());
            return redirect()->route('crm.orders.manual.index')->with('error', 'Card charge failed: ' . $e->getMessage());
        }

        if (!$result['approved']) {
            return redirect()->route('crm.orders.manual.index')
                ->with('error', "Card {$result['card_label']} was NOT charged for {$label}: " . $result['message']);
        }

        $msg = "Card {$result['card_label']} charged {$cur} " . number_format($amount, 2) . " for {$label} (Txn {$result['transaction_id']}).";
        $msg .= $order->invoice_status === 'paid' ? ' Order is now fully PAID.' : " Balance due: {$cur} " . number_format($order->balanceDue(), 2) . '.';
        return redirect()->route('crm.orders.manual.index')->with('success', $msg);
    }

    /** Remove a recorded payment (admin / sales manager / accounts). */
    public function destroyPayment($id, $paymentId)
    {
        $user = $this->guard();
        if (!$user->isAdmin() && !$user->isSalesManager() && !(method_exists($user, 'isAccounts') && $user->isAccounts())) {
            abort(403);
        }
        $order = CrmManualOrder::findOrFail($id);
        $payment = CrmManualOrderPayment::where('manual_order_id', $order->id)->findOrFail($paymentId);
        $payment->delete();
        $order->refreshPaymentStatus();
        return redirect()->route('crm.orders.manual.index')->with('success', 'Payment removed.');
    }

    /** Preview the CCA form PDF inline. */
    public function ccaPdf($id)
    {
        $this->guard();
        $order = CrmManualOrder::findOrFail($id);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('crm.orders.cca_pdf', ['order' => $order, 'brand' => OrderCcaMail::brandFor($order)])->setPaper('a4');
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="cca-' . ($order->invoice_number ?: $order->id) . '.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
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
            'billing_email' => $inquiry->client_email,
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
            'crm_customer_id' => 'nullable|integer|exists:crm_customers,id',
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
            'crm_customer_id' => $data['crm_customer_id'] ?? null,
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
