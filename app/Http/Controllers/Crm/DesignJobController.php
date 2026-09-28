<?php

namespace App\Http\Controllers\Crm;

use App\DesignJob;
use App\EstimateTicket;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DesignJobController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->requireDesignJobAccess();
        $workspaceId = \App\Support\CrmWorkspaceContext::id();
        $status = $request->input('status', 'all');

        $query = DesignJob::with(['ticket', 'designer'])
            ->where('workspace_id', $workspaceId)
            ->latest();
        if ($status !== 'all' && array_key_exists($status, DesignJob::STATUSES)) {
            $query->where('status', $status);
        }
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('job_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('ticket', function ($tq) use ($search) {
                        $tq->where('ticket_number', 'like', "%{$search}%")
                            ->orWhere('client_name', 'like', "%{$search}%");
                    });
            });
        }

        $statusCounts = DesignJob::where('workspace_id', $workspaceId)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')->pluck('total', 'status');
        $jobs = $query->paginate(20)->appends($request->all());

        return view('crm.design_jobs.index', compact('jobs', 'status', 'statusCounts'));
    }

    /**
     * Create a blank design job and go straight to its job card.
     * (The "New Job" button posts here — no separate create form.)
     */
    public function store(Request $request)
    {
        $user = $this->requireDesigner();
        $workspaceId = \App\Support\CrmWorkspaceContext::id();

        // Unique auto job number.
        do {
            $jobNumber = 'JOB-' . now()->format('ymd') . '-' . strtoupper(substr(uniqid(), -5));
        } while (DesignJob::where('job_number', $jobNumber)->exists());

        $job = DesignJob::create([
            'job_number' => $jobNumber,
            'workspace_id' => $workspaceId,
            'designer_id' => $user->id,
            'title' => 'Untitled Job',
            'status' => 'designing',
            'status_updated_at' => now(),
        ]);

        return redirect()->route('crm.design_jobs.job_card.edit', $job->id)
            ->with('success', 'Job ' . $job->job_number . ' created. Fill in its job card below.');
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
