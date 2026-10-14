<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmCustomer extends Model
{
    protected $table = 'crm_customers';

    protected $fillable = [
        'workspace_id', 'name', 'company_name', 'contact_person', 'phone', 'email', 'country',
        'billing_address', 'shipping_address', 'tax_number', 'currency', 'notes',
        'customer_type', 'income_head', 'recurring_amount', 'recurring_day', 'recurring_start', 'recurring_active',
    ];

    protected $casts = ['recurring_start' => 'date', 'recurring_active' => 'boolean'];

    public function incomeEntries()
    {
        return $this->hasMany(CrmIncomeEntry::class, 'customer_id')->orderBy('period', 'desc');
    }

    public function isIncomeCustomer(): bool
    {
        return $this->customer_type === 'income';
    }

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
