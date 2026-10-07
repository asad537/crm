<?php

namespace App\Services;

use App\CrmManualOrder;
use RuntimeException;

/**
 * Charges a card for a manual order through the Vault gateway and records the payment.
 * Shared by the CRM "Payment" modal and the customer invoice portal.
 */
class ManualOrderCardCharge
{
    /**
     * @param  array{number:string,exp:string,cvv:string,name:?string} $card  exp = MMYY
     * @return array{approved:bool,message:string,card_label:string,transaction_id:?string}
     */
    public function charge(CrmManualOrder $order, float $amount, array $card, ?int $createdBy, string $source): array
    {
        $amount = round($amount, 2);
        $balance = $order->balanceDue();
        if ($amount <= 0) {
            throw new RuntimeException('Amount must be greater than zero.');
        }
        if ($amount > $balance + 0.009) {
            throw new RuntimeException('Charge of ' . number_format($amount, 2) . ' exceeds the remaining balance of ' . number_format($balance, 2) . '.');
        }

        $gateway = new VaultGatewayService();
        if (!$gateway->isConfigured()) {
            throw new RuntimeException('Card payments are not available right now. Please contact support.');
        }

        $result = $gateway->sale($order, $amount, $card);
        $cardLabel = trim(($result['card_type'] ?: 'Card') . ' ****' . $result['last4']);

        if (!$result['approved']) {
            return ['approved' => false, 'message' => $result['message'], 'card_label' => $cardLabel, 'transaction_id' => null];
        }

        $order->payments()->create([
            'workspace_id' => $order->workspace_id,
            'amount' => $amount,
            'paid_at' => now()->toDateString(),
            'method' => 'Credit/Debit Card',
            'reference' => $result['transaction_id'],
            'note' => $cardLabel . ($result['auth_code'] ? ' · Auth ' . $result['auth_code'] : '') . ' · ' . $source,
            'created_by' => $createdBy,
            'gateway' => 'vault',
            'gateway_transaction_id' => $result['transaction_id'],
            'gateway_auth_code' => $result['auth_code'],
            'card_type' => $result['card_type'],
            'card_last4' => $result['last4'],
            'gateway_response' => json_encode($result['raw']),
        ]);
        $order->refreshPaymentStatus();

        return ['approved' => true, 'message' => $result['message'], 'card_label' => $cardLabel, 'transaction_id' => $result['transaction_id']];
    }

    public static function luhn(string $number): bool
    {
        $sum = 0; $alt = false;
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $n = (int) $number[$i];
            if ($alt) { $n *= 2; if ($n > 9) $n -= 9; }
            $sum += $n; $alt = !$alt;
        }
        return strlen($number) >= 12 && $sum % 10 === 0;
    }

    /** Normalise "MM/YY" or "MM/YYYY" into MMYY, or null when malformed. */
    public static function normalizeExpiry(string $exp): ?string
    {
        if (!preg_match('/^\s*(0[1-9]|1[0-2])\s*\/?\s*(\d{2}|\d{4})\s*$/', $exp, $m)) return null;
        return $m[1] . substr($m[2], -2);
    }
}
