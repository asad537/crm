<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmInventoryMovement extends Model
{
    protected $table = 'crm_inventory_movements';

    protected $fillable = [
        'workspace_id', 'inventory_item_id', 'type', 'quantity', 'balance_after',
        'job_id', 'job_number', 'vendor_purchase_id', 'unit_cost', 'reference', 'note', 'created_by',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->workspace_id && ($id = \App\Support\CrmWorkspaceContext::id())) {
                $model->workspace_id = $id;
            }
        });
    }

    public function item()
    {
        return $this->belongsTo(CrmInventoryItem::class, 'inventory_item_id');
    }

    public function creator()
    {
        return $this->belongsTo(CrmUser::class, 'created_by');
    }
}
