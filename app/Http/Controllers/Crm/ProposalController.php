<?php

namespace App\Http\Controllers\Crm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CrmProposal;
use App\CrmUser;
use Illuminate\Support\Facades\Auth;

class ProposalController extends Controller
{
    /** Admin (incl. CEO) and designers explicitly granted Proposal access. */
    private function guard()
    {
        $user = Auth::guard('crm')->user();
        if (!$user || !$user->canAccessProposals()) {
            abort(403);
        }
        return $user;
    }

    /** Designers available for the "Assigned designer" dropdown, in the active workspace. */
    private function designers()
    {
        $workspaceId = session('crm_workspace_id') ?: \App\Support\CrmWorkspaceContext::id();
        return CrmUser::inWorkspace($workspaceId, ['designer'])
            ->orderBy('name')
            ->get(['crm_users.id', 'crm_users.name', 'crm_users.proposal_access']);
    }

    /** Status buckets behind the Active / Open / History tabs. */
    private const TAB_STATUSES = [
        'active' => ['requested'],
        'open' => ['in_progress', 'change_requested'],
        'history' => ['completed'],
    ];

    public function index(Request $request)
    {
        $user = $this->guard();
        $isAdmin = $user->isAdmin();

        $tab = $request->query('tab', 'active');
        if (!array_key_exists($tab, self::TAB_STATUSES)) $tab = 'active';

        // Visibility per tab:
        //  - Admin sees everything.
        //  - Designer's ACTIVE tab is the open pool: unassigned proposals any designer may pick,
        //    plus any already assigned to them. OPEN/HISTORY show only what is assigned to them.
        $applyScope = function ($q, $tabKey) use ($user, $isAdmin) {
            if ($isAdmin) return;
            if ($tabKey === 'active') {
                $q->where(function ($w) use ($user) {
                    $w->whereNull('assigned_designer_id')->orWhere('assigned_designer_id', $user->id);
                });
            } else {
                $q->where('assigned_designer_id', $user->id);
            }
        };

        $proposals = CrmProposal::with(['designer', 'creator'])
            ->whereIn('status', self::TAB_STATUSES[$tab])
            ->where(function ($q) use ($applyScope, $tab) { $applyScope($q, $tab); })
            ->latest()
            ->get();

        // Per-tab counts (respecting the same per-tab visibility).
        $counts = [];
        foreach (self::TAB_STATUSES as $key => $statuses) {
            $counts[$key] = CrmProposal::whereIn('status', $statuses)
                ->where(function ($q) use ($applyScope, $key) { $applyScope($q, $key); })
                ->count();
        }

        // Admin-only access panel: every designer in the workspace + whether they can see Proposals.
        $accessDesigners = $isAdmin ? $this->designers() : collect();

        return view('crm.proposals.index', [
            'proposals' => $proposals,
            'isAdmin' => $isAdmin,
            'isDesigner' => $user->isDesigner(),
            'currentUserId' => $user->id,
            'tab' => $tab,
            'counts' => $counts,
            'accessDesigners' => $accessDesigners,
        ]);
    }

    /** Admin grants access to a designer chosen from the searchable picker. */
    public function grantAccess(Request $request)
    {
        $user = Auth::guard('crm')->user();
        if (!$user || !$user->isAdmin()) abort(403);

        $data = $request->validate(['designer_id' => 'required|integer']);
        $designer = CrmUser::findOrFail($data['designer_id']);
        $designer->proposal_access = 1;
        $designer->save();

        return redirect()->route('crm.proposals.index')
            ->with('success', 'Granted Proposal access for ' . $designer->name . '.');
    }

    /** Admin grants/revokes which specific designers can access the Proposal module. */
    public function toggleAccess(Request $request, $designerId)
    {
        $user = Auth::guard('crm')->user();
        if (!$user || !$user->isAdmin()) abort(403);

        $designer = CrmUser::findOrFail($designerId);
        $designer->proposal_access = $request->boolean('grant') ? 1 : 0;
        $designer->save();

        return redirect()->route('crm.proposals.index')
            ->with('success', ($designer->proposal_access ? 'Granted' : 'Revoked') . ' Proposal access for ' . $designer->name . '.');
    }

    /** Designer picks a proposal from the Active pool -> it gets assigned to them. */
    public function claim($id)
    {
        $user = $this->guard();
        $proposal = CrmProposal::findOrFail($id);

        if ($proposal->assigned_designer_id && (int) $proposal->assigned_designer_id !== (int) $user->id) {
            return redirect()->route('crm.proposals.index')->with('error', 'This proposal has already been picked by another designer.');
        }

        $proposal->assigned_designer_id = $user->id;
        if ($proposal->status === 'requested') $proposal->status = 'in_progress';
        $proposal->save();

        return redirect()->route('crm.proposals.show', $proposal->id)->with('success', 'Proposal assigned to you.');
    }

    public function create()
    {
        $this->guard();
        return view('crm.proposals.create', [
            'designers' => $this->designers(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->guard();

        $data = $request->validate([
            'subject' => 'required|string|max:190',
            'client_name' => 'nullable|string|max:190',
            'product_name' => 'nullable|string|max:190',
            'quantity' => 'nullable|string|max:120',
            'size' => 'nullable|string|max:120',
            'comment' => 'nullable|string|max:5000',
            'server_path' => 'nullable|string|max:1000',
            'assigned_designer_id' => 'nullable|integer',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,ai,psd,eps,zip|max:20480',
        ]);

        $proposal = new CrmProposal();
        $proposal->fill([
            'subject' => $data['subject'],
            'client_name' => $data['client_name'] ?? null,
            'product_name' => $data['product_name'] ?? null,
            'quantity' => $data['quantity'] ?? null,
            'size' => $data['size'] ?? null,
            'comment' => $data['comment'] ?? null,
            'server_path' => $data['server_path'] ?? null,
            'assigned_designer_id' => $data['assigned_designer_id'] ?? null,
            'status' => 'open',
            'created_by' => $user->id,
        ]);

        if ($request->hasFile('attachment')) {
            [$path, $name] = $this->storeAttachment($request->file('attachment'));
            $proposal->attachment_path = $path;
            $proposal->attachment_name = $name;
        }

        $proposal->save();

        return redirect()->route('crm.proposals.index')->with('success', 'Proposal created successfully.');
    }

    public function update(Request $request, $id)
    {
        $user = $this->guard();
        $proposal = CrmProposal::findOrFail($id);
        $this->authorizeProposal($user, $proposal);

        if ($user->isAdmin()) {
            // Admin can edit everything, including the inquiry-derived fields and the assignment.
            $data = $request->validate([
                'subject' => 'required|string|max:190',
                'client_name' => 'nullable|string|max:190',
                'product_name' => 'nullable|string|max:190',
                'quantity' => 'nullable|string|max:120',
                'size' => 'nullable|string|max:120',
                'comment' => 'nullable|string|max:5000',
                'server_path' => 'nullable|string|max:1000',
                'assigned_designer_id' => 'nullable|integer',
                'status' => 'nullable|string|max:40',
                'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,ai,psd,eps,zip|max:20480',
            ]);
            $proposal->fill([
                'subject' => $data['subject'],
                'client_name' => $data['client_name'] ?? null,
                'product_name' => $data['product_name'] ?? null,
                'quantity' => $data['quantity'] ?? null,
                'size' => $data['size'] ?? null,
                'comment' => $data['comment'] ?? null,
                'server_path' => $data['server_path'] ?? null,
                'status' => $data['status'] ?? $proposal->status,
            ]);
            $proposal->assigned_designer_id = $data['assigned_designer_id'] ?? null;
        } else {
            // Designer only edits their work fields; the inquiry data (subject/client/product/
            // quantity/size) and the assignment stay read-only.
            $data = $request->validate([
                'comment' => 'nullable|string|max:5000',
                'server_path' => 'nullable|string|max:1000',
                'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,ai,psd,eps,zip|max:20480',
            ]);
            $proposal->comment = $data['comment'] ?? null;
            $proposal->server_path = $data['server_path'] ?? null;
            // Designer submitting their work marks the proposal complete -> moves to History.
            $proposal->status = 'completed';
        }

        if ($request->hasFile('attachment')) {
            [$path, $name] = $this->storeAttachment($request->file('attachment'));
            $proposal->attachment_path = $path;
            $proposal->attachment_name = $name;
        }

        $proposal->save();

        if (!$user->isAdmin()) {
            return redirect()->route('crm.proposals.index', ['tab' => 'history'])
                ->with('success', 'Proposal submitted & completed — moved to History.');
        }

        return redirect()->route('crm.proposals.index')->with('success', 'Proposal updated.');
    }

    public function destroy($id)
    {
        $user = $this->guard();
        if (!$user->isAdmin()) abort(403);
        $proposal = CrmProposal::findOrFail($id);
        $proposal->delete();

        return redirect()->route('crm.proposals.index')->with('success', 'Proposal deleted.');
    }

    /** Dedicated proposal page (view + edit + change request). */
    public function show($id)
    {
        $user = $this->guard();
        $proposal = CrmProposal::with(['designer', 'creator'])->findOrFail($id);
        $this->authorizeProposal($user, $proposal);

        // Opening an unassigned proposal from the Active pool auto-assigns it to the designer
        // (there is no separate "Pick" step — viewing IS picking). Admin viewing does not claim.
        if ($user->isDesigner() && !$user->isAdmin() && empty($proposal->assigned_designer_id)) {
            $proposal->assigned_designer_id = $user->id;
            if ($proposal->status === 'requested') $proposal->status = 'in_progress';
            $proposal->save();
            $proposal->load('designer');
        }

        return view('crm.proposals.show', [
            'proposal' => $proposal,
            'designers' => $this->designers(),
            'isAdmin' => $user->isAdmin(),
        ]);
    }

    /** Raise a change request on a proposal (adds a note + flags the status). */
    public function changeRequest(Request $request, $id)
    {
        $user = $this->guard();
        $proposal = CrmProposal::findOrFail($id);
        $this->authorizeProposal($user, $proposal);

        $data = $request->validate(['change_request_note' => 'required|string|max:5000']);
        $proposal->change_request_note = $data['change_request_note'];
        $proposal->status = 'change_requested';
        $proposal->save();

        return redirect()->route('crm.proposals.show', $proposal->id)->with('success', 'Change request submitted.');
    }

    /** Inquiry Action -> "Request Proposal": create a proposal from the inquiry and open its page. */
    public function requestFromInquiry(Request $request, $id)
    {
        // Raised from the inquiry Action menu, so allow the sales-side roles (+admin),
        // not only designers. The proposal then surfaces to admin/designers in the Proposal tab.
        $user = Auth::guard('crm')->user();
        if (!$user || (!$user->isAdmin() && !$user->isSalesManager() && !$user->isSales() && !$user->isTeamLead() && !$user->isDesigner())) {
            abort(403);
        }
        $inquiry = \App\CrmEmail::findOrFail($id);

        // The requester (e.g. a sales agent) can open the dedicated page only if they are
        // an admin or designer; otherwise they just get a confirmation and the proposal
        // surfaces to admin/designers in the Proposal tab.
        $canOpen = $user->isAdmin() || $user->isDesigner();

        // Reuse an existing proposal for this inquiry instead of duplicating.
        $existing = CrmProposal::where('crm_email_id', $inquiry->id)->first();
        if ($existing) {
            return $canOpen
                ? redirect()->route('crm.proposals.show', $existing->id)
                : redirect()->route('crm.inquiries.index')->with('success', 'Proposal already requested for this inquiry.');
        }

        $quantities = $inquiry->inquiry_quantities;
        $qty = is_array($quantities) ? implode(', ', $quantities) : ($inquiry->quantity ?: null);
        $size = $inquiry->finish_size ?: ($inquiry->open_size ?: null);

        $proposal = CrmProposal::create([
            'crm_email_id' => $inquiry->id,
            'subject' => 'Proposal — ' . ($inquiry->product_name ?: ($inquiry->subject ?: 'Inquiry')),
            'client_name' => $inquiry->client_name,
            'product_name' => $inquiry->product_name,
            'quantity' => $qty,
            'size' => $size,
            'status' => 'requested',
            'created_by' => $user->id,
        ]);

        return $canOpen
            ? redirect()->route('crm.proposals.show', $proposal->id)->with('success', 'Proposal requested. Assign a designer and add details below.')
            : redirect()->route('crm.inquiries.index')->with('success', 'Proposal requested — the design team will pick it up.');
    }

    private function authorizeProposal($user, CrmProposal $proposal)
    {
        if ($user->isAdmin()) return;
        if ((int) $proposal->assigned_designer_id === (int) $user->id) return;
        if ((int) $proposal->created_by === (int) $user->id) return;
        // A designer may view an unassigned proposal in the Active pool (to inspect before picking).
        if ($user->isDesigner() && empty($proposal->assigned_designer_id)) return;
        abort(403);
    }

    private function storeAttachment($file)
    {
        $name = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
        $dir = public_path('crm_proposals');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $file->move($dir, $name);
        return ['crm_proposals/' . $name, $file->getClientOriginalName()];
    }
}
