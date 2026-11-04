<?php

namespace App\Support;

use Carbon\Carbon;

/** Where a party's name opens: a vendor's ledger, a customer's statement of account, an employee's ledger or an owner's capital. */
class PartyLinks
{
    public static function vendor($vendorId): ?string
    {
        return $vendorId ? route('crm.vendor_purchases.index', ['vendor_id' => $vendorId]) : null;
    }

    /** The statement for the given dates, or the report's default period. */
    public static function customer(?string $customer, ?Carbon $from = null, ?Carbon $to = null): ?string
    {
        $customer = trim((string) $customer);
        if ($customer === '' || $customer === '—') return null;

        return route('crm.financial_reports.index', ['report' => 'customer-statement', 'customer' => $customer]
            + ($from && $to ? ['period' => 'custom', 'date_from' => $from->toDateString(), 'date_to' => $to->toDateString()] : []));
    }

    /** A payee with no vendor record: everything paid to that name. */
    public static function payee(?string $name): ?string
    {
        $name = trim((string) $name);

        return in_array($name, ['', '-', '—'], true) ? null : route('crm.payments.index', ['party' => $name]);
    }

    /** A payer with no customer account: everything received from that name. */
    public static function payer(?string $name): ?string
    {
        $name = trim((string) $name);

        return in_array($name, ['', '-', '—'], true) ? null : route('crm.receipts.index', ['customer' => $name]);
    }

    public static function employee($employeeId): ?string
    {
        return $employeeId ? route('crm.employees.show', $employeeId) : null;
    }

    public static function owner($ownerId): ?string
    {
        return $ownerId ? route('crm.owner_capital.index', ['owner' => $ownerId]) : null;
    }
}
