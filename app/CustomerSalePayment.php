<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/** A payment received on a customer sale (from "Make Receipt"), with its own date and bank / cash account. */
class CustomerSalePayment extends Model
{
    protected $table = 'customer_sale_payments';

    protected $fillable = ['workspace_id', 'customer_sale_id', 'amount', 'paid_at', 'method', 'bank_account_id', 'reference', 'note', 'created_by'];

    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'date'];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('crm_workspace', function ($query) {
            if ($id = \App\Support\CrmWorkspaceContext::id()) {
                $query->where($query->getModel()->getTable() . '.workspace_id', $id);
            }
        });
        static::creating(function ($m) {
            if (!$m->workspace_id && ($id = \App\Support\CrmWorkspaceContext::id())) $m->workspace_id = $id;
        });
    }

    public function sale() { return $this->belongsTo(CustomerSale::class, 'customer_sale_id'); }
}
