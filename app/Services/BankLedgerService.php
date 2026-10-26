<?php

namespace App\Services;

use App\CrmBankAccount;
use App\CrmBankTransfer;
use App\CrmCapitalEntry;
use App\CrmFixedAsset;
use App\Http\Controllers\Crm\PaymentController;
use App\Http\Controllers\Crm\ReceiptController;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Bank & cash ledger: every receipt and payment the CRM knows (plus owner's capital and drawings), booked to the account it went through.
 * A movement uses the account picked on its form; if none was picked, the account set to pick up that
 * payment method (Cash, Bank Transfer...) is used; otherwise it stays "unassigned" until someone assigns it.
 * Balance = opening balance + money in − money out ± transfers, counting movements from the opening date.
 */
class BankLedgerService
{
    /** Source tables a movement can be re-assigned on (all carry a nullable bank_account_id). */
    const LINK_TABLES = [
        'order_payment' => 'crm_order_payments',
        'customer_sale' => 'customer_sales',
        'income_entry' => 'crm_income_entries',
        'vendor_payment' => 'vendor_purchase_payments',
        'demand_payment' => 'demand_request_payments',
        'expense' => 'crm_expenses',
        'invoice_payment' => 'crm_purchase_invoice_payments',
        'payroll_line' => 'crm_payroll_lines',
        'advance' => 'crm_employee_advances',
        'fixed_asset' => 'crm_fixed_assets',
        'capital' => 'crm_capital_entries',
        'direct_receipt' => 'crm_direct_receipts',
        'sale_payment' => 'customer_sale_payments',
    ];

    protected array $memo = [];

    /** Normalise free-text payment methods ("bank transfer", "Company Account"...) into the groups accounts can claim. */
    public static function methodGroup(?string $method): string
    {
        $m = mb_strtolower(trim((string) $method));
        if ($m === '' || $m === '-' || $m === '—') return 'Not specified';
        if (str_contains($m, 'petty')) return 'Petty Cash';
        if (str_contains($m, 'cash')) return 'Cash';
        if (str_contains($m, 'bank') || str_contains($m, 'transfer') || str_contains($m, 'company account')) return 'Bank';
        if (str_contains($m, 'card')) return 'Card';
        if (str_contains($m, 'cheque') || str_contains($m, 'check')) return 'Cheque';
        if (str_contains($m, 'adjust')) return 'Adjustment';

        return 'Other';
    }

    public function accounts(): Collection
    {
        return $this->memo['accounts'] ??= CrmBankAccount::orderByDesc('is_active')->orderBy('type')->orderBy('name')->get();
    }

    /** method group => account id, from each account's "pick up automatically" methods. */
    public function methodMap(): array
    {
        return $this->memo['map'] ??= (function () {
            $map = [];
            foreach ($this->accounts()->where('is_active', true) as $account) {
                foreach ($account->auto_methods ?: [] as $group) $map[$group] ??= $account->id;
            }
            return $map;
        })();
    }

    /** Every money movement (newest first) with the account it is booked to (account_id null = unassigned). */
    public function movements(): Collection
    {
        return $this->memo['movements'] ??= (function () {
            $accountIds = $this->accounts()->pluck('id')->all();
            $map = $this->methodMap();
            $rows = collect();
            $push = function (string $dir, $date, float $amount, $party, string $type, $ref, $method, $url, ?string $linkType, $linkId, $explicit) use (&$rows, $accountIds, $map) {
                $explicit = $explicit && in_array((int) $explicit, $accountIds, true) ? (int) $explicit : null;
                $rows->push((object) [
                    // Stable across requests (bank reconciliation stores it); rows without a source record get a content hash.
                    'key' => $linkType ? $linkType . ':' . $linkId : 'h:' . substr(md5($dir . '|' . $type . '|' . $ref . '|' . Carbon::parse($date)->toDateString() . '|' . round($amount, 2) . '|' . $party), 0, 20),
                    'dir' => $dir, 'date' => Carbon::parse($date)->startOfDay(), 'amount' => round($amount, 2),
                    'party' => $party ?: '—', 'type' => $type, 'ref' => $ref, 'method' => $method, 'url' => $url,
                    'link_type' => $linkType, 'link_id' => $linkId,
                    'account_id' => $explicit ?: ($map[self::methodGroup($method)] ?? null),
                    'auto' => !$explicit,
                ]);
            };

            foreach (app(ReceiptController::class)->receipts() as $r) {
                $push('in', $r->date, $r->amount, $r->customer, $r->source_label, $r->ref, $r->method, $r->url, $r->link_type, $r->link_id, $r->bank_account_id);
            }
            // Purchase invoices come in per payment (part payments too), the same rows the Payments tab lists.
            foreach (app(PaymentController::class)->payments()->whereNull('duplicate_of') as $p) {
                $push('out', $p->date, $p->amount, $p->party, $p->source_label, $p->ref, $p->method, $p->url, $p->link_type, $p->link_id, $p->bank_account_id);
            }
            // Fixed assets bought outright are real cash out. Bought on credit are not paid yet; those on a
            // purchase invoice are paid through the invoice's payments (already above).
            foreach (CrmFixedAsset::whereNotIn('payment_method', CrmFixedAsset::NOT_PAID_DIRECTLY)->get() as $a) {
                $push('out', $a->purchase_date, (float) $a->amount, $a->supplier_name, 'Fixed Asset', $a->number, $a->payment_method,
                    route('crm.fixed_assets.index', ['search' => $a->name]), 'fixed_asset', $a->id, $a->bank_account_id);
            }
            // Owner's capital put in (money in) and drawings taken out (money out). Opening / non-cash capital moves no money.
            foreach (CrmCapitalEntry::with('owner')->get()->filter->isCash() as $c) {
                $push($c->type === 'drawing' ? 'out' : 'in', $c->entry_date, (float) $c->amount, optional($c->owner)->name,
                    $c->type === 'drawing' ? "Owner's Drawing" : "Owner's Capital", $c->number, $c->method,
                    route('crm.owner_capital.index', ['owner' => $c->owner_id]), 'capital', $c->id, $c->bank_account_id);
            }

            return $rows->sortByDesc(fn ($m) => $m->date->timestamp)->values();
        })();
    }

    public function transfers(): Collection
    {
        return $this->memo['transfers'] ??= CrmBankTransfer::with(['from', 'to', 'creator'])->orderByDesc('transfer_date')->orderByDesc('id')->get();
    }

    /**
     * Opening, in, out, transfers and balance for every account, plus what is still unassigned —
     * today, or as of the end of $asOf (an account opened after that date shows 0).
     */
    public function balances(?Carbon $asOf = null): array
    {
        $end = $asOf ? $asOf->copy()->endOfDay() : null;
        $movements = $end ? $this->movements()->filter(fn ($m) => $m->date->lte($end)) : $this->movements();
        $transfers = $end ? $this->transfers()->filter(fn ($t) => $t->transfer_date->lte($end)) : $this->transfers();
        $accounts = [];
        foreach ($this->accounts() as $account) {
            if ($end && $account->opening_date->gt($end)) {
                $accounts[$account->id] = ['account' => $account, 'opening' => 0.0, 'in' => 0.0, 'out' => 0.0, 'transfers_in' => 0.0, 'transfers_out' => 0.0, 'entries' => 0, 'balance' => 0.0];
                continue;
            }
            $since = $account->opening_date->copy()->startOfDay();
            $mine = $movements->where('account_id', $account->id)->filter(fn ($m) => $m->date->gte($since));
            $in = (float) $mine->where('dir', 'in')->sum('amount');
            $out = (float) $mine->where('dir', 'out')->sum('amount');
            $tIn = (float) $transfers->where('to_account_id', $account->id)->filter(fn ($t) => $t->transfer_date->gte($since))->sum('amount');
            $tOut = (float) $transfers->where('from_account_id', $account->id)->filter(fn ($t) => $t->transfer_date->gte($since))->sum('amount');
            $accounts[$account->id] = [
                'account' => $account, 'opening' => (float) $account->opening_balance, 'in' => $in, 'out' => $out,
                'transfers_in' => $tIn, 'transfers_out' => $tOut, 'entries' => $mine->count(),
                'balance' => round((float) $account->opening_balance + $in - $out + $tIn - $tOut, 2),
            ];
        }
        $unassigned = $movements->whereNull('account_id');

        return [
            'accounts' => $accounts,
            'total' => round(array_sum(array_column($accounts, 'balance')), 2),
            'unassigned' => ['count' => $unassigned->count(), 'in' => (float) $unassigned->where('dir', 'in')->sum('amount'),
                'out' => (float) $unassigned->where('dir', 'out')->sum('amount')],
        ];
    }

    /** Passbook-style statement for one account with a running balance (optionally limited to a date range). */
    public function statement(CrmBankAccount $account, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $since = $account->opening_date->copy()->startOfDay();
        $entries = $this->movements()->where('account_id', $account->id)->filter(fn ($m) => $m->date->gte($since))
            ->map(fn ($m) => (object) ['key' => $m->key, 'date' => $m->date, 'type' => $m->type, 'party' => $m->party, 'ref' => $m->ref, 'url' => $m->url,
                'method' => $m->method, 'in' => $m->dir === 'in' ? $m->amount : 0.0, 'out' => $m->dir === 'out' ? $m->amount : 0.0,
                'auto' => $m->auto, 'link_type' => $m->link_type, 'link_id' => $m->link_id, 'transfer_id' => null]);
        foreach ($this->transfers() as $t) {
            if ($t->transfer_date->lt($since)) continue;
            if ((int) $t->from_account_id === (int) $account->id) {
                $entries->push((object) ['key' => 'transfer:' . $t->id, 'date' => $t->transfer_date, 'type' => 'Transfer', 'party' => 'To ' . optional($t->to)->name, 'ref' => $t->reference, 'url' => null,
                    'method' => null, 'in' => 0.0, 'out' => (float) $t->amount, 'auto' => false, 'link_type' => null, 'link_id' => null, 'transfer_id' => $t->id, 'note' => $t->note]);
            } elseif ((int) $t->to_account_id === (int) $account->id) {
                $entries->push((object) ['key' => 'transfer:' . $t->id, 'date' => $t->transfer_date, 'type' => 'Transfer', 'party' => 'From ' . optional($t->from)->name, 'ref' => $t->reference, 'url' => null,
                    'method' => null, 'in' => (float) $t->amount, 'out' => 0.0, 'auto' => false, 'link_type' => null, 'link_id' => null, 'transfer_id' => $t->id, 'note' => $t->note]);
            }
        }
        $entries = $entries->sortBy(fn ($e) => [$e->date->timestamp, $e->in > 0 ? 0 : 1])->values();

        $running = (float) $account->opening_balance;
        $broughtForward = $running;
        $rows = [];
        foreach ($entries as $e) {
            $running = round($running + $e->in - $e->out, 2);
            $e->balance = $running;
            if ($from && $e->date->lt($from->copy()->startOfDay())) { $broughtForward = $running; continue; }
            if ($to && $e->date->gt($to->copy()->endOfDay())) continue;
            $rows[] = $e;
        }

        return [
            'rows' => array_reverse($rows),        // newest first on screen
            'opening' => (float) $account->opening_balance,
            'brought_forward' => $from ? $broughtForward : (float) $account->opening_balance,
            'in' => (float) collect($rows)->sum('in'),
            'out' => (float) collect($rows)->sum('out'),
            'closing' => $rows ? end($rows)->balance : ($from ? $broughtForward : (float) $account->opening_balance),
        ];
    }

    /** Find one movement (only movements of this workspace are found) — used before re-assigning it. */
    public function findMovement(string $linkType, int $linkId)
    {
        return $this->movements()->first(fn ($m) => $m->link_type === $linkType && (int) $m->link_id === $linkId);
    }
}
