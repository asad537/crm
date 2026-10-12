<?php

namespace App\Services;

use App\CrmEmail;
use App\CrmUser;

/**
 * "One customer, one CSR." The sales agent who first brought a customer into the CRM
 * (created or was assigned that customer's first inquiry) owns every later inquiry from
 * the same email address: new inquiries auto-assign to them, other CSRs cannot create
 * inquiries/orders for that customer, and managers get a warning when reassigning.
 */
class CustomerOwnership
{
    /** Owning sales agent for a customer email inside a workspace, or null if nobody owns them yet. */
    public static function ownerFor(?string $clientEmail, ?int $workspaceId, ?int $excludeLeadId = null): ?CrmUser
    {
        $email = strtolower(trim((string) $clientEmail));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        $q = CrmEmail::withoutGlobalScopes()
            ->whereRaw('LOWER(client_email) = ?', [$email])
            ->where(function ($w) { $w->whereNotNull('assigned_to')->orWhereNotNull('created_by'); })
            ->where('is_spam', false)
            ->orderBy('created_at')->orderBy('id');
        if ($workspaceId) $q->where('workspace_id', $workspaceId);
        if ($excludeLeadId) $q->where('id', '!=', $excludeLeadId);

        foreach ($q->limit(20)->get(['assigned_to', 'created_by']) as $lead) {
            foreach ([$lead->assigned_to, $lead->created_by] as $uid) {
                if (!$uid) continue;
                $user = CrmUser::find($uid);
                if ($user && self::isSalesIn($user, $workspaceId)) {
                    return $user;
                }
            }
        }
        return null;
    }

    /** Can $user work (create inquiries/orders) for this customer? Admins and managers always can. */
    public static function allows(CrmUser $user, ?string $clientEmail, ?int $workspaceId): bool
    {
        if ($user->isAdmin() || $user->isSalesManager()) return true;
        $owner = self::ownerFor($clientEmail, $workspaceId);
        return !$owner || (int) $owner->id === (int) $user->id;
    }

    public static function message(CrmUser $owner): string
    {
        return "This customer already belongs to {$owner->name}. Only they (or a manager) can work on their inquiries and orders.";
    }

    private static function isSalesIn(CrmUser $user, ?int $workspaceId): bool
    {
        $role = $workspaceId ? ($user->roleForWorkspace($workspaceId) ?: $user->role) : $user->role;
        return in_array($role, ['sales', 'team_lead'], true);
    }
}
