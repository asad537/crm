<?php

namespace App\Http\Controllers\Crm;

use App\CrmDeletionLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeletionLogController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('crm')->user();
        if (!$user || (!$user->isAdmin() && !$user->isSuperAdmin() && !$user->isAccounts())) {
            abort(403, 'You do not have access to deletion logs.');
        }

        $workspaceId = \App\Support\CrmWorkspaceContext::id() ?: session('crm_workspace_id');

        $query = CrmDeletionLog::query();
        if ($workspaceId) {
            $query->where(function ($q) use ($workspaceId) {
                $q->where('workspace_id', $workspaceId)->orWhereNull('workspace_id');
            });
        }
        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->entity_type);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('entity_label', 'like', "%{$s}%")
                    ->orWhere('user_name', 'like', "%{$s}%");
            });
        }

        $logs = $query->latest('id')->paginate(30)->appends($request->all());

        $counts = [
            'total'           => CrmDeletionLog::when($workspaceId, fn($q) => $q->where(function ($x) use ($workspaceId) { $x->where('workspace_id', $workspaceId)->orWhereNull('workspace_id'); }))->count(),
            'invoice'         => CrmDeletionLog::when($workspaceId, fn($q) => $q->where(function ($x) use ($workspaceId) { $x->where('workspace_id', $workspaceId)->orWhereNull('workspace_id'); }))->where('entity_type', 'invoice')->count(),
            'vendor_purchase' => CrmDeletionLog::when($workspaceId, fn($q) => $q->where(function ($x) use ($workspaceId) { $x->where('workspace_id', $workspaceId)->orWhereNull('workspace_id'); }))->where('entity_type', 'vendor_purchase')->count(),
            'vendor'          => CrmDeletionLog::when($workspaceId, fn($q) => $q->where(function ($x) use ($workspaceId) { $x->where('workspace_id', $workspaceId)->orWhereNull('workspace_id'); }))->where('entity_type', 'vendor')->count(),
        ];

        return view('crm.deletion_logs.index', compact('logs', 'counts'));
    }
}
