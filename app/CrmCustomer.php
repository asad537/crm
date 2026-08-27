<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmCustomer extends Model
{
    protected $table = 'crm_customers';

    protected $fillable = [
        'workspace_id', 'name', 'company_name', 'phone', 'email', 'country',
        'billing_address', 'shipping_address', 'tax_number', 'currency', 'notes',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('crm_workspace', function ($query) {
            if ($workspaceId = \App\Support\CrmWorkspaceContext::id()) {
                $query->where($query->getModel()->getTable().'.workspace_id', $workspaceId);
            }
        });

        static::creating(function ($customer) {
            if (!$customer->workspace_id && ($workspaceId = \App\Support\CrmWorkspaceContext::id())) {
                $customer->workspace_id = $workspaceId;
            }
        });
    }

    public function sales()
    {
        return $this->hasMany(CustomerSale::class, 'customer_id');
    }
}
