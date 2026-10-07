<?php

namespace App\Services;

use App\CrmManualOrder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Charges a card through the Vault / NMI-compatible Direct Post API (transact.php).
 * Card data is sent straight to the gateway and never stored or logged here.
 */
class VaultGatewayService
{
    private string $url;
    private string $username;
    private string $password;

    public function __construct()
    {
        $cfg = (array) config('services.vault', []);
        $this->url = (string) ($cfg['url'] ?: 'https://secure.merchantservicegateway.com/api/transact.php');
        $this->username = (string) ($cfg['username'] ?? '');
        $this->password = (string) ($cfg['password'] ?? '');
    }

    public function isConfigured(): bool
    {
        return $this->username !== '' && $this->password !== '';
    }

    /**
     * Run a "sale" for the order.
     *
     * @param  array{number:string,exp:string,cvv:string,name:?string} $card  exp is MMYY
     * @return array{approved:bool,transaction_id:?string,auth_code:?string,card_type:?string,last4:string,message:string,raw:array}
     */
    public function sale(CrmManualOrder $order, float $amount, array $card): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Card gateway is not configured. Set VAULT_USERNAME and VAULT_PASSWORD in .env.');
        }

        $b = (array) ($order->billing ?: []);
        $s = (array) ($order->shipping ?: []);
        $number = preg_replace('/\D+/', '', $card['number'] ?? '');
        $last4 = substr($number, -4);
        [$first, $last] = $this->splitName((string) (($card['name'] ?? '') ?: ($b['name'] ?? '')));
        $label = $order->invoice_number ? 'TCB-' . $order->invoice_number : 'ORD-' . $order->id;

        $params = array_filter([
            'username' => $this->username,
            'password' => $this->password,
            'type' => 'sale',
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => strtoupper($order->currency ?: 'USD'),
            'ccnumber' => $number,
            'ccexp' => preg_replace('/\D+/', '', $card['exp'] ?? ''),
            'cvv' => preg_replace('/\D+/', '', $card['cvv'] ?? ''),
            'orderid' => mb_substr($label, 0, 50),
            'orderdescription' => mb_substr('Order ' . $label . ($order->enquiry_number ? ' / ' . $order->enquiry_number : ''), 0, 255),
            'ipaddress' => request()->ip(),
            // Billing (from the order)
            'first_name' => $first,
            'last_name' => $last,
            'company' => $b['company'] ?? null,
            'address1' => $b['street'] ?? null,
            'city' => $b['city'] ?? null,
            'state' => $b['state'] ?? null,
            'zip' => $b['zip'] ?? null,
            'country' => $this->countryCode($b['country'] ?? null),
            'phone' => $b['phone'] ?? null,
            'email' => $order->customerEmail(),
            // Shipping (from the order)
            'shipping_first_name' => $this->splitName((string) ($s['name'] ?? ''))[0],
            'shipping_last_name' => $this->splitName((string) ($s['name'] ?? ''))[1],
            'shipping_company' => $s['company'] ?? null,
            'shipping_address1' => $s['street'] ?? null,
            'shipping_city' => $s['city'] ?? null,
            'shipping_state' => $s['state'] ?? null,
            'shipping_zip' => $s['zip'] ?? null,
            'shipping_country' => $this->countryCode($s['country'] ?? null),
        ], fn ($v) => $v !== null && $v !== '');

        try {
            $res = Http::asForm()->timeout(60)->post($this->url, $params);
        } catch (\Throwable $e) {
            throw new RuntimeException('Could not reach the card gateway: ' . $e->getMessage());
        }

        parse_str(trim($res->body()), $out);
        $out = is_array($out) ? $out : [];
        $code = (string) ($out['response'] ?? '');
        $message = trim((string) ($out['responsetext'] ?? ''));
        $message = preg_replace('/\s*REFID:\s*\S+$/i', '', $message) ?: $message;

        // Keep a sanitized copy for the audit trail (no card data in it; gateway never echoes it).
        unset($out['username'], $out['password']);
        Log::info('Vault sale for manual order #' . $order->id . ': response=' . $code . ' code=' . ($out['response_code'] ?? '') . ' txn=' . ($out['transactionid'] ?? ''));

        if ($code === '') {
            throw new RuntimeException('Unexpected reply from the card gateway (HTTP ' . $res->status() . ').');
        }

        return [
            'approved' => $code === '1',
            'transaction_id' => $out['transactionid'] ?? null,
            'auth_code' => $out['authcode'] ?? null,
            'card_type' => $this->cardType($number),
            'last4' => $last4,
            'message' => $message !== '' ? $message : ($code === '1' ? 'Approved' : ($code === '2' ? 'Declined' : 'Error')),
            'raw' => $out,
        ];
    }

    /** Verifies the gateway credentials without charging anything (no card is sent). */
    public function checkCredentials(): array
    {
        $res = Http::asForm()->timeout(30)->post($this->url, [
            'username' => $this->username, 'password' => $this->password, 'type' => 'sale', 'amount' => '0.00',
        ]);
        parse_str(trim($res->body()), $out);
        $text = (string) ($out['responsetext'] ?? '');
        $authFailed = stripos($text, 'authentication') !== false || (string) ($out['response_code'] ?? '') === '300' && stripos($text, 'auth') !== false;
        return ['ok' => !$authFailed, 'text' => $text, 'code' => $out['response_code'] ?? null];
    }

    private function splitName(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if ($name === '') return [null, null];
        $parts = explode(' ', $name, 2);
        return [$parts[0], $parts[1] ?? null];
    }

    private function cardType(string $number): ?string
    {
        if (preg_match('/^4/', $number)) return 'Visa';
        if (preg_match('/^(5[1-5]|2[2-7])/', $number)) return 'MasterCard';
        if (preg_match('/^3[47]/', $number)) return 'American Express';
        if (preg_match('/^(6011|65|64[4-9])/', $number)) return 'Discover';
        return null;
    }

    private function countryCode(?string $country): ?string
    {
        $c = strtoupper(trim((string) $country));
        if ($c === '') return null;
        if (strlen($c) === 2) return $c;
        $map = [
            'USA' => 'US', 'UNITED STATES' => 'US', 'UNITED STATES OF AMERICA' => 'US', 'U.S.' => 'US', 'U.S.A.' => 'US',
            'CANADA' => 'CA', 'UNITED KINGDOM' => 'GB', 'UK' => 'GB', 'ENGLAND' => 'GB', 'AUSTRALIA' => 'AU',
            'NEW ZEALAND' => 'NZ', 'IRELAND' => 'IE', 'GERMANY' => 'DE', 'FRANCE' => 'FR', 'ITALY' => 'IT', 'SPAIN' => 'ES',
            'NETHERLANDS' => 'NL', 'UAE' => 'AE', 'UNITED ARAB EMIRATES' => 'AE', 'SAUDI ARABIA' => 'SA', 'PAKISTAN' => 'PK',
            'INDIA' => 'IN', 'SINGAPORE' => 'SG', 'MEXICO' => 'MX', 'SWITZERLAND' => 'CH', 'SWEDEN' => 'SE', 'NORWAY' => 'NO',
        ];
        return $map[$c] ?? null;
    }
}
