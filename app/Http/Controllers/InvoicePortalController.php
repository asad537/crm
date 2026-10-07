<?php

namespace App\Http\Controllers;

use App\CrmManualOrder;
use App\CrmPortalAccount;
use App\Mail\OrderCcaMail;
use App\Services\ManualOrderCardCharge;
use App\Services\PaypalCheckoutService;
use App\Services\PaypalInvoiceService;
use App\Services\PortalAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Customer-facing invoice portal (/login): customers log in with the credentials emailed
 * to them, see their invoices, pay by card, and download invoice PDFs.
 */
class InvoicePortalController extends Controller
{
    private function account(Request $request): CrmPortalAccount
    {
        return $request->attributes->get('portal_account');
    }

    private function defaultBrand(): array
    {
        return [
            'name' => 'The Custom Boxes', 'site' => 'www.thecustomboxes.com', 'email' => 'support@thecustomboxes.com',
            'phones' => '1800-396-1840, 630-364-3944, 800-604-1874', 'logo' => 'thecustomboxes-logo.png', 'color' => '#376094',
        ];
    }

    public function loginPage(Request $request)
    {
        if ($request->session()->get('invoice_portal_account_id')) {
            return redirect()->route('invoice_portal.invoices');
        }
        return view('invoice_portal.login', ['brand' => $this->defaultBrand()]);
    }

    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $email = CrmPortalAccount::normalizeEmail($data['email']);

        $key = 'invoice-portal-login:' . $request->ip() . ':' . $email;
        if (RateLimiter::tooManyAttempts($key, 8)) {
            return back()->withInput(['email' => $email])->with('error', 'Too many attempts. Please wait a few minutes and try again.');
        }

        $account = CrmPortalAccount::where('email', $email)->orderByDesc('last_login_at')->get()
            ->first(fn ($a) => $a->checkPassword($data['password']));

        if (!$account) {
            RateLimiter::hit($key, 600);
            return back()->withInput(['email' => $email])->with('error', 'The email or password is incorrect.');
        }
        RateLimiter::clear($key);

        $account->last_login_at = now();
        $account->save();
        $request->session()->regenerate();
        $request->session()->put('invoice_portal_account_id', $account->id);
        return redirect()->route('invoice_portal.invoices');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('invoice_portal_account_id');
        $request->session()->regenerateToken();
        return redirect()->route('invoice_portal.login')->with('success', 'You have been logged out.');
    }

    /** "Forgot password": email a fresh password to the address if an account exists. */
    public function forgot(Request $request)
    {
        $data = $request->validate(['email' => 'required|email']);
        $email = CrmPortalAccount::normalizeEmail($data['email']);
        $key = 'invoice-portal-forgot:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->with('error', 'Too many requests. Please try again later.');
        }
        RateLimiter::hit($key, 900);

        $account = CrmPortalAccount::where('email', $email)->orderByDesc('updated_at')->first();
        if ($account) {
            $order = $account->orders()->latest()->first();
            if ($order) {
                try {
                    $plain = $account->resetPassword();
                    \Illuminate\Support\Facades\Mail::to($email)->send(new \App\Mail\PortalCredentialsMail($account, $order, $plain));
                    $account->credentials_sent_at = now();
                    $account->save();
                } catch (\Throwable $e) {
                    Log::warning('Portal password reset mail failed for ' . $email . ': ' . $e->getMessage());
                }
            }
        }
        // Same message either way so the form cannot be used to probe which emails exist.
        return back()->with('success', 'If an account exists for ' . $email . ', new login details have been emailed to it.');
    }

    public function invoices(Request $request)
    {
        $account = $this->account($request);
        $orders = $account->orders()->with('payments')->orderByDesc('created_at')->get();
        foreach ($orders->take(5) as $o) { $this->refreshPaypalInvoice($o); }
        return view('invoice_portal.index', [
            'account' => $account,
            'orders' => $orders,
            'brand' => $orders->first() ? OrderCcaMail::brandFor($orders->first()) : $this->defaultBrand(),
        ]);
    }

    private function findOrder(Request $request, $id): CrmManualOrder
    {
        $order = $this->account($request)->orders()->with('payments')->whereKey($id)->first();
        abort_unless($order, 404);
        return $order;
    }

    public function show(Request $request, $id)
    {
        $order = $this->findOrder($request, $id);
        $this->refreshPaypalInvoice($order);
        $paypal = new PaypalCheckoutService();
        $paypalReady = $paypal->isConfigured() && PaypalInvoiceService::supportsCurrency($order->currency ?: 'USD');
        return view('invoice_portal.show', [
            'account' => $this->account($request),
            'order' => $order,
            'brand' => OrderCcaMail::brandFor($order),
            'paypalClientId' => $paypalReady ? $paypal->clientId() : null,
            'paypalMode' => config('services.paypal.mode', 'sandbox'),
            'cardReady' => (new \App\Services\VaultGatewayService())->isConfigured(),
        ]);
    }

    /** If a PayPal invoice was emailed for this order and is still open, pull its latest status (throttled). */
    private function refreshPaypalInvoice(CrmManualOrder $order): void
    {
        if (!$order->paypal_invoice_id || $order->invoice_status === 'paid') return;
        if (in_array($order->paypal_invoice_status, ['PAID', 'MARKED_AS_PAID', 'CANCELLED', 'REFUNDED'], true)) return;
        $key = 'crm:paypal-sync:' . $order->id;
        if (\Illuminate\Support\Facades\Cache::has($key)) return;
        \Illuminate\Support\Facades\Cache::put($key, 1, now()->addMinute());
        try { $order->syncPaypal(); $order->load('payments'); }
        catch (\Throwable $e) { Log::warning('Portal PayPal sync failed for order #' . $order->id . ': ' . $e->getMessage()); }
    }

    /** PayPal buttons: create a PayPal order for the balance due (JSON). */
    public function paypalCreate(Request $request, $id)
    {
        $order = $this->findOrder($request, $id);
        $due = $order->balanceDue();
        if ($due <= 0.009) {
            return response()->json(['error' => 'This invoice is already paid.'], 422);
        }
        try {
            $paypalOrderId = (new PaypalCheckoutService())->createOrder($order, $due);
        } catch (\Throwable $e) {
            Log::error('Portal PayPal create failed for order #' . $order->id . ': ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 422);
        }
        return response()->json(['id' => $paypalOrderId]);
    }

    /** PayPal buttons: capture the approved PayPal order and record the payment (JSON). */
    public function paypalCapture(Request $request, $id)
    {
        $order = $this->findOrder($request, $id);
        $paypalOrderId = trim((string) $request->input('orderID'));
        if ($paypalOrderId === '' || !preg_match('/^[A-Z0-9]{5,40}$/i', $paypalOrderId)) {
            return response()->json(['error' => 'Invalid PayPal order.'], 422);
        }
        $svc = new PaypalCheckoutService();
        try {
            $pp = $svc->getOrder($paypalOrderId);
            if ((string) data_get($pp, 'purchase_units.0.custom_id') !== (string) $order->id) {
                return response()->json(['error' => 'PayPal order does not belong to this invoice.'], 422);
            }
            // Idempotent: already captured earlier (e.g. double click / refresh).
            $existingCapture = data_get($pp, 'purchase_units.0.payments.captures.0.id');
            if ($existingCapture && $order->payments()->where('gateway_transaction_id', $existingCapture)->exists()) {
                return response()->json(['ok' => true, 'redirect' => route('invoice_portal.show', $order->id)]);
            }
            $result = $svc->captureOrder($paypalOrderId);
        } catch (\Throwable $e) {
            Log::error('Portal PayPal capture failed for order #' . $order->id . ': ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if (!$result['completed']) {
            return response()->json(['error' => 'PayPal did not complete the payment (status ' . $result['status'] . '). Please try again or contact us.'], 422);
        }

        $amount = $result['amount'] > 0 ? $result['amount'] : $order->balanceDue();
        $order->payments()->create([
            'workspace_id' => $order->workspace_id,
            'amount' => min($amount, max($order->balanceDue(), 0.01)),
            'paid_at' => now()->toDateString(),
            'method' => 'PayPal',
            'reference' => $result['capture_id'],
            'note' => 'PayPal Checkout' . ($result['payer_email'] ? ' · ' . $result['payer_email'] : '') . ' · Customer Portal',
            'created_by' => null,
            'gateway' => 'paypal_checkout',
            'gateway_transaction_id' => $result['capture_id'],
            'gateway_response' => json_encode($result['raw']),
        ]);
        $order->refreshPaymentStatus();

        $request->session()->flash('success', 'Thank you! Your PayPal payment of ' . $result['currency'] . ' ' . number_format($amount, 2) . ' was received (transaction ' . $result['capture_id'] . ').');
        return response()->json(['ok' => true, 'redirect' => route('invoice_portal.show', $order->id)]);
    }

    public function pdf(Request $request, $id)
    {
        $order = $this->findOrder($request, $id);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('crm.orders.pdf', ['order' => $order])->setPaper('a4');
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Invoice-' . ($order->invoice_number ?: $order->id) . '.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    /** Pay the full remaining balance by card. */
    public function pay(Request $request, $id)
    {
        $order = $this->findOrder($request, $id);
        if ($order->balanceDue() <= 0.009) {
            return redirect()->route('invoice_portal.show', $order->id)->with('success', 'This invoice is already paid.');
        }

        $data = $request->validate([
            'card_name' => 'required|string|max:120',
            'card_number' => ['required', 'regex:/^[\d\s-]{12,23}$/'],
            'card_exp' => ['required', 'string', 'max:7'],
            'card_cvv' => ['required', 'regex:/^\d{3,4}$/'],
        ], [
            'card_number.regex' => 'Please enter a valid card number.',
            'card_cvv.regex' => 'Please enter the 3 or 4 digit security code.',
        ]);

        $number = preg_replace('/\D+/', '', $data['card_number']);
        $exp = ManualOrderCardCharge::normalizeExpiry($data['card_exp']);
        if (!ManualOrderCardCharge::luhn($number)) {
            return back()->with('error', 'The card number is not valid. Please check and try again.');
        }
        if (!$exp) {
            return back()->with('error', 'Please enter the expiry date as MM/YY.');
        }

        $key = 'invoice-portal-pay:' . $order->id;
        if (RateLimiter::tooManyAttempts($key, 6)) {
            return back()->with('error', 'Too many payment attempts. Please contact us to complete your payment.');
        }
        RateLimiter::hit($key, 900);

        try {
            $result = app(ManualOrderCardCharge::class)->charge($order, $order->balanceDue(), [
                'number' => $number, 'exp' => $exp, 'cvv' => $data['card_cvv'], 'name' => $data['card_name'],
            ], null, 'Customer Portal');
        } catch (\Throwable $e) {
            Log::error('Portal card payment failed for order #' . $order->id . ': ' . $e->getMessage());
            return back()->with('error', 'We could not process the payment: ' . $e->getMessage());
        }

        if (!$result['approved']) {
            return back()->with('error', 'Your card (' . $result['card_label'] . ') was declined: ' . $result['message'] . '. Please try another card or contact us.');
        }
        RateLimiter::clear($key);
        return redirect()->route('invoice_portal.show', $order->id)
            ->with('success', 'Thank you! Your payment was approved (' . $result['card_label'] . ', transaction ' . $result['transaction_id'] . ').');
    }
}
