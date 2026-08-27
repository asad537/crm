<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmFinishingOption extends Model
{
    protected $fillable = [
        'workspace_id',
        'parent_name',
        'child_name',
        'parent_sort_order',
        'child_sort_order',
        'created_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('crm_workspace', function ($query) {
            if ($workspaceId = \App\Support\CrmWorkspaceContext::id()) {
                $query->where($query->getModel()->getTable().'.workspace_id', $workspaceId);
            }
        });

        static::creating(function ($option) {
            if (!$option->workspace_id && ($workspaceId = \App\Support\CrmWorkspaceContext::id())) {
                $option->workspace_id = $workspaceId;
            }
        });
    }
}
