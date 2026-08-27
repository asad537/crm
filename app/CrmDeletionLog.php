<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CrmDeletionLog extends Model
{
    protected $table = 'crm_deletion_logs';

    protected $fillable = [
        'workspace_id', 'user_id', 'user_name', 'user_role',
        'entity_type', 'entity_id', 'entity_label', 'snapshot', 'reason',
    ];

    protected $casts = ['snapshot' => 'array'];

    /**
     * Record a deletion. Call this from any destroy() method that removes a
     * business record (invoices, vendor purchases, vendors, etc.).
     */
    public static function record($entityType, $entity, $label = null, $snapshot = [], $reason = null)
    {
        $user = \Auth::guard('crm')->user();
        return static::create([
            'workspace_id' => \App\Support\CrmWorkspaceContext::id() ?: session('crm_workspace_id'),
            'user_id'      => $user ? $user->id : null,
            'user_name'    => $user ? $user->name : null,
            'user_role'    => $user && method_exists($user, 'getRoleLabel') ? $user->getRoleLabel() : ($user->role ?? null),
            'entity_type'  => $entityType,
            'entity_id'    => is_object($entity) ? ($entity->id ?? 0) : (int) $entity,
            'entity_label' => $label,
            'snapshot'     => $snapshot ?: null,
            'reason'       => $reason,
        ]);
    }

    public function entityLabelPretty()
    {
        $map = [
            'invoice'         => 'Invoice',
            'vendor_purchase' => 'Vendor Purchase',
            'vendor'          => 'Vendor',
        ];
        return $map[$this->entity_type] ?? ucfirst(str_replace('_', ' ', $this->entity_type));
    }
}
