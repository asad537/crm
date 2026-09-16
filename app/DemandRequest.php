<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DemandRequest extends Model
{
    protected $fillable = [
        'workspace_id', 'created_by', 'request_no', 'request_date', 'requested_by',
        'priority', 'status', 'force_completed', 'approved_by', 'approved_at', 'rejection_reason', 'notes',
        'estimated_total', 'actual_total',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at' => 'datetime',
        'force_completed' => 'boolean',
        'estimated_total' => 'decimal:2',
        'actual_total' => 'decimal:2',
    ];

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

    /** Requested amount still not covered = requested − everything paid. */
    public function outstandingTotal(): float
    {
        return round(array_sum($this->itemRemainingMap()), 2);
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

    /** Remaining on items the company is paying directly (still owed by company). */
    public function companyOutstanding(): float
    {
        $map = $this->itemRemainingMap();
        $total = 0;
        foreach ($this->items as $it) {
            if ($this->itemHasDirect($it->id)) {
                $total += $map[$it->id] ?? 0;
            }
        }

        return round($total, 2);
    }

    /** Remaining on items that still have to be arranged through the account. */
    public function accountOutstanding(): float
    {
        $map = $this->itemRemainingMap();
        $total = 0;
        foreach ($this->items as $it) {
            if (!$this->itemHasDirect($it->id)) {
                $total += $map[$it->id] ?? 0;
            }
        }

        return round($total, 2);
    }

    /**
     * Remaining per item = requested − paid (tagged), then a waterfall share of any
     * general / advance (untagged) money. No automatic write-off.
     */
    public function itemRemainingMap(): array
    {
        $general = $this->generalPaid();
        $map = [];
        foreach ($this->items as $it) {
            $base = max(0, (float) $it->estimated_total - $this->paidForItem($it->id));
            $alloc = min($general, $base);
            $general -= $alloc;
            $map[$it->id] = round($base - $alloc, 2);
        }

        return $map;
    }

    /** Remaining still owed on one item (waterfall-aware). */
    public function itemRemaining($itemId): float
    {
        return $this->itemRemainingMap()[$itemId] ?? 0.0;
    }
}
