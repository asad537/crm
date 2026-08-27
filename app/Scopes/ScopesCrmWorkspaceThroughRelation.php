<?php

namespace App\Scopes;

trait ScopesCrmWorkspaceThroughRelation
{
    protected static function bootScopesCrmWorkspaceThroughRelation()
    {
        static::addGlobalScope('crm_workspace_relation', function ($query) {
            if (\App\Support\CrmWorkspaceContext::id()) {
                $query->whereHas(static::crmWorkspaceRelation());
            }
        });
    }
}
