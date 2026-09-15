<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmManualOrder extends Model
{
    protected $table = 'crm_manual_orders';

    protected $fillable = [
        'workspace_id', 'crm_email_id', 'user_name', 'enquiry_number', 'invoice_status',
        'invoice_number', 'website', 'currency', 'invoice_date', 'customer_id', 'payment_term',
        'billing', 'shipping', 'sales_person', 'shipping_method', 'shipping_term',
        'payment_term_via', 'additional_info', 'line_items', 'sub_total', 'package_price',
        'rush_charges', 'discount', 'total', 'created_by',
    ];

    protected $casts = [
        'billing' => 'array',
        'shipping' => 'array',
        'line_items' => 'array',
        'invoice_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('workspace', function ($query) {
            if ($id = \App\Support\CrmWorkspaceContext::id()) {
                $query->where($query->getModel()->getTable() . '.workspace_id', $id);
            }
        });
        static::creating(function ($model) {
            if (!$model->workspace_id && ($id = \App\Support\CrmWorkspaceContext::id())) {
                $model->workspace_id = $id;
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }
}
