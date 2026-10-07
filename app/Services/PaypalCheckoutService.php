<?php

namespace App\Services;

use App\CrmManualOrder;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * PayPal Checkout (Orders API v2) for the customer invoice portal: the customer pays in-page
 * with PayPal's buttons, and we capture the payment server-side.
 */
class PaypalCheckoutService
{
    private string $baseUrl;
    private string $clientId;
    private string $secret;

    public function __construct()
    {
        $cfg = (array) config('services.paypal', []);
        $this->baseUrl = ($cfg['mode'] ?? 'sandbox') === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
        $this->clientId = (string) ($cfg['client_id'] ?? '');
        $this->secret = (string) ($cfg['secret'] ?? '');
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->secret !== '';
    }

    public function clientId(): string
    {
        return $this->clientId;
    }

    /** Create a PayPal order for the amount due; returns the PayPal order id. */
    public function createOrder(CrmManualOrder $order, float $amount): string
    {
        $currency = strtoupper($order->currency ?: 'USD');
        if (!PaypalInvoiceService::supportsCurrency($currency)) {
            throw new RuntimeException("PayPal does not support {$currency} payments.");
        }
        $label = $order->invoice_number ? 'TCB-' . $order->invoice_number : 'ORD-' . $order->id;
        $res = Http::withToken($this->token())->acceptJson()->post($this->baseUrl . '/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => 'order-' . $order->id,
                'invoice_id' => mb_substr($label . '-' . time(), 0, 127),
                'custom_id' => (string) $order->id,
                'description' => mb_substr('Invoice ' . $label . ' - ' . $this->brandName($order), 0, 127),
                'amount' => ['currency_code' => $currency, 'value' => number_format($amount, 2, '.', '')],
            ]],
            'application_context' => [
                'brand_name' => $this->brandName($order),
                'shipping_preference' => 'NO_SHIPPING',
                'user_action' => 'PAY_NOW',
            ],
        ]);
        if (!$res->successful() || !$res->json('id')) {
            throw new RuntimeException('PayPal could not start the payment: ' . $this->error($res));
        }
        return (string) $res->json('id');
    }

    /**
     * Capture an approved PayPal order.
     *
     * @return array{completed:bool,capture_id:?string,amount:float,currency:string,payer_email:?string,status:string,raw:array}
     */
    public function captureOrder(string $paypalOrderId): array
    {
        $res = Http::withToken($this->token())->acceptJson()
            ->withHeaders(['Prefer' => 'return=representation'])
            ->post($this->baseUrl . '/v2/checkout/orders/' . $paypalOrderId . '/capture', (object) []);
        if (!$res->successful()) {
            throw new RuntimeException('PayPal could not complete the payment: ' . $this->error($res));
        }
        $j = (array) $res->json();
        $cap = data_get($j, 'purchase_units.0.payments.captures.0', []);
        $status = strtoupper((string) ($cap['status'] ?? $j['status'] ?? ''));
        return [
            'completed' => $status === 'COMPLETED',
            'capture_id' => $cap['id'] ?? null,
            'amount' => (float) data_get($cap, 'amount.value', 0),
            'currency' => strtoupper((string) data_get($cap, 'amount.currency_code', '')),
            'payer_email' => data_get($j, 'payer.email_address'),
            'status' => $status,
            'raw' => ['id' => $j['id'] ?? null, 'status' => $j['status'] ?? null, 'capture' => $cap, 'payer' => $j['payer'] ?? null],
        ];
    }

    /** Look up a PayPal order (used to confirm custom_id matches before capturing). */
    public function getOrder(string $paypalOrderId): array
    {
        $res = Http::withToken($this->token())->acceptJson()->get($this->baseUrl . '/v2/checkout/orders/' . $paypalOrderId);
        if (!$res->successful()) {
            throw new RuntimeException('PayPal order lookup failed: ' . $this->error($res));
        }
        return (array) $res->json();
    }

    private function brandName(CrmManualOrder $order): string
    {
        return str_contains(strtolower((string) $order->website), 'myboxprinting') ? 'My Box Printing' : 'The Custom Boxes';
    }

    private function token(): string
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('PayPal is not configured.');
        }
        $res = Http::asForm()->withBasicAuth($this->clientId, $this->secret)
            ->post($this->baseUrl . '/v1/oauth2/token', ['grant_type' => 'client_credentials']);
        if (!$res->successful() || !$res->json('access_token')) {
            throw new RuntimeException('PayPal login failed.');
        }
        return (string) $res->json('access_token');
    }

    private function error($response): string
    {
        $j = $response->json();
        if (is_array($j)) {
            $d = data_get($j, 'details.0.description') ?: data_get($j, 'details.0.issue');
            $m = $j['message'] ?? $j['error_description'] ?? null;
            $out = trim(implode(' - ', array_filter([$m, $d])));
            if ($out !== '') return $out;
        }
        return 'HTTP ' . $response->status();
    }
}
