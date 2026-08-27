<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class CrmUser extends Authenticatable
{
    use Notifiable;

    protected $table = 'crm_users';

    protected $fillable = [
        'name', 'email', 'password', 'role', 'production_facility_id', 'allowed_ip',
        'imap_host', 'imap_port', 'imap_encryption',
        'smtp_host', 'smtp_port', 'smtp_encryption',
        'email_user', 'email_pass', 'signature',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function isAdmin()
    {
        return in_array($this->activeWorkspaceRole(), ['admin', 'super_admin'], true);
    }

    public function isSuperAdmin()
    {
        return $this->activeWorkspaceRole() === 'super_admin';
    }

    public function isSalesManager()
    {
        return $this->activeWorkspaceRole() === 'sales_manager';
    }

    public function isSales()
    {
        return $this->activeWorkspaceRole() === 'sales';
    }

    public function isDesigner()
    {
        return $this->activeWorkspaceRole() === 'designer';
    }

    public function isPrepress()
    {
        return $this->activeWorkspaceRole() === 'prepress';
    }

    public function isRetention()
    {
        return $this->activeWorkspaceRole() === 'retention';
    }

    public function isQC()
    {
        return $this->activeWorkspaceRole() === 'qc';
    }

    public function isShipping()
    {
        return $this->activeWorkspaceRole() === 'shipping';
    }

    public function isEstimator()
    {
        return $this->activeWorkspaceRole() === 'estimator';
    }

    public function isTeamLead()
    {
        return $this->activeWorkspaceRole() === 'team_lead';
    }

    public function isProductionManager()
    {
        return $this->activeWorkspaceRole() === 'production_manager';
    }

    public function isPressOperator()
    {
        return $this->activeWorkspaceRole() === 'press_operator';
    }

    public function isFinishingOperator()
    {
        return $this->activeWorkspaceRole() === 'finishing_operator';
    }

    public function isWarehouse()
    {
        return $this->activeWorkspaceRole() === 'warehouse';
    }

    public function isAccounts()
    {
        return $this->activeWorkspaceRole() === 'accounts';
    }

    public function productionFacility()
    {
        return $this->belongsTo(ProductionFacility::class, 'production_facility_id');
    }

    public function canAssign()
    {
        return in_array($this->activeWorkspaceRole(), ['super_admin', 'admin', 'sales_manager'], true);
    }

    public function workspaces()
    {
        return $this->belongsToMany(CrmWorkspace::class, 'crm_user_workspace', 'crm_user_id', 'workspace_id')
            ->withPivot('role')->withTimestamps();
    }

    public function activeWorkspaceRole()
    {
        $workspaceId = \App\Support\CrmWorkspaceContext::id();
        if (!$workspaceId && function_exists('session')) {
            $workspaceId = session('crm_workspace_id');
        }
        if (!$workspaceId) return $this->role;

        return $this->roleForWorkspace($workspaceId) ?: $this->role;
    }

    /**
     * Return the role assigned on the workspace pivot. The legacy role column is
     * not authoritative for users who belong to more than one CRM workspace.
     */
    public function roleForWorkspace($workspaceId)
    {
        $workspaceId = (int) $workspaceId;
        if (!$workspaceId) return null;

        if ($this->relationLoaded('workspaces')) {
            $workspace = $this->workspaces->firstWhere('id', $workspaceId);
            if ($workspace) return $workspace->pivot->role;
        }

        $workspace = $this->workspaces()->where('crm_workspaces.id', $workspaceId)->first();
        return $workspace ? $workspace->pivot->role : null;
    }

    public function scopeInWorkspace($query, $workspaceId = null, array $roles = null)
    {
        $workspaceId = $workspaceId ?: \App\Support\CrmWorkspaceContext::id();
        return $query->whereHas('workspaces', function ($q) use ($workspaceId, $roles) {
            $q->where('crm_workspaces.id', $workspaceId);
            if ($roles) $q->whereIn('crm_user_workspace.role', $roles);
        });
    }

    public function getRoleLabel()
    {
        switch ($this->activeWorkspaceRole()) {
            case 'super_admin':   return 'Owner';
            case 'admin':         return 'Administrator';
            case 'sales_manager': return 'Sales Manager';
            case 'sales':         return 'Sales Agent';
            case 'designer':      return 'Designer';
            case 'prepress':      return 'Prepress';
            case 'retention':     return 'Customer Retention Team';
            case 'qc':            return 'QC Inspector';
            case 'shipping':      return 'Shipping Agent';
            case 'estimator':     return 'Estimator';
            case 'team_lead':     return 'Team Lead';
            case 'production_manager': return 'Production Manager';
            case 'press_operator': return 'Press Operator';
            case 'finishing_operator': return 'Finishing Operator';
            case 'warehouse': return 'Warehouse Officer';
            case 'accounts': return 'Accounts Officer';
            default:              return ucfirst($this->activeWorkspaceRole());
        }
    }
}
