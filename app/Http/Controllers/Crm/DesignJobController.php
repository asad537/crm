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

    public function create()
    {
        $this->requireDesigner();
        $tickets = EstimateTicket::latest()->limit(150)
            ->get(['id', 'ticket_number', 'client_name', 'product_style', 'status']);
        $suggestedJobNumber = 'JOB-' . now()->format('ymd') . '-' . strtoupper(substr(uniqid(), -5));
        return view('crm.design_jobs.create', compact('tickets', 'suggestedJobNumber'));
    }

    public function store(Request $request)
    {
        $user = $this->requireDesigner();
        $workspaceId = \App\Support\CrmWorkspaceContext::id();
        $data = $request->validate([
            'job_number' => 'nullable|string|max:100',
            'estimate_ticket_id' => 'nullable|integer',
            'estimate_number' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',
            'details' => 'nullable|string|max:3000',
            'status' => 'nullable|in:' . implode(',', array_keys(DesignJob::STATUSES)),
            'estimated_delivery_date' => 'nullable|date',
        ]);

        // Estimate link is optional: either pick a ticket or type an estimate number.
        $ticket = null;
        if (!empty($data['estimate_ticket_id'])) {
            $ticket = EstimateTicket::find($data['estimate_ticket_id']);
            if (!$ticket) {
                return back()->withInput()->with('error', 'Please select a valid estimate ticket.');
            }
        }
        if (!$ticket && empty($data['estimate_number'])) {
            return back()->withInput()->with('error', 'Select an estimate ticket or enter an estimate number.');
        }

        // Job number: use the one typed, else auto-generate. Keep it unique.
        $jobNumber = trim($data['job_number'] ?? '');
        if ($jobNumber === '') {
            $jobNumber = 'JOB-' . now()->format('ymd') . '-' . strtoupper(substr(uniqid(), -5));
        }
        if (DesignJob::where('job_number', $jobNumber)->exists()) {
            return back()->withInput()->with('error', 'Job number "' . $jobNumber . '" already exists. Use a different one.');
        }

        $job = DesignJob::create([
            'job_number' => $jobNumber,
            'workspace_id' => $workspaceId,
            'estimate_ticket_id' => $ticket ? $ticket->id : null,
            'estimate_number' => $ticket ? null : ($data['estimate_number'] ?? null),
            'designer_id' => $user->id,
            'title' => $data['title'],
            'details' => $data['details'] ?? null,
            'status' => $data['status'] ?? 'designing',
            'status_updated_at' => now(),
            'estimated_delivery_date' => $data['estimated_delivery_date'] ?? null,
        ]);

        $ref = $ticket ? $ticket->ticket_number : ($data['estimate_number'] ?? '');
        return redirect()->route('crm.design_jobs.show', $job->id)
            ->with('success', 'Job ' . $job->job_number . ' created' . ($ref ? ' against ' . $ref : '') . '.');
    }

    public function show($id)
    {
        $user = $this->requireDesignJobAccess();
        $job = DesignJob::with(['ticket', 'designer'])
            ->where('workspace_id', \App\Support\CrmWorkspaceContext::id())
            ->findOrFail($id);
        $canUpdate = $user->isAdmin() || (int) $job->designer_id === (int) $user->id;
        return view('crm.design_jobs.show', compact('job', 'canUpdate'));
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
