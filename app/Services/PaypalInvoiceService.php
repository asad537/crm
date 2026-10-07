<?php

namespace App\Services;

use App\CrmManualOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Creates and sends a PayPal invoice (Invoicing API v2) for a TCB manual order.
 * PayPal emails the customer a "pay now" request from PayPal's side.
 */
class PaypalInvoiceService
{
    private string $baseUrl;
    private string $clientId;
    private string $secret;
    private string $businessName;
    private ?string $businessEmail;

    public function __construct()
    {
        $cfg = (array) config('services.paypal', []);
        $this->baseUrl = ($cfg['mode'] ?? 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
        $this->clientId = (string) ($cfg['client_id'] ?? '');
        $this->secret = (string) ($cfg['secret'] ?? '');
        $this->businessName = (string) ($cfg['business_name'] ?: 'My Box Printing');
        $this->businessEmail = $cfg['business_email'] ?: null;
    }

    /** Currencies PayPal accepts for invoices (AED, PKR, SAR, QAR are NOT supported). */
    public const SUPPORTED_CURRENCIES = [
        'AUD', 'BRL', 'CAD', 'CNY', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN',
        'TWD', 'NZD', 'NOK', 'PHP', 'PLN', 'GBP', 'SGD', 'SEK', 'CHF', 'THB', 'USD',
    ];

    public static function supportsCurrency(?string $code): bool
    {
        return in_array(strtoupper((string) $code), self::SUPPORTED_CURRENCIES, true);
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->secret !== '';
    }

    public function mode(): string
    {
        return str_contains($this->baseUrl, 'sandbox') ? 'sandbox' : 'live';
    }

    /**
     * Create a PayPal invoice for the order and send it to the customer.
     *
     * @return array{id:string,number:string,status:string,url:?string,email:string}
     */
    public function sendRequest(CrmManualOrder $order, string $email): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('PayPal is not configured. Set PAYPAL_CLIENT_ID and PAYPAL_SECRET in .env.');
        }
        if ((float) $order->total <= 0) {
            throw new RuntimeException('Order total must be greater than zero to request a PayPal payment.');
        }
        if (!self::supportsCurrency($order->currency)) {
            throw new RuntimeException('PayPal does not support ' . strtoupper((string) $order->currency) . ' invoices. Change the order currency to USD, CAD, GBP, EUR or AUD and try again.');
        }

        $token = $this->accessToken();
        $payload = $this->buildInvoice($order, $email);

        // 1) Create draft invoice. If the invoice number is already used on the PayPal account
        //    (e.g. an earlier send that was not recorded), retry once with a unique suffix.
        $create = $this->createInvoice($token, $payload);
        if ($create->status() === 403) {
            // PayPal caches client-credential tokens for hours; a token issued before the app's
            // Invoicing permission was enabled carries stale scopes. Terminate it and retry once.
            $token = $this->freshAccessToken($token);
            $create = $this->createInvoice($token, $payload);
        }
        if (!$create->successful() && $this->isDuplicateNumber($create)) {
            $payload['detail']['invoice_number'] = $this->invoiceNumber($this->invoiceBase($order), substr((string) time(), -5));
            $create = $this->createInvoice($token, $payload);
        }
        if (!$create->successful()) {
            throw new RuntimeException('PayPal could not create the invoice: ' . $this->errorMessage($create));
        }

        $created = $create->json();
        $invoiceId = (string) ($created['id'] ?? '');
        if ($invoiceId === '') {
            // Minimal representation returns only an href; the id is the last path segment.
            $href = (string) ($created['href'] ?? '');
            $invoiceId = $href !== '' ? basename(parse_url($href, PHP_URL_PATH)) : '';
        }
        if ($invoiceId === '') {
            throw new RuntimeException('PayPal created the invoice but returned no invoice id.');
        }

        // 2) Send it (PayPal emails the customer; the business gets a copy too).
        $send = Http::withToken($token)
            ->acceptJson()
            ->post($this->baseUrl . "/v2/invoicing/invoices/{$invoiceId}/send", [
                'send_to_recipient' => true,
                'send_to_invoicer' => true,
            ]);

        if (!$send->successful()) {
            throw new RuntimeException('PayPal invoice was created but could not be sent: ' . $this->errorMessage($send));
        }

        $payUrl = (string) (data_get($send->json(), 'href') ?: '');
        if ($payUrl === '' || str_contains($payUrl, '/v2/invoicing/')) {
            // The send response's href is the API resource; look up the customer-facing link.
            $payUrl = $this->customerViewUrl($invoiceId, $token) ?: $payUrl;
        }

        return [
            'id' => $invoiceId,
            'number' => (string) data_get($payload, 'detail.invoice_number'),
            'status' => 'SENT',
            'url' => $payUrl ?: null,
            'email' => $email,
        ];
    }

    private function createInvoice(string $token, array $payload)
    {
        return Http::withToken($token)
            ->acceptJson()
            ->withHeaders(['Prefer' => 'return=representation'])
            ->post($this->baseUrl . '/v2/invoicing/invoices', $payload);
    }

    private function isDuplicateNumber($response): bool
    {
        $json = (array) $response->json();
        $text = strtoupper(json_encode($json));
        return str_contains($text, 'DUPLICATE_INVOICE_NUMBER') || str_contains($text, 'INVOICE NUMBER ALREADY EXISTS');
    }

    /**
     * Fetch an invoice's current state from PayPal.
     *
     * @return array{status:string,total:float,paid:float,currency:string,raw:array}
     */
    public function getInvoice(string $invoiceId): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('PayPal is not configured.');
        }
        $res = Http::withToken($this->accessToken())->acceptJson()->get($this->baseUrl . '/v2/invoicing/invoices/' . $invoiceId);
        if (!$res->successful()) {
            throw new RuntimeException('PayPal invoice lookup failed: ' . $this->errorMessage($res));
        }
        $inv = (array) $res->json();
        return [
            'status' => strtoupper((string) ($inv['status'] ?? 'UNKNOWN')),
            'total' => (float) data_get($inv, 'amount.value', 0),
            'paid' => (float) data_get($inv, 'payments.paid_amount.value', 0),
            'currency' => strtoupper((string) data_get($inv, 'amount.currency_code', '')),
            'raw' => $inv,
        ];
    }

    private function accessToken(): string
    {
        $res = Http::asForm()
            ->withBasicAuth($this->clientId, $this->secret)
            ->post($this->baseUrl . '/v1/oauth2/token', ['grant_type' => 'client_credentials']);

        if (!$res->successful() || !$res->json('access_token')) {
            throw new RuntimeException('PayPal login failed (check PAYPAL_CLIENT_ID / PAYPAL_SECRET / PAYPAL_MODE): ' . $this->errorMessage($res));
        }
        return (string) $res->json('access_token');
    }

    private function freshAccessToken(string $staleToken): string
    {
        try {
            Http::asForm()->withBasicAuth($this->clientId, $this->secret)
                ->post($this->baseUrl . '/v1/oauth2/token/terminate', ['token' => $staleToken, 'token_type_hint' => 'ACCESS_TOKEN']);
        } catch (\Throwable $e) {
            Log::warning('PayPal token terminate failed: ' . $e->getMessage());
        }
        return $this->accessToken();
    }

    private function customerViewUrl(string $invoiceId, string $token): ?string
    {
        try {
            $res = Http::withToken($token)->acceptJson()->get($this->baseUrl . "/v2/invoicing/invoices/{$invoiceId}");
            if ($res->successful()) {
                return data_get($res->json(), 'detail.metadata.recipient_view_url')
                    ?: data_get($res->json(), 'detail.metadata.invoicer_view_url');
            }
        } catch (\Throwable $e) {
            Log::warning('PayPal invoice lookup failed: ' . $e->getMessage());
        }
        return null;
    }

    private function buildInvoice(CrmManualOrder $order, string $email): array
    {
        $currency = strtoupper($order->currency ?: 'USD');
        $attempt = (int) $order->paypal_send_count + 1;
        $base = $this->invoiceBase($order);
        // PayPal requires invoice numbers to be unique per account (max 25 chars); suffix re-sends.
        $invoiceNumber = $this->invoiceNumber($base, $attempt > 1 ? 'R' . $attempt : null);

        $items = [];
        foreach ((array) $order->line_items as $row) {
            $qty = (float) ($row['qty'] ?? 0);
            $unit = (float) ($row['unit_price'] ?? 0);
            if ($qty <= 0) continue;
            $name = trim((string) ($row['box_style'] ?? '')) ?: 'Custom Packaging';
            $descParts = array_filter([
                $row['stock'] ?? null ? 'Stock: ' . $row['stock'] : null,
                ($row['length'] ?? null) || ($row['width'] ?? null) || ($row['height'] ?? null)
                    ? 'Size: ' . ($row['length'] ?? '') . ' x ' . ($row['width'] ?? '') . ' x ' . ($row['height'] ?? '') . ' ' . ($row['unit'] ?? '')
                    : null,
                $row['color'] ?? null ? 'Color: ' . $row['color'] : null,
                $row['finishing'] ?? null ? 'Finishing: ' . $row['finishing'] : null,
            ]);
            $items[] = [
                'name' => mb_substr($name, 0, 200),
                'description' => mb_substr(implode(' | ', $descParts), 0, 1000),
                'quantity' => $this->num($qty),
                'unit_amount' => ['currency_code' => $currency, 'value' => $this->money($unit)],
                'unit_of_measure' => 'QUANTITY',
            ];
            $other = (float) ($row['other_charges'] ?? 0);
            if ($other > 0) {
                $items[] = [
                    'name' => mb_substr('Other charges - ' . $name, 0, 200),
                    'quantity' => '1',
                    'unit_amount' => ['currency_code' => $currency, 'value' => $this->money($other)],
                    'unit_of_measure' => 'AMOUNT',
                ];
            }
        }
        if ((float) $order->package_price > 0) {
            $items[] = ['name' => 'Packaging', 'quantity' => '1', 'unit_amount' => ['currency_code' => $currency, 'value' => $this->money($order->package_price)], 'unit_of_measure' => 'AMOUNT'];
        }
        if ((float) $order->rush_charges > 0) {
            $items[] = ['name' => 'Rush charges', 'quantity' => '1', 'unit_amount' => ['currency_code' => $currency, 'value' => $this->money($order->rush_charges)], 'unit_of_measure' => 'AMOUNT'];
        }
        if (!$items) {
            // No priced line items — bill the order total as a single line.
            $items[] = ['name' => 'Order ' . $base, 'quantity' => '1', 'unit_amount' => ['currency_code' => $currency, 'value' => $this->money($order->total + $order->discount)], 'unit_of_measure' => 'AMOUNT'];
        }

        $billing = (array) ($order->billing ?: []);
        [$given, $surname] = $this->splitName((string) ($billing['name'] ?? ''));

        $address = array_filter([
            'address_line_1' => $billing['street'] ?? null,
            'admin_area_2' => $billing['city'] ?? null,
            'admin_area_1' => $billing['state'] ?? null,
            'postal_code' => $billing['zip'] ?? null,
            'country_code' => $this->countryCode($billing['country'] ?? null),
        ]);
        // PayPal rejects an address block without a country code.
        if (empty($address['country_code'])) $address = [];

        $recipient = [
            'billing_info' => array_filter([
                'name' => array_filter(['given_name' => $given, 'surname' => $surname]),
                'business_name' => $billing['company'] ?? null,
                'email_address' => $email,
                'address' => $address ?: null,
            ]),
        ];

        $noteParts = array_filter([
            $order->enquiry_number ? 'Enquiry #: ' . $order->enquiry_number : null,
            $order->payment_term ? 'Payment term: ' . $order->payment_term : null,
            $order->additional_info ? trim((string) $order->additional_info) : null,
        ]);

        $invoice = [
            'detail' => array_filter([
                'invoice_number' => mb_substr($invoiceNumber, 0, 127),
                'reference' => mb_substr('CRM order #' . $order->id, 0, 120),
                'invoice_date' => optional($order->invoice_date)->format('Y-m-d') ?: now()->format('Y-m-d'),
                'currency_code' => $currency,
                'note' => $noteParts ? mb_substr(implode("\n", $noteParts), 0, 4000) : null,
                'payment_term' => ['term_type' => 'DUE_ON_RECEIPT'],
            ]),
            'invoicer' => array_filter([
                'business_name' => mb_substr($this->businessName, 0, 300),
                'email_address' => $this->businessEmail,
                'website' => $order->website ? $this->websiteUrl($order->website) : null,
            ]),
            'primary_recipients' => [$recipient],
            'items' => $items,
            'configuration' => [
                'allow_tip' => false,
                'tax_calculated_after_discount' => true,
                'tax_inclusive' => false,
            ],
        ];

        if ((float) $order->discount > 0) {
            $invoice['amount'] = [
                'breakdown' => [
                    'discount' => [
                        'invoice_discount' => ['amount' => ['currency_code' => $currency, 'value' => $this->money($order->discount)]],
                    ],
                ],
            ];
        }

        return $invoice;
    }

    /** PayPal invoice numbers must be unique and at most 25 characters. */
    private const INVOICE_NUMBER_MAX = 25;

    private function invoiceBase(CrmManualOrder $order): string
    {
        $raw = trim((string) $order->invoice_number);
        return $raw !== '' ? 'TCB-' . $raw : 'TCB-ORD-' . $order->id;
    }

    private function invoiceNumber(string $base, ?string $suffix): string
    {
        $suffix = $suffix !== null && $suffix !== '' ? '-' . $suffix : '';
        $room = self::INVOICE_NUMBER_MAX - strlen($suffix);
        return mb_substr($base, 0, max(1, $room)) . $suffix;
    }

    private function money($v): string
    {
        return number_format((float) $v, 2, '.', '');
    }

    private function num(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.') ?: '0';
    }

    private function splitName(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if ($name === '') return [null, null];
        $parts = explode(' ', $name, 2);
        return [$parts[0], $parts[1] ?? null];
    }

    private function websiteUrl(string $site): ?string
    {
        $site = trim($site);
        if ($site === '') return null;
        return preg_match('~^https?://~i', $site) ? $site : 'https://' . $site;
    }

    /** Map a free-text country to an ISO 3166-1 alpha-2 code (null when unknown). */
    private function countryCode(?string $country): ?string
    {
        $c = strtoupper(trim((string) $country));
        if ($c === '') return null;
        if (strlen($c) === 2) return $c;
        $map = [
            'USA' => 'US', 'UNITED STATES' => 'US', 'UNITED STATES OF AMERICA' => 'US', 'U.S.' => 'US', 'U.S.A.' => 'US',
            'CANADA' => 'CA', 'UNITED KINGDOM' => 'GB', 'UK' => 'GB', 'ENGLAND' => 'GB', 'GREAT BRITAIN' => 'GB',
            'AUSTRALIA' => 'AU', 'NEW ZEALAND' => 'NZ', 'IRELAND' => 'IE', 'GERMANY' => 'DE', 'FRANCE' => 'FR',
            'ITALY' => 'IT', 'SPAIN' => 'ES', 'NETHERLANDS' => 'NL', 'BELGIUM' => 'BE', 'SWEDEN' => 'SE', 'NORWAY' => 'NO',
            'DENMARK' => 'DK', 'SWITZERLAND' => 'CH', 'UAE' => 'AE', 'UNITED ARAB EMIRATES' => 'AE', 'SAUDI ARABIA' => 'SA',
            'PAKISTAN' => 'PK', 'INDIA' => 'IN', 'SINGAPORE' => 'SG', 'MALAYSIA' => 'MY', 'JAPAN' => 'JP', 'MEXICO' => 'MX',
            'BRAZIL' => 'BR', 'SOUTH AFRICA' => 'ZA', 'QATAR' => 'QA', 'KUWAIT' => 'KW', 'OMAN' => 'OM', 'BAHRAIN' => 'BH',
        ];
        return $map[$c] ?? null;
    }

    private function errorMessage($response): string
    {
        $json = $response->json();
        if (is_array($json)) {
            $detail = data_get($json, 'details.0.description') ?: data_get($json, 'details.0.issue');
            $msg = $json['message'] ?? $json['error_description'] ?? $json['error'] ?? null;
            $out = trim(implode(' - ', array_filter([$msg, $detail])));
            if ($out !== '') return $out . ' (HTTP ' . $response->status() . ')';
        }
        return 'HTTP ' . $response->status();
    }
}
