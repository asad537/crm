<?php

namespace App\Policies;

use App\CrmMailAccount;
use App\CrmUser;

/**
 * Mailboxes are private to their owner. Admins / Sales Managers get READ-ONLY
 * visibility when the account allows it (share_with_admin). Only the owner can
 * change, test, delete, or send from an account.
 * Always evaluate with Gate::forUser($crmUser) — CRM users live on the 'crm' guard.
 */
class CrmMailAccountPolicy
{
    public function viewAny(CrmUser $user): bool
    {
        return true;
    }

    public function view(CrmUser $user, CrmMailAccount $account): bool
    {
        if ($this->owns($user, $account)) {
            return true;
        }
        return $account->share_with_admin && ($user->isAdmin() || $user->isSalesManager());
    }

    public function create(CrmUser $user): bool
    {
        return $user->isSales() || $user->isSalesManager() || $user->isAdmin();
    }

    public function update(CrmUser $user, CrmMailAccount $account): bool
    {
        return $this->owns($user, $account);
    }

    public function delete(CrmUser $user, CrmMailAccount $account): bool
    {
        return $this->owns($user, $account);
    }

    public function test(CrmUser $user, CrmMailAccount $account): bool
    {
        return $this->owns($user, $account);
    }

    public function send(CrmUser $user, CrmMailAccount $account): bool
    {
        return $this->owns($user, $account) && $account->is_active;
    }

    private function owns(CrmUser $user, CrmMailAccount $account): bool
    {
        return (int) $account->crm_user_id === (int) $user->id;
    }
}
