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

    /** Estimate gap written off because the company paid those items directly. */
    public function writeOffTotal(): float
    {
        $total = 0;
        foreach ($this->items as $it) {
            if ($this->itemHasDirect($it->id)) {
                $total += max(0, (float) $it->estimated_total - $this->paidForItem($it->id));
            }
        }

        return round($total, 2);
    }

    /** Requested amount still not covered — direct-settled items are treated as closed. */
    public function outstandingTotal(): float
    {
        return max(0, round((float) $this->estimated_total - $this->paidTotal() - $this->writeOffTotal(), 2));
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
}
