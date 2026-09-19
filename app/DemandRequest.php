<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DemandRequest extends Model
{
    protected $fillable = [
        'workspace_id', 'created_by', 'request_no', 'request_date', 'requested_by',
        'priority', 'status', 'force_completed', 'approved_by', 'approved_at', 'rejection_reason', 'notes',
        'estimated_total', 'vat_percentage', 'actual_total', 'cash_in_hand_used', 'cash_in_hand_note',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
        'force_completed' => 'boolean',
        'estimated_total' => 'decimal:2',
        'vat_percentage' => 'decimal:2',
        'actual_total' => 'decimal:2',
        'cash_in_hand_used' => 'decimal:2',
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

    /** Money that went through a company account and needs reconciliation. */
    public function accountTotal(): float
    {
        return (float) $this->payments->where('pay_type', '!=', 'Direct')->sum('amount');
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
        $total = 0;
        foreach ($this->items as $it) {
            if ($this->itemHasDirect($it->id)) {
                $total += $this->paidForItem($it->id) - (float) $it->estimated_total;
            }
        }

        return round($total, 2);
    }

    /**
     * Signed balance for the account side = paid − requested on items paid through the
     * account, plus any untagged general/advance money. Negative = owed, positive = credit.
     */
    public function accountOutstanding(): float
    {
        $total = 0;
        foreach ($this->items as $it) {
            if (!$this->itemHasDirect($it->id)) {
                $total += $this->paidForItem($it->id) - (float) $it->estimated_total;
            }
        }
        $total += $this->generalPaid();
        // Cash in Hand drawn against this demand settles it like account money.
        $total += (float) $this->cash_in_hand_used;

        return round($total, 2);
    }

    /** Net balance across the whole demand = paid − requested (signed). */
    public function netBalance(): float
    {
        return round($this->paidTotal() - (float) $this->estimated_total, 2);
    }

    /**
     * Simple money status once the demand is approved: Paid when nothing is outstanding,
     * otherwise Unpaid. Returns '' before approval (no payment stage yet).
     */
    public function paymentStatus(): string
    {
        if (!in_array($this->status, ['Approved', 'Partially Paid', 'Completed'], true)) {
            return '';
        }

        return $this->owedTotal() <= 0.009 ? 'Paid' : 'Unpaid';
    }

    /** What any side still owes (no cross-subsidy) — drives completion status. */
    public function owedTotal(): float
    {
        $owed = 0;
        $c = $this->companyOutstanding();
        $a = $this->accountOutstanding();
        if ($c < 0) {
            $owed += -$c;
        }
        if ($a < 0) {
            $owed += -$a;
        }

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
            $map[$it->id] = max(0, round((float) $it->estimated_total - $this->paidForItem($it->id), 2));
        }

        return $map;
    }

    /** Signed net for one item = paid − requested (tagged payments). */
    public function itemNet($itemId): float
    {
        $it = $this->items->firstWhere('id', $itemId);

        return $it ? round($this->paidForItem($itemId) - (float) $it->estimated_total, 2) : 0.0;
    }

    /** Owed (>=0) on one item. */
    public function itemRemaining($itemId): float
    {
        return $this->itemRemainingMap()[$itemId] ?? 0.0;
    }
}
