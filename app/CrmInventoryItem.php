<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmInventoryItem extends Model
{
    protected $table = 'crm_inventory_items';

    protected $fillable = [
        'workspace_id', 'name', 'category', 'paper_size', 'gsm', 'stock_type',
        'unit', 'quantity', 'reorder_level', 'unit_cost', 'currency', 'notes',
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

    public function movements()
    {
        return $this->hasMany(CrmInventoryMovement::class, 'inventory_item_id')->orderBy('id', 'desc');
    }

    public function getIsLowAttribute()
    {
        return $this->reorder_level > 0 && $this->quantity <= $this->reorder_level;
    }
}
