<?php

namespace App\Http\Controllers\Crm;

use App\DesignJob;
use App\EstimateTicket;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class DesignJobController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->requireDesignJobAccess();
        $workspaceId = \App\Support\CrmWorkspaceContext::id();
        $status = $request->input('status', 'all');
        $stage = $request->input('stage', 'all');
        $dueFilter = in_array($request->input('due', 'all'), ['all', 'overdue'], true)
            ? $request->input('due', 'all') : 'all';

        $query = DesignJob::with(['ticket', 'designer', 'jobCard.attachments', 'challan'])
            ->where('workspace_id', $workspaceId);
        if ($status !== 'all' && array_key_exists($status, DesignJob::STATUSES)) {
            $query->where('status', $status);
        }
        if ($stage === 'unassigned') {
            $query->whereNull('production_stage');
        } elseif ($stage !== 'all' && array_key_exists($stage, DesignJob::STAGES)) {
            $query->where('production_stage', $stage);
        }
        if ($dueFilter === 'overdue') {
            $query->whereDate('due_date', '<', now()->toDateString())
                ->where('status', '!=', 'delivered')
                ->where(function ($overdue) {
                    $overdue->whereNull('production_stage')
                        ->orWhere('production_stage', '!=', 'close');
                });
        }
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('job_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%")
                    ->orWhereHas('ticket', function ($tq) use ($search) {
                        $tq->where('ticket_number', 'like', "%{$search}%")
                            ->orWhere('client_name', 'like', "%{$search}%");
                    });
            });
        }

        $statusCounts = DesignJob::where('workspace_id', $workspaceId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')->pluck('total', 'status');

        // Keep AMS jobs in their natural numeric order (AMS-0062 before
        // AMS-0061). The ID fallback also gives sensible ordering to legacy
        // job-number formats.
        $query->orderByRaw("CASE WHEN job_number REGEXP '^AMS-[0-9]+$' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE WHEN job_number REGEXP '^AMS-[0-9]+$' THEN CAST(SUBSTRING(job_number, 5) AS UNSIGNED) END DESC")
            ->orderByDesc('id');
        $jobs = $query->paginate(20)->appends($request->all());

        return view('crm.design_jobs.index', compact('jobs', 'status', 'stage', 'dueFilter', 'statusCounts'));
    }

    /** Show the job card before creating a job or assigning its number. */
    public function create()
    {
        $this->requireDesigner();

        return view('crm.design_jobs.job_card', [
            'job' => new DesignJob(),
            'card' => new \App\DesignJobCard(),
            'stocks' => collect(),
            'materials' => collect(),
            'attachments' => collect(),
            'designers' => $this->jobDesignerOptions(),
        ]);
    }

    protected function jobDesignerOptions()
    {
        return DB::table('crm_user_workspace')
            ->join('crm_users', 'crm_users.id', '=', 'crm_user_workspace.crm_user_id')
            ->where('crm_user_workspace.workspace_id', \App\Support\CrmWorkspaceContext::id())
            ->whereIn('crm_user_workspace.role', ['designer', 'admin', 'super_admin'])
            ->orderBy('crm_users.name')
            ->get(['crm_users.id', 'crm_users.name']);
    }

    public function updateStatus(Request $request, $id)
    {
        $user = $this->requireDesignJobAccess();
        $job = DesignJob::where('workspace_id', \App\Support\CrmWorkspaceContext::id())->findOrFail($id);
        if (!$user->isAdmin() && (int) $job->designer_id !== (int) $user->id) {
            abort(403, 'Only the designer who created this job can update its status.');
        }
        $data = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(DesignJob::STATUSES)),
            'estimated_delivery_date' => 'nullable|date',
        ]);
        $update = ['status' => $data['status'], 'status_updated_at' => now()];
        if ($request->has('estimated_delivery_date')) {
            $update['estimated_delivery_date'] = $data['estimated_delivery_date'] ?: null;
        }
        $job->update($update);

        return back()->with('success', $job->job_number . ' moved to ' . DesignJob::STATUSES[$data['status']] . '.');
    }

    /** Update the shop-floor production stage shown on the jobs list. */
    public function updateStage(Request $request, $id)
    {
        $user = $this->requireDesignJobAccess();
        $job = DesignJob::where('workspace_id', \App\Support\CrmWorkspaceContext::id())->findOrFail($id);
        if (!$user->isAdmin() && (int) $job->designer_id !== (int) $user->id) {
            abort(403, 'Only the designer who created this job can update its stage.');
        }
        $data = $request->validate([
            'production_stage' => 'nullable|in:' . implode(',', array_keys(DesignJob::STAGES)),
        ]);
        $job->update(['production_stage' => $data['production_stage'] ?: null]);

        $label = $data['production_stage'] ? DesignJob::STAGES[$data['production_stage']] : 'Not set';

        return back()->with('success', $job->job_number . ' stage: ' . $label . '.');
    }

    public function destroy($id)
    {
        $user = $this->requireDesignJobAccess();
        $job = DesignJob::where('workspace_id', \App\Support\CrmWorkspaceContext::id())->findOrFail($id);

        if (!$user->isAdmin() && (!$user->isDesigner() || (int) $job->designer_id !== (int) $user->id)) {
            abort(403, 'Only an admin or the job designer can delete this job.');
        }

        if (Schema::hasTable('crm_inventory_movements') && DB::table('crm_inventory_movements')
            ->where('job_id', $job->id)
            ->exists()) {
            return back()->with('error', 'This job has inventory movements and cannot be deleted.');
        }

        $jobNumber = $job->job_number;
        $attachmentPaths = $job->attachments()->pluck('path')->all();
        $job->delete();
        Storage::disk('local')->delete($attachmentPaths);

        return redirect()->route('crm.design_jobs.index')->with('success', 'Job ' . $jobNumber . ' deleted.');
    }

    protected function requireDesignJobAccess()
    {
        $user = Auth::guard('crm')->user();
        // Designers/admins manage jobs; sales (CSR) can view job status.
        if (!$user->isDesigner() && !$user->isAdmin() && !$user->isSales()) {
            abort(403, 'You do not have access to design jobs.');
        }
        return $user;
    }

    protected function requireDesigner()
    {
        $user = Auth::guard('crm')->user();
        if (!$user->isDesigner() && !$user->isAdmin()) {
            abort(403, 'Only designers can create design jobs.');
        }
        return $user;
    }
}
