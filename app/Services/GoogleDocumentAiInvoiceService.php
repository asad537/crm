<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Illuminate\Http\UploadedFile;

class GoogleDocumentAiInvoiceService
{
    public function extract(UploadedFile $file)
    {
        $config = config('services.document_ai');
        $credentialsPath = $config['credentials_path'];

        if (!$config['project_id'] || !$config['processor_id'] || !$credentialsPath || !is_readable($credentialsPath)) {
            throw new \RuntimeException('Google Document AI is not configured. Set GOOGLE_DOCUMENT_AI_PROJECT_ID, GOOGLE_DOCUMENT_AI_PROCESSOR_ID and GOOGLE_DOCUMENT_AI_CREDENTIALS_PATH.');
        }

        $credentials = json_decode(file_get_contents($credentialsPath), true);
        if (empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new \RuntimeException('The Google Document AI service-account JSON is invalid.');
        }

        $now = time();
        $assertion = JWT::encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'aud' => $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ], $credentials['private_key'], 'RS256');

        $token = $this->request(
            $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
            http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]),
            ['Content-Type: application/x-www-form-urlencoded']
        );

        if (empty($token['access_token'])) {
            throw new \RuntimeException('Google authentication failed. Check the service-account permissions.');
        }

        $location = strtolower($config['location'] ?: 'us');
        $endpoint = sprintf(
            'https://%s-documentai.googleapis.com/v1/projects/%s/locations/%s/processors/%s:process',
            $location,
            rawurlencode($config['project_id']),
            rawurlencode($location),
            rawurlencode($config['processor_id'])
        );

        $response = $this->request($endpoint, json_encode([
            'rawDocument' => [
                'content' => base64_encode(file_get_contents($file->getRealPath())),
                'mimeType' => $file->getMimeType(),
            ],
        ]), [
            'Content-Type: application/json',
            'Authorization: Bearer '.$token['access_token'],
        ]);

        if (empty($response['document'])) {
            throw new \RuntimeException($response['error']['message'] ?? 'Google Document AI could not read this document.');
        }

        return $this->mapInvoice($response['document']);
    }

    private function request($url, $body, array $headers)
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('PHP cURL is required for Google Document AI.');
        }
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 90,
        ]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($body === false) throw new \RuntimeException('Could not connect to Google Document AI: '.$error);
        $decoded = json_decode($body, true);
        if ($status < 200 || $status >= 300) throw new \RuntimeException($decoded['error']['message'] ?? 'Google Document AI request failed.');
        return is_array($decoded) ? $decoded : [];
    }

    private function mapInvoice(array $document)
    {
        $invoice = ['items' => []];
        foreach (($document['entities'] ?? []) as $entity) {
            $type = $entity['type'] ?? '';
            $value = $this->entityValue($entity);
            if ($type === 'supplier_name') $invoice['vendor_name'] = $value;
            if ($type === 'supplier_address') $invoice['vendor_address'] = $value;
            if ($type === 'supplier_tax_id') $invoice['vendor_tax_id'] = $value;
            if ($type === 'invoice_id') $invoice['invoice_number'] = $value;
            if ($type === 'invoice_date') $invoice['purchase_date'] = $this->dateValue($entity, $value);
            if ($type === 'due_date') $invoice['due_date'] = $this->dateValue($entity, $value);
            if ($type === 'net_amount') $invoice['subtotal'] = $this->moneyValue($entity, $value);
            if ($type === 'total_tax_amount') $invoice['tax_amount'] = $this->moneyValue($entity, $value);
            if ($type === 'total_amount') {
                $invoice['document_total'] = $this->moneyValue($entity, $value);
                $currency = data_get($entity, 'normalizedValue.moneyValue.currencyCode');
                if ($currency) $invoice['currency'] = $currency;
            }
            if ($type === 'line_item') $invoice['items'][] = $this->mapLineItem($entity);
        }

        $invoice['items'] = array_values(array_filter($invoice['items'], function ($item) {
            return !empty($item['item_name']);
        }));
        if (!$invoice['items'] && !empty($invoice['document_total'])) {
            $invoice['items'][] = ['category' => 'Other', 'item_name' => 'Invoice total', 'quantity' => 1, 'unit' => 'Pieces', 'line_total' => $invoice['document_total']];
        }
        if (!empty($invoice['subtotal']) && !empty($invoice['tax_amount'])) {
            $invoice['vat_percentage'] = round($invoice['tax_amount'] * 100 / $invoice['subtotal'], 2);
        }
        return $invoice;
    }

    private function mapLineItem(array $entity)
    {
        $item = ['category' => 'Other', 'quantity' => 1, 'unit' => 'Pieces', 'line_total' => 0];
        foreach (($entity['properties'] ?? []) as $property) {
            $type = preg_replace('/^line_item\//', '', $property['type'] ?? '');
            $value = $this->entityValue($property);
            if (in_array($type, ['description', 'product_code'])) $item['item_name'] = $value;
            if ($type === 'quantity') $item['quantity'] = max((float) $this->number($value), 1);
            if ($type === 'unit') $item['unit'] = $this->safeUnit($value);
            if ($type === 'unit_price') $item['unit_price'] = $this->moneyValue($property, $value);
            if (in_array($type, ['amount', 'total_price'])) $item['line_total'] = $this->moneyValue($property, $value);
        }
        if (!$item['line_total'] && !empty($item['unit_price'])) $item['line_total'] = round($item['quantity'] * $item['unit_price'], 2);
        return $item;
    }

    private function entityValue(array $entity) { return trim((string) ($entity['mentionText'] ?? data_get($entity, 'normalizedValue.text') ?? '')); }
    private function moneyValue(array $entity, $fallback) { $money = data_get($entity, 'normalizedValue.moneyValue'); return is_array($money) ? ((float) ($money['units'] ?? 0) + ((float) ($money['nanos'] ?? 0) / 1000000000)) : $this->number($fallback); }
    private function number($value) { return (float) preg_replace('/[^0-9.\-]/', '', str_replace(',', '', (string) $value)); }
    private function dateValue(array $entity, $fallback) { $date = data_get($entity, 'normalizedValue.dateValue'); return is_array($date) && !empty($date['year']) ? sprintf('%04d-%02d-%02d', $date['year'], $date['month'], $date['day']) : $fallback; }
    private function safeUnit($value) { $map = ['sheet'=>'Sheets','sheets'=>'Sheets','kg'=>'Kg','kilogram'=>'Kg','roll'=>'Rolls','rolls'=>'Rolls','piece'=>'Pieces','pieces'=>'Pieces','box'=>'Boxes','boxes'=>'Boxes','liter'=>'Liters','litre'=>'Liters','meter'=>'Meters','metre'=>'Meters','pallet'=>'Pallets']; return $map[strtolower(trim((string) $value))] ?? 'Pieces'; }
}
