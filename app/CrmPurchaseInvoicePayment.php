<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmPurchaseInvoicePayment extends Model
{
    protected $table = 'crm_purchase_invoice_payments';

    protected $fillable = ['purchase_invoice_id', 'workspace_id', 'amount', 'paid_at', 'method', 'reference', 'note', 'created_by', 'bank_account_id'];

    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'date'];

    public function invoice() { return $this->belongsTo(CrmPurchaseInvoice::class, 'purchase_invoice_id'); }

    public function creator() { return $this->belongsTo(CrmUser::class, 'created_by'); }
}
