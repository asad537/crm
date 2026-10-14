<?php

namespace App\Services;

use App\CrmCustomer;
use App\CrmIncomeEntry;
use Carbon\Carbon;

/** Creates the monthly dues of "other income" customers (rent etc.) so every month shows as due until it is paid. */
class IncomeDueService
{
    /** Make sure a due row exists for every month from the customer's start month through $through (default: this month). */
    public function ensureDues(CrmCustomer $customer, ?Carbon $through = null): int
    {
        if ($customer->customer_type !== 'income' || !$customer->recurring_active || !$customer->recurring_start || (float) $customer->recurring_amount <= 0) {
            return 0;
        }
        $through = ($through ?: now())->copy()->startOfMonth();
        $cursor = Carbon::parse($customer->recurring_start)->startOfMonth();
        $existing = CrmIncomeEntry::withoutGlobalScopes()->where('customer_id', $customer->id)->pluck('id', 'period');
        $created = 0;
        while ($cursor->lte($through)) {
            $period = $cursor->format('Y-m');
            if (!isset($existing[$period])) {
                $day = min(max(1, (int) ($customer->recurring_day ?: 1)), $cursor->daysInMonth);
                CrmIncomeEntry::create([
                    'workspace_id' => $customer->workspace_id,
                    'customer_id' => $customer->id,
                    'income_head' => $customer->income_head ?: 'Rent',
                    'period' => $period,
                    'due_date' => $cursor->copy()->day($day)->toDateString(),
                    'amount_due' => $customer->recurring_amount,
                ]);
                $created++;
            }
            $cursor->addMonth();
        }
        return $created;
    }

    /** Run for every active income customer (daily scheduler + page load). */
    public function ensureAll(): int
    {
        $n = 0;
        foreach (CrmCustomer::withoutGlobalScopes()->where('customer_type', 'income')->where('recurring_active', true)->get() as $c) {
            $n += $this->ensureDues($c);
        }
        return $n;
    }
}
