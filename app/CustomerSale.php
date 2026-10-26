<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CustomerSale extends Model
{
    protected $fillable = [
        'workspace_id', 'created_by', 'customer_id', 'crm_email_id', 'order_number', 'order_date',
        'due_date', 'item_name', 'description', 'quantity', 'unit', 'unit_price',
        'subtotal', 'discount_amount', 'tax_amount', 'shipping_cost', 'total_amount',
        'paid_amount', 'balance_amount', 'currency', 'payment_status', 'order_status',
        'payment_method', 'notes', 'import_source_key',
        'bank_account_id',
    ];

    protected $casts = [
        'order_date' => 'date', 'due_date' => 'date', 'quantity' => 'decimal:2',
        'unit_price' => 'decimal:6', 'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2', 'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2', 'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2', 'balance_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('crm_workspace', function ($query) {
            if ($workspaceId = \App\Support\CrmWorkspaceContext::id()) {
                $query->where($query->getModel()->getTable().'.workspace_id', $workspaceId);
            }
        });

        static::creating(function ($sale) {
            if (!$sale->workspace_id && ($workspaceId = \App\Support\CrmWorkspaceContext::id())) {
                $sale->workspace_id = $workspaceId;
            }
        });
    }

    /** Payments received from "Make Receipt" (paid_amount already includes them). */
    public function payments()
    {
        return $this->hasMany(CustomerSalePayment::class, 'customer_sale_id')->orderBy('paid_at')->orderBy('id');
    }

    public function customer()
    {
        return $this->belongsTo(CrmCustomer::class, 'customer_id');
    }

    /** Files attached on the Customers tab (PO, signed invoice, delivery note...). */
    public function attachments()
    {
        return $this->hasMany(CustomerSaleAttachment::class, 'customer_sale_id')->orderBy('id');
    }

    public function crmOrder()
    {
        return $this->belongsTo(CrmEmail::class, 'crm_email_id');
    }
}
