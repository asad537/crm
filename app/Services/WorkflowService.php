<?php

namespace App\Services;

use App\CrmApproval;
use Illuminate\Support\Facades\Auth;

class WorkflowService
{
    /**
     * Log a new workflow transition/approval state.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param string $stage e.g. 'sales_agent_review', 'customer_quote_review', 'artwork_proof_review', 'color_match_review', 'balance_payment_check'
     * @param string $status e.g. 'pending', 'approved', 'rejected', 'revision_requested'
     * @param string|null $comments
     * @param int|null $approverId
     * @return CrmApproval
     */
    public static function logApproval($model, $stage, $status, $comments = null, $approverId = null)
    {
        if (!$approverId && Auth::guard('crm')->check()) {
            $approverId = Auth::guard('crm')->id();
        }

        return CrmApproval::create([
            'approvable_type' => get_class($model),
            'approvable_id' => $model->id,
            'stage' => $stage,
            'status' => $status,
            'approver_id' => $approverId,
            'comments' => $comments,
        ]);
    }
}
