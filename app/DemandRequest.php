<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DemandRequest extends Model
{
    protected $fillable = [
        'workspace_id', 'created_by', 'request_no', 'request_date', 'requested_by',
        'priority', 'status', 'force_completed', 'approved_by', 'approved_at', 'rejection_reason', 'notes',
        'estimated_total', 'vat_percentage', 'actual_total', 'cash_in_hand_used', 'cash_in_hand_note',
        'account_funding_tracked',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
        'force_completed' => 'boolean',
        'estimated_total' => 'decimal:2',
        'vat_percentage' => 'decimal:2',
        'actual_total' => 'decimal:2',
        'cash_in_hand_used' => 'decimal:2',
        'account_funding_tracked' => 'boolean',
    ];

    /** Base (ex-VAT) amount for one item. */
    protected function itemBase($it): float
    {
        $qty = is_numeric(trim((string) $it->qty)) ? (float) $it->qty : null;
        if ($qty !== null && $it->estimated_price !== null) {
            return round($qty * (float) $it->estimated_price, 2);
        }

        return (float) $it->estimated_total; // no VAT split possible
    }

    /** Total VAT across all items (each item total already includes its VAT). */
    public function vatAmount(): float
    {
        $v = 0;
        foreach ($this->items as $it) {
            $v += max(0, (float) $it->estimated_total - $this->itemBase($it));
        }

        return round($v, 2);
    }

    /** Sum of item totals (VAT included) — same as estimated_total. */
    public function grandTotal(): float
    {
        return round((float) $this->estimated_total, 2);
    }

    /** Grand total minus VAT. */
    public function subtotalExVat(): float
    {
        return round($this->grandTotal() - $this->vatAmount(), 2);
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('crm_workspace', function ($query) {
            if ($workspaceId = \App\Support\CrmWorkspaceContext::id()) {
                $query->where($query->getModel()->getTable() . '.workspace_id', $workspaceId);
            }
        });

        static::creating(function ($model) {
            if (!$model->workspace_id && ($workspaceId = \App\Support\CrmWorkspaceContext::id())) {
                $model->workspace_id = $workspaceId;
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(CrmUser::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(DemandRequestItem::class)->orderBy('position');
    }

    public function payments()
    {
        return $this->hasMany(DemandRequestPayment::class)->orderBy('paid_at')->orderBy('id');
    }

    public function attachments()
    {
        return $this->hasMany(DemandRequestAttachment::class)->orderByDesc('id');
    }

    public function settlements()
    {
        return $this->hasMany(DemandRequestSettlement::class)->orderBy('paid_at')->orderBy('id');
    }

    /** Money actually transferred to the accountant; legacy rows are reimbursements. */
    public function settledTotal(): float
    {
        if (!$this->exists && !$this->relationLoaded('settlements')) {
            return 0.0;
        }

        return (float) $this->settlements->sum('amount');
    }

    /** Total money paid so far against this demand (all payments). */
    public function paidTotal(): float
    {
        return (float) $this->payments->sum('amount');
    }

    /** True if an item has been paid directly by the company (closes it — gap written off). */
    public function itemHasDirect($itemId): bool
    {
        return $this->payments->where('item_id', $itemId)->where('pay_type', 'Direct')->count() > 0;
    }

    /** No automatic write-off — remaining is always requested minus paid. */
    public function writeOffTotal(): float
    {
        return 0.0;
    }

    /** Amount still owed by any side (no cross-subsidy). */
    public function outstandingTotal(): float
    {
        return $this->owedTotal();
    }

    /** How much has been paid against one specific item (payments tagged to it). */
    public function paidForItem($itemId): float
    {
        return (float) $this->payments->where('item_id', $itemId)->sum('amount');
    }

    /** Total cash adjusted OUT of a category (used to pay other items via "Adjust from another category"). */
    public function adjustedOutForCategory($cat): float
    {
        $cat = trim((string) $cat);
        if ($cat === '') {
            return 0.0;
        }

        return (float) $this->payments->filter(fn($p) => strcasecmp(trim((string) $p->adjust_from), $cat) === 0)->sum('amount');
    }

    /**
     * Adjusted-out amount attributed to one item (spread FIFO across items sharing its category).
     * The LAST item of the category absorbs any remainder, so if more was adjusted out of a
     * category than its budget, the excess stays visible as a credit on that item's side
     * (e.g. Account Outstanding for an Account-side petty-cash line).
     */
    public function adjustedOutForItem($itemId): float
    {
        $it = $this->items->firstWhere('id', $itemId);
        if (!$it) {
            return 0.0;
        }
        $cat = trim((string) $it->category);
        if ($cat === '') {
            return 0.0;
        }
        $pool = $this->adjustedOutForCategory($cat);
        $catItems = $this->items->filter(fn($row) => strcasecmp(trim((string) $row->category), $cat) === 0)->values();
        $lastId = $catItems->last()->id ?? null;
        foreach ($catItems as $row) {
            $amt = (float) $row->estimated_total;
            $consume = ($row->id === $lastId) ? $pool : min($amt, $pool);
            if ($row->id == $itemId) {
                return round($consume, 2);
            }
            $pool = max(0, $pool - $consume);
        }

        return 0.0;
    }

    /** Total cash moved between categories via adjustments — capped at each item's own budget. */
    public function totalAdjustedOut(): float
    {
        $sum = 0;
        foreach ($this->items as $it) {
            $sum += $this->adjustedOutForItem($it->id);
        }

        return round($sum, 2);
    }

    /** Money that went through a company account and needs reconciliation. */
    public function accountTotal(): float
    {
        return (float) $this->payments->where('pay_type', '!=', 'Direct')->sum('amount');
    }

    /** Planned budget for items assigned to the accountant, not proof of cash received. */
    public function accountBudget(): float
    {
        return round((float) $this->items->filter(fn($it) => ($it->pay_by ?? 'Company') === 'Account')->sum('estimated_total'), 2);
    }

    /** Do not hand the accountant funds for Account items already paid directly by the company. */
    public function accountFundingTarget(): float
    {
        $planned = $this->accountBudget();
        foreach ($this->items as $it) {
            if (($it->pay_by ?? 'Company') === 'Account') {
                $planned -= (float) $this->payments->where('item_id', $it->id)->where('pay_type', 'Direct')->sum('amount');
            }
        }

        return round(max(0, $planned, $this->accountTotal()), 2);
    }

    /** Actual funds available on this demand: company transfers plus an internal cash draw. */
    public function accountFundsAvailable(): float
    {
        return round($this->settledTotal() + (float) $this->cash_in_hand_used, 2);
    }

    /** Vendor expenses still open on Account-planned items, independent of cash transfers. */
    public function accountExpenseRemaining(): float
    {
        $remaining = $this->accountBudget() - $this->generalPaid();
        foreach ($this->items as $it) {
            if (($it->pay_by ?? 'Company') === 'Account') {
                $remaining -= $this->paidForItem($it->id) + $this->adjustedOutForItem($it->id);
            }
        }

        return round(max(0, $remaining), 2);
    }

    /** Maximum additional transfer without exceeding the budget or actual accountant spend. */
    public function accountTransferLimit(): float
    {
        if (!in_array($this->status, ['Approved', 'Partially Paid', 'Completed'], true)) {
            return 0.0;
        }
        if (!$this->account_funding_tracked) {
            return $this->status === 'Completed' ? round(max(0, -$this->accountOutstanding()), 2) : 0.0;
        }

        return round(max(0, $this->accountFundingTarget() - $this->accountFundsAvailable()), 2);
    }

    /** Money the company paid directly (no reconciliation needed). */
    public function directTotal(): float
    {
        return (float) $this->payments->where('pay_type', 'Direct')->sum('amount');
    }

    /** Payments not tied to any specific item (general / advance). */
    public function generalPaid(): float
    {
        return (float) $this->payments->whereNull('item_id')->sum('amount');
    }

    /**
     * Signed balance for the company (direct) side = paid − requested on items the
     * company is paying directly. Negative = still owed, positive = credit/overpaid.
     * No cross-subsidy: account surplus never covers a company shortfall.
     */
    public function companyOutstanding(): float
    {
        // No outstanding before the demand enters the payment stage (Draft / Submitted / Rejected).
        if (!in_array($this->status, ['Approved', 'Partially Paid', 'Completed'], true)) {
            return 0.0;
        }
        $total = 0;
        foreach ($this->items as $it) {
            // Bucket by the item's planned payer, not by how it was actually paid.
            if (($it->pay_by ?? 'Company') === 'Company') {
                $total += $this->paidForItem($it->id) + $this->adjustedOutForItem($it->id) - (float) $it->estimated_total;
            }
        }

        return round($total, 2);
    }

    /**
     * New demands: actual funds received (including an internal cash draw) minus
     * expenses the accountant paid. Legacy demands retain the old approved-budget
     * balance because their historical transfers were never recorded.
     */
    public function accountOutstanding(): float
    {
        // No outstanding before the demand enters the payment stage (Draft / Submitted / Rejected).
        if (!in_array($this->status, ['Approved', 'Partially Paid', 'Completed'], true)) {
            return 0.0;
        }
        if ($this->account_funding_tracked) {
            return round($this->accountFundsAvailable() - $this->accountTotal(), 2);
        }
        $total = 0;
        foreach ($this->items as $it) {
            // Bucket by the item's planned payer, not by how it was actually paid.
            if (($it->pay_by ?? 'Company') === 'Account') {
                $total += (float) $it->estimated_total - $this->paidForItem($it->id) - $this->adjustedOutForItem($it->id);
            }
        }
        $total -= $this->generalPaid();
        $total += $this->settledTotal();

        return round($total, 2);
    }

    /** Net balance across the whole demand = paid (+ cross-category adjustments) − requested (signed). */
    public function netBalance(): float
    {
        return round($this->paidTotal() + $this->totalAdjustedOut() - (float) $this->estimated_total, 2);
    }

    /**
     * Money badge once the demand enters payment stage.
     * Paid means no side still owes money; Partial means some money exists but balance remains.
     */
    public function paymentStatus(): string
    {
        if (!in_array($this->status, ['Approved', 'Partially Paid', 'Completed'], true)) {
            return '';
        }
        if ($this->outstandingTotal() <= 0.009) {
            return 'Paid';
        }

        return $this->paidTotal() > 0.009 ? 'Partial' : 'Unpaid';
    }

    /** Vendor expenses still open, not the amount of cash handed to the accountant. */
    public function owedTotal(): float
    {
        if (!in_array($this->status, ['Approved', 'Partially Paid', 'Completed'], true)) {
            return 0.0;
        }
        $owed = 0;
        $c = $this->companyOutstanding();
        if ($c < 0) {
            $owed += -$c;
        }
        $owed += $this->accountExpenseRemaining();

        return round($owed, 2);
    }

    /** Amount paid over and above the requested total (credit). */
    public function overpaidTotal(): float
    {
        return max(0, $this->netBalance());
    }

    /** Owed per item (>=0) from that item's own tagged payments. */
    public function itemRemainingMap(): array
    {
        $map = [];
        foreach ($this->items as $it) {
            $map[$it->id] = max(0, round((float) $it->estimated_total - $this->paidForItem($it->id) - $this->adjustedOutForItem($it->id), 2));
        }

        return $map;
    }

    /** Signed net for one item = paid (+ adjusted out of its category) − requested. */
    public function itemNet($itemId): float
    {
        $it = $this->items->firstWhere('id', $itemId);

        return $it ? round($this->paidForItem($itemId) + $this->adjustedOutForItem($itemId) - (float) $it->estimated_total, 2) : 0.0;
    }

    /** Owed (>=0) on one item. */
    public function itemRemaining($itemId): float
    {
        return $this->itemRemainingMap()[$itemId] ?? 0.0;
    }
}
