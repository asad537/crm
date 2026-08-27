<?php

namespace App\Http\Controllers\Crm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CrmEmail;
use App\CrmMessage;
use App\CrmStatusLog;
use App\CrmUser;
use App\Mail\ClientMessage;
use App\Services\HunterService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class EmailController extends Controller
{
    public function inquiriesIndex(Request $request)
    {
        $user = Auth::guard('crm')->user();
        if (!$user->isAdmin() && !$user->isSalesManager() && !$user->isSales() && !$user->isTeamLead()) abort(403);

        $query = CrmEmail::with([
                'designRequirementTicket.designer', 'inquiryAttachments',
                'estimateTickets.estimator', 'estimateTickets.teamLead', 'salesOrder.productionJob'
            ])
            ->whereNotNull('inquiry_quantities')
            ->latest();

        if ($user->isSales()) {
            // A sales agent can see inquiries they created as well as inquiries
            // assigned to them by a manager/admin.
            if (\Schema::hasColumn('crm_emails', 'created_by')) {
                $query->where(function ($ownerQuery) use ($user) {
                    $ownerQuery->where('created_by', $user->id)
                        ->orWhere('assigned_to', $user->id);
                });
            } else {
                $query->where('assigned_to', $user->id);
            }
        }
        if ($user->isTeamLead()) {
            $query->whereHas('estimateTickets', function ($estimateQuery) use ($user) {
                $estimateQuery->where(function ($reviewQuery) use ($user) {
                    $reviewQuery->where(function ($availableQuery) {
                        $availableQuery->where('status', 'team_lead_review')
                            ->whereNull('team_lead_id');
                    })->orWhere('team_lead_id', $user->id);
                });
            });
        }
        if ($request->filled('search')) {
            $search = trim($request->search);
            $inquiryId = preg_match('/^(?:INQ[-\s]*)?0*(\d+)$/i', $search, $matches)
                ? (int) $matches[1]
                : null;
            $query->where(function ($q) use ($search, $inquiryId) {
                $q->where('client_name', 'like', "%{$search}%")
                    ->orWhere('client_email', 'like', "%{$search}%")
                    ->orWhere('client_phone', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%");
                if ($inquiryId) {
                    $q->orWhere('id', $inquiryId);
                }
            });
        }
        if ($request->filled('workflow')) {
            if ($request->workflow === 'design') $query->where('estimate_status', 'awaiting_design');
            elseif ($request->workflow === 'estimate') $query->whereIn('estimate_status', ['pending', 'estimated']);
            elseif ($request->workflow === 'returned') $query->where('estimate_status', 'returned_to_sales');
            elseif ($request->workflow === 'sales') $query->whereHas('salesOrder', function ($q) {
                $q->whereNotIn('shipping_stage', ['order_completed', 'retention_follow_up', 'reorder_reminder'])
                    ->where('status', '!=', 'order_completed');
            });
            elseif ($request->workflow === 'completed') $query->whereHas('salesOrder', function ($q) {
                $q->where('shipping_stage', 'order_completed')->orWhere('status', 'order_completed');
            });
        }

        $inquiries = $query->paginate(20)->appends($request->all());
        return view('crm.emails.inquiries', compact('inquiries'));
    }

    public function createInquiryForm(Request $request)
    {
        $user = Auth::guard('crm')->user();
        if (!$user->isAdmin() && !$user->isSalesManager() && !$user->isSales()) abort(403);

        $prefillEmail = null;
        if ($request->filled('source_email')) {
            $request->validate([
                'source_email' => 'integer|min:1',
            ]);

            $prefillEmail = CrmEmail::findOrFail($request->input('source_email'));
            if ($user->isSales()
                && (int) $prefillEmail->created_by !== (int) $user->id
                && (int) $prefillEmail->assigned_to !== (int) $user->id) {
                abort(403);
            }
        }

        $savedFinishingGroups = $this->savedFinishingGroups();
        return view('crm.emails.create_inquiry', compact('savedFinishingGroups', 'prefillEmail'));
    }

    private function savedFinishingGroups()
    {
        $groups = [];

        if (\Schema::hasTable('crm_finishing_options')) {
            \App\CrmFinishingOption::orderBy('parent_sort_order')
                ->orderBy('parent_name')
                ->orderBy('child_sort_order')
                ->orderBy('child_name')
                ->get(['parent_name', 'child_name'])
                ->each(function ($option) use (&$groups) {
                    if (!isset($groups[$option->parent_name])) $groups[$option->parent_name] = [];
                    if (!in_array($option->child_name, $groups[$option->parent_name], true)) {
                        $groups[$option->parent_name][] = $option->child_name;
                    }
                });
        }

        return $groups;
    }

    public function storeFinishingOption(Request $request)
    {
        $user = Auth::guard('crm')->user();
        if (!$user || (!$user->isAdmin() && !$user->isSalesManager() && !$user->isSales())) abort(403);

        $data = $request->validate([
            'parent_name' => 'required|string|max:80',
            'child_name' => 'required|string|max:100',
        ]);
        $parent = trim(preg_replace('/\s+/', ' ', $data['parent_name']));
        $child = trim(preg_replace('/\s+/', ' ', $data['child_name']));
        if ($parent === '' || $child === '') {
            return response()->json(['message' => 'Parent category and child option are required.'], 422);
        }

        $option = \App\CrmFinishingOption::firstOrCreate(
            ['parent_name' => $parent, 'child_name' => $child],
            ['parent_sort_order' => 1000, 'child_sort_order' => 1000, 'created_by' => $user->id]
        );

        return response()->json([
            'success' => true,
            'parent_name' => $option->parent_name,
            'child_name' => $option->child_name,
            'value' => $option->parent_name.' — '.$option->child_name,
        ]);
    }

    public function index(Request $request)
    {
        $currentUser = Auth::guard('crm')->user();

        $query = CrmEmail::withCount([
            'messages as unread_incoming' => function ($q) {
                $q->where('is_read', false)->where('sender_type', 'client');
            }
        ])->where('is_spam', false)
          ->where('is_rejected', false)
          ->where(function($q) {
              $q->whereNull('source')->orWhere('source', '!=', 'imap');
          })
          ->orderBy('unread_incoming', 'desc')
          ->orderBy('updated_at', 'desc');

        // Non-admin and non-manager roles can ONLY see emails assigned to them
        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager()) {
            $query->where('assigned_to', $currentUser->id);
        }

        // Production roles (Designer, Prepress, Retention, QC, Shipping) see ONLY "Order Done" orders
        // These are confirmed orders that have entered the production pipeline
        if ($currentUser->isDesigner() || $currentUser->isPrepress() || $currentUser->isRetention() || $currentUser->isQC() || $currentUser->isShipping()) {
            $query->where('status', 'Order Done');
        }

        // Status Filter
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        // Product Filter
        if ($request->has('product') && $request->product != '') {
            $query->where('product_name', $request->product);
        }

        // Live Search (Keyword)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%")
                    ->orWhere('client_email', 'like', "%{$search}%")
                    ->orWhere('client_phone', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%")
                    ->orWhere('finish_size', 'like', "%{$search}%")
                    ->orWhere('open_size', 'like', "%{$search}%")
                    ->orWhere('stock', 'like', "%{$search}%")
                    ->orWhere('printing', 'like', "%{$search}%")
                    ->orWhere('csr_comment', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        // Source Filter
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->has('date_filter') && $request->date_filter != '') {
            $this->applyDateFilter($query, $request->date_filter);
        }

        // Custom Date Range (from daterangepicker — same as Orders page)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                \Carbon\Carbon::parse($request->start_date)->startOfDay(),
                \Carbon\Carbon::parse($request->end_date)->endOfDay(),
            ]);
        }

        // Unique products list — cached 10 min (was a full table scan on every request).
        $workspaceId = session('crm_workspace_id');
        $products = \Illuminate\Support\Facades\Cache::remember(
            "crm:emails:products:{$workspaceId}",
            600,
            function () {
                return CrmEmail::select('product_name')
                    ->distinct()
                    ->whereNotNull('product_name')
                    ->where('product_name', '!=', '')
                    ->pluck('product_name');
            }
        );

        // Pagination — for "all", cap at 500 rows so we don't run the count() twice or explode memory.
        $perPage = $request->input('per_page', 20);
        $emails = $query->paginate($perPage === 'all' ? 500 : (int) $perPage);

        // Assignable users list — cached 5 min (users don't change frequently).
        $users = [];
        if ($currentUser->canAssign()) {
            $cacheKey = "crm:emails:assignable:{$workspaceId}:".($currentUser->isSalesManager() ? 'sm' : 'ad').":{$currentUser->id}";
            $users = \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($currentUser, $workspaceId) {
                $uQuery = CrmUser::where('id', '!=', $currentUser->id)
                    ->whereHas('workspaces', function ($q) use ($workspaceId) {
                        $q->where('crm_workspaces.id', $workspaceId);
                    });
                if ($currentUser->isSalesManager()) {
                    $uQuery->where('role', 'sales');
                }
                return $uQuery->get(['id', 'name', 'role']);
            });
        }

        return view('crm.emails.index', compact('emails', 'products', 'users'));
    }

    public function spam(Request $request)
    {
        $currentUser = Auth::guard('crm')->user();
        $query = CrmEmail::where('is_spam', true)->orderBy('created_at', 'desc');

        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager()) {
            $query->where('assigned_to', $currentUser->id);
        }

        if ($request->has('date_filter') && $request->date_filter != '') {
            $this->applyDateFilter($query, $request->date_filter);
        }

        $emails = $query->paginate(20);
        return view('crm.emails.spam', compact('emails'));
    }

    public function rejected(Request $request)
    {
        $currentUser = Auth::guard('crm')->user();
        $query = CrmEmail::where('is_rejected', true)
            ->where('is_spam', false)
            ->whereDoesntHave('salesOrder')
            ->orderBy('created_at', 'desc');

        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager()) {
            $query->where('assigned_to', $currentUser->id);
        }

        if ($request->filled('date_filter')) {
            $this->applyDateFilter($query, $request->date_filter);
        }

        $emails = $query->paginate(20);

        return view('crm.emails.rejected', compact('emails'));
    }

    private function applyDateFilter($query, $filter)
    {
        if ($filter == 'today') {
            $query->whereDate('created_at', \Carbon\Carbon::today());
        } elseif ($filter == 'yesterday') {
            $query->whereDate('created_at', \Carbon\Carbon::yesterday());
        } elseif ($filter == 'this_week') {
            $query->whereBetween('created_at', [\Carbon\Carbon::now()->startOfWeek(), \Carbon\Carbon::now()->endOfWeek()]);
        }
    }

    public function show($id)
    {
        $currentUser = Auth::guard('crm')->user();
        $email = CrmEmail::with('messages.user')->findOrFail($id);

        // Sales agents may open inquiries they created or that were assigned to them.
        // Also allow access if current user is the assigned estimator for this email.
        $isAssignedEstimator = ($email->estimator_id == $currentUser->id);
        $isTeamLeadReviewer = $this->teamLeadCanAccessInquiry($currentUser, $email);
        $isSalesOwner = $currentUser->isSales()
            && ((int) $email->created_by === (int) $currentUser->id
                || (int) $email->assigned_to === (int) $currentUser->id);
        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager()
            && !$isSalesOwner && !$isAssignedEstimator && !$isTeamLeadReviewer) {
            return redirect()->route('crm.emails.index')->with('error', 'Access denied. This email is not assigned to you.');
        }

        // Update status to 'Viewed' if it's currently 'New'
        if ($email->status == 'New') {
            $oldStatus = 'New';
            $newStatus = 'Viewed';

            $email->update(['status' => $newStatus]);

            try {
                $existingLog = \App\CrmStatusLog::where('crm_email_id', $email->id)->first();
                if ($existingLog) {
                    $existingLog->update([
                        'user_name' => \Auth::guard('crm')->user()->name,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'created_at' => now(),
                    ]);
                } else {
                    \App\CrmStatusLog::create([
                        'crm_email_id' => $email->id,
                        'user_name' => \Auth::guard('crm')->user()->name,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                    ]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('CRM status log failed: ' . $e->getMessage());
            }
        }

        // Fetch Product Image & URL
        $productDetails = null;
        if ($email->product_name) {
            $productDetails = \Illuminate\Support\Facades\DB::table('products')
                ->where('prod_name', $email->product_name)
                ->select('prod_image', 'prod_url')
                ->first();
        }

        // Mark all incoming messages for this lead as read
        CrmMessage::where('crm_email_id', $email->id)
            ->where('sender_type', 'client')
            ->update(['is_read' => true]);

        $estimators = \App\CrmUser::inWorkspace(null, ['estimator'])->get();
        $assignableUsers = $currentUser->canAssign()
            ? $this->assignableUsersFor($currentUser)
            : collect();

        // The current estimate workflow stores prices in estimate_tickets/options rather
        // than the legacy columns on crm_emails. Make the latest completed estimate
        // available to the order controls on the lead detail page.
        $latestOrderEstimate = \App\EstimateTicket::with('options')
            ->where('crm_email_id', $email->id)
            ->whereIn('status', ['estimated', 'completed'])
            ->latest('id')
            ->first();

        // Internal admin <-> agent notes for this inquiry.
        $inquiryNotes = \App\CrmInquiryNote::where('crm_email_id', $email->id)
            ->orderBy('id', 'asc')
            ->get();

        return view('crm.emails.show', compact('email', 'productDetails', 'estimators', 'assignableUsers', 'latestOrderEstimate', 'inquiryNotes'));
    }

    public function attachment($inquiryId, $filename)
    {
        $currentUser = Auth::guard('crm')->user();
        $inquiry = CrmEmail::findOrFail($inquiryId);
        $isAssignedEstimator = ((int) $inquiry->estimator_id === (int) $currentUser->id);
        $isTeamLeadReviewer = $this->teamLeadCanAccessInquiry($currentUser, $inquiry);
        $isSalesOwner = $currentUser->isSales()
            && ((int) $inquiry->created_by === (int) $currentUser->id
                || (int) $inquiry->assigned_to === (int) $currentUser->id);
        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager()
            && !$isSalesOwner && !$isAssignedEstimator && !$isTeamLeadReviewer) {
            abort(403);
        }

        $relativePath = 'crm_attachments/'.$inquiry->id.'/'.basename($filename);
        foreach ($this->attachmentPathCandidates($relativePath) as $path) {
            if (is_file($path) && is_readable($path)) {
                return response()->file($path, [
                    'Content-Disposition' => 'inline; filename="'.basename($filename).'"',
                    'Cache-Control' => 'private, max-age=3600',
                ]);
            }
        }

        abort(404);
    }

    private function teamLeadCanAccessInquiry($user, CrmEmail $inquiry)
    {
        if (!$user || !$user->isTeamLead()) {
            return false;
        }

        return $inquiry->estimateTickets()
            ->where(function ($query) use ($user) {
                $query->where(function ($availableQuery) {
                    $availableQuery->where('status', 'team_lead_review')
                        ->whereNull('team_lead_id');
                })->orWhere('team_lead_id', $user->id);
            })
            ->exists();
    }

    public function markAsSpam($id)
    {
        $email = CrmEmail::findOrFail($id);
        $email->update(['is_spam' => true]);
        return redirect()->back()->with('success', 'Email marked as spam.');
    }

    public function markAsRejected(Request $request, $id)
    {
        $request->validate([
            'rejection_note' => 'required|string|max:1000',
        ]);

        $email = CrmEmail::whereDoesntHave('salesOrder')->findOrFail($id);
        $email->update([
            'is_rejected' => true,
            'is_spam' => false,
            'rejection_note' => $request->rejection_note,
            'rejected_at' => now(),
            'rejected_by' => Auth::guard('crm')->id(),
        ]);

        return redirect()->route('crm.emails.rejected')->with('success', 'Lead moved to Rejected Leads.');
    }

    public function restoreRejected($id)
    {
        $email = CrmEmail::findOrFail($id);
        $email->update([
            'is_rejected' => false,
            'rejection_note' => null,
            'rejected_at' => null,
            'rejected_by' => null,
        ]);

        return redirect()->route('crm.emails.rejected')->with('success', 'Lead restored to the inbox.');
    }

    public function markAsValid($id)
    {
        $email = CrmEmail::findOrFail($id);
        $email->update(['is_spam' => false]);
        return redirect()->back()->with('success', 'Email marked as valid.');
    }

    public function updateStatus(Request $request, $id)
    {
        $email = CrmEmail::findOrFail($id);
        $oldStatus = $email->status;
        $newStatus = $request->input('status');

        if ($oldStatus !== $newStatus) {
            $updateData = ['status' => $newStatus];

            // If marking as Order Done, save order details
            if ($newStatus === 'Order Done') {
                $request->validate([
                    'order_price' => 'nullable|numeric|min:0',
                    'order_quantity' => 'nullable|integer|min:1',
                    'order_notes' => 'nullable|string|max:1000',
                ]);
                $updateData['order_price'] = $request->input('order_price');
                $updateData['order_quantity'] = $request->input('order_quantity') ?: $email->quantity;
                $updateData['order_notes'] = $request->input('order_notes');
                $updateData['order_marked_at'] = now();
                $updateData['order_marked_by'] = \Auth::guard('crm')->user()->name;

                // Log workflow transition
                \App\Services\WorkflowService::logApproval($email, 'customer_quote_review', 'approved', 'Order Confirmed: Qty ' . $updateData['order_quantity'] . ' @ $' . $updateData['order_price']);
            }

            // If marking as Rejected, capture reason
            if ($newStatus === 'Rejected') {
                $request->validate([
                    'rejection_reason' => 'required|string|max:255',
                    'retention_agent_id' => 'nullable|exists:crm_users,id',
                    'follow_up_notes' => 'nullable|string|max:1000',
                ]);

                \App\CrmRejectionLog::updateOrCreate(
                    ['crm_email_id' => $email->id],
                    [
                        'rejection_reason' => $request->rejection_reason,
                        'retention_agent_id' => $request->retention_agent_id ?: \Auth::guard('crm')->id(),
                        'status' => 'pending',
                        'follow_up_notes' => $request->follow_up_notes
                    ]
                );

                // Log workflow transition
                \App\Services\WorkflowService::logApproval($email, 'customer_quote_review', 'rejected', 'Rejection Reason: ' . $request->rejection_reason);
            }

            $email->update($updateData);

            $existingLog = \App\CrmStatusLog::where('crm_email_id', $email->id)->first();

            if ($existingLog) {
                $existingLog->update([
                    'user_name' => \Auth::guard('crm')->user()->name,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'created_at' => now(),
                ]);
            } else {
                \App\CrmStatusLog::create([
                    'crm_email_id' => $email->id,
                    'user_name' => \Auth::guard('crm')->user()->name,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Status updated successfully.');
    }

    public function markQualified($id)
    {
        $email = CrmEmail::findOrFail($id);
        $email->update(['status' => 'Qualified Lead']);

        $existingLog = \App\CrmStatusLog::where('crm_email_id', $email->id)->first();
        if ($existingLog) {
            $existingLog->update([
                'user_name' => \Auth::guard('crm')->user()->name,
                'old_status' => 'New Lead',
                'new_status' => 'Qualified Lead',
                'created_at' => now(),
            ]);
        } else {
            \App\CrmStatusLog::create([
                'crm_email_id' => $email->id,
                'user_name' => \Auth::guard('crm')->user()->name,
                'old_status' => 'New Lead',
                'new_status' => 'Qualified Lead',
            ]);
        }

        return redirect()->route('crm.leads.index')->with('success', 'Lead marked as Qualified and moved to Quotes.');
    }

    public function updateProductName(Request $request, $id)
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
        ]);

        $email = CrmEmail::findOrFail($id);
        $email->update([
            'product_name' => $request->product_name,
            'subject' => $request->product_name // Also update subject to match, similar to createInquiry
        ]);

        return redirect()->back()->with('success', 'Product name updated successfully.');
    }

    public function updateProductSpecs(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'nullable|integer',
            'unit' => 'nullable|string|max:50',
            'length' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'stock' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'coating' => 'nullable|string|max:255',
            'lamination' => 'nullable|string|max:255',
            'die' => 'nullable|string|max:255',
            'glue' => 'nullable|string|max:255',
            'shipping_region' => 'nullable|string|max:255',
        ]);

        $email = CrmEmail::findOrFail($id);
        
        $fields = [
            'quantity', 'unit', 'length', 'width', 'height',
            'stock', 'color', 'coating', 'lamination', 'die', 'glue', 'shipping_region'
        ];

        $changedSpecs = is_array($email->changed_specs) ? $email->changed_specs : [];

        foreach ($fields as $field) {
            if ($request->has($field) && $email->$field != $request->$field) {
                if (!in_array($field, $changedSpecs)) {
                    $changedSpecs[] = $field;
                }
            }
        }

        $updateData = $request->only($fields);

        $oldCustomSpecs = is_array($email->custom_specs) ? $email->custom_specs : [];
        // Structured values (such as Finishing Options) are managed by their
        // dedicated inquiry UI and must survive edits in this legacy form.
        $customSpecs = array_filter($oldCustomSpecs, function ($value) {
            return is_array($value);
        });
        $keys = $request->input('custom_spec_keys', []);
        $values = $request->input('custom_spec_values', []);
        
        if (is_array($keys) && is_array($values)) {
            for ($i = 0; $i < count($keys); $i++) {
                $key = trim($keys[$i]);
                if (!empty($key)) {
                    $customSpecs[$key] = trim($values[$i] ?? '');
                }
            }
        }

        if ($oldCustomSpecs !== $customSpecs) {
            if (!in_array('custom_specs', $changedSpecs)) {
                $changedSpecs[] = 'custom_specs';
            }
        }

        $updateData['custom_specs'] = empty($customSpecs) ? null : $customSpecs;
        $updateData['changed_specs'] = $changedSpecs;

        $email->update($updateData);

        return back()->with('success', 'Production status updated successfully.');
    }

    public function requestEstimate(Request $request, $id)
    {
        $request->validate([
            'estimator_id' => 'required|exists:crm_users,id'
        ]);

        $email = CrmEmail::findOrFail($id);
        
        $email->update([
            'estimator_id' => $request->estimator_id,
            'estimate_status' => 'pending',
            'sales_agent_notes' => $request->notes,
            'status' => 'Qualified Lead',
        ]);

        return back()->with('success', 'Estimate requested successfully.');
    }

    public function submitEstimate(Request $request, $id)
    {
        $request->validate([
            'breakdown_names' => 'required|array',
            'breakdown_prices' => 'required|array',
            'tier_quantities' => 'required|array|size:3',
            'tier_prices' => 'required|array|size:3',
        ]);

        $email = CrmEmail::findOrFail($id);
        
        // Make sure only the assigned estimator can submit, or admin
        $currentUser = \Auth::guard('crm')->user();
        if (!$currentUser->isAdmin() && $currentUser->id != $email->estimator_id) {
            return back()->with('error', 'Unauthorized. Only the assigned estimator can submit pricing.');
        }

        $breakdown = [];
        $totalPrice = 0;
        $names = $request->input('breakdown_names', []);
        $prices = $request->input('breakdown_prices', []);
        
        $extraNames = $request->input('extra_names', []);
        $extraDetails = $request->input('extra_details', []);
        $extraPrices = $request->input('extra_prices', []);

        foreach ($names as $index => $name) {
            if (!empty($name) && isset($prices[$index]) && is_numeric($prices[$index])) {
                $price = (float)$prices[$index];
                $breakdown[] = [
                    'name' => $name,
                    'price' => $price
                ];
                $totalPrice += $price;
            }
        }
        
        foreach ($extraNames as $index => $name) {
            if (!empty($name) && isset($extraPrices[$index]) && is_numeric($extraPrices[$index])) {
                $price = (float)$extraPrices[$index];
                $breakdown[] = [
                    'name' => $name,
                    'detail' => $extraDetails[$index] ?? '',
                    'price' => $price
                ];
                $totalPrice += $price;
            }
        }
        
        // Calculate gross total
        $quantity = (float)($email->quantity ?: 1);
        $grossTotal = $totalPrice * $quantity;

        // Calculate waste material amount from percentage
        $wasteMaterialPercentage = max(0, (float)$request->input('waste_material_percentage', 0));
        $wasteMaterialAmount = $grossTotal * ($wasteMaterialPercentage / 100);

        // Add waste material to the estimate. Discounts are not set by estimators.
        $finalTotal = $grossTotal + $wasteMaterialAmount;
        if ($finalTotal < 0) {
            $finalTotal = 0;
        }

        $quantityOptions = [];
        $tierQuantities = $request->input('tier_quantities', []);
        $tierPrices = $request->input('tier_prices', []);

        for ($i = 0; $i < 3; $i++) {
            $tierQuantity = isset($tierQuantities[$i]) ? (int)$tierQuantities[$i] : 0;
            $tierPrice = isset($tierPrices[$i]) ? (float)$tierPrices[$i] : 0;

            if ($tierQuantity < 1 || $tierPrice < 0) {
                return back()->withInput()->with('error', 'Please enter valid quantity and price for all 3 estimate options.');
            }

            $quantityOptions[] = [
                'quantity' => $tierQuantity,
                'price' => round($tierPrice, 2),
                'unit_price' => $tierQuantity > 0 ? round($tierPrice / $tierQuantity, 4) : 0,
            ];
        }

        $finalTotal = $quantityOptions[0]['price'];

        $email->update([
            'estimated_price' => $finalTotal,
            'discount' => 0,
            'waste_material_percentage' => $wasteMaterialPercentage,
            'waste_material_amount' => $wasteMaterialAmount,
            'estimate_breakdown' => $breakdown,
            'estimate_quantity_options' => $quantityOptions,
            'estimator_notes' => $request->estimator_notes,
            'estimate_status' => 'estimated'
        ]);

        return back()->with('success', 'Estimate submitted successfully.');
    }

    public function approveEstimate(Request $request, $id)
    {
        $email = CrmEmail::findOrFail($id);
        
        $email->update([
            'estimate_status' => 'approved'
        ]);

        return back()->with('success', 'Estimate approved successfully.');
    }

    public function rejectEstimate(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string'
        ]);

        $email = CrmEmail::findOrFail($id);
        
        $newNotes = "Sales Agent Revision Request: " . $request->rejection_reason . "\n\n" . $email->estimator_notes;

        $email->update([
            'estimate_status' => 'change_requested',
            'estimator_notes' => $newNotes
        ]);

        return back()->with('success', 'Estimate sent back to the estimator for revisions.');
    }

    public function destroy($id)
    {
        if (!\Auth::guard('crm')->user()->isAdmin()) {
            return redirect()->back()->with('error', 'Only Admins can delete emails.');
        }

        $email = CrmEmail::findOrFail($id);
        $email->delete(); // Soft delete

        return redirect()->route('crm.emails.index')->with('success', 'Email deleted successfully.');
    }

    public function forward(Request $request, $id)
    {
        $request->validate(['forward_email' => 'required|email']);
        $email = CrmEmail::findOrFail($id);

        try {
            \Illuminate\Support\Facades\Mail::to($request->forward_email)->send(new \App\Mail\ForwardedInquiry($email));
            return redirect()->back()->with('success', 'Inquiry forwarded successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }
    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'message_body' => 'nullable|string',
            'attachments' => 'required_without:message_body|array',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,zip|max:10240', // Max 10MB per file
            'cc' => 'nullable|string',
            'bcc' => 'nullable|string',
            'email_subject' => 'nullable|string',
        ]);

        $inquiry = CrmEmail::findOrFail($id);
        $user = \Auth::guard('crm')->user();

        $storedAttachments = [];
        $absolutePaths = [];

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = 'crm_attachments/' . $inquiry->id;
                $absoluteDirectory = $this->webDocumentPath($path);
                if (!is_dir($absoluteDirectory)) {
                    mkdir($absoluteDirectory, 0755, true);
                }
                $movedFile = $file->move($absoluteDirectory, $filename);

                $storedAttachments[] = $path . '/' . $filename;
                $absolutePaths[] = $movedFile->getPathname();
            }
        }

        // 1. Create message record
        $message = CrmMessage::create([
            'crm_email_id' => $inquiry->id,
            'sender_type' => 'admin',
            'crm_user_id' => $user->id,
            'message_body' => $request->message_body,
            'attachments' => $storedAttachments,
            'is_read' => true,
        ]);

        // 2. Send email to client
        try {
            // IMPORTANT: config is cached in production, so env() returns NULL at runtime.
            // The default SMTP mailer (config/mail.php, built from .env at cache time) already
            // holds the correct support@ credentials — do NOT override it with env() here.
            // FROM is set explicitly inside the ClientMessage mailable via config('mail.from.*').

            $recipientEmail = trim($inquiry->client_email);
            if (empty($recipientEmail)) {
                throw new \Exception("Client email address is missing or invalid.");
            }

            $ccList = [];
            if ($request->filled('cc')) {
                $ccList = array_filter(array_map('trim', explode(',', $request->cc)));
            }
            $bccList = [];
            if ($request->filled('bcc')) {
                $bccList = array_filter(array_map('trim', explode(',', $request->bcc)));
            }

            $mailable = new ClientMessage($inquiry, $request->message_body, $absolutePaths, $user, $ccList, $bccList, $request->email_subject);
            Mail::to($recipientEmail)->send($mailable);
            \Log::info('CRM outgoing email accepted by SMTP transport.', [
                'crm_email_id' => $inquiry->id,
                'recipient' => $recipientEmail,
                'message_id' => $mailable->generatedMessageId ?? null,
                'attachment_count' => count($absolutePaths),
            ]);

            // SMTP delivery does not automatically create an IMAP Sent copy.
            // Append the exact MIME message so it also appears in Outlook/IMAP Sent.
            $sentMime = $this->buildSentMimeCopy(
                $inquiry,
                $request->message_body,
                $absolutePaths,
                $user,
                $recipientEmail,
                $ccList,
                $bccList,
                $request->email_subject,
                $mailable->generatedMessageId,
                $mailable->signatureHtml,
                config('mail.from.address') ?: config('mail.mailers.smtp.username')
            );
            $this->appendToImapSent($user, $sentMime);

            // Save the outgoing Message-ID so that client replies can be
            // correctly matched back to this lead (In-Reply-To / References).
            if (isset($mailable->generatedMessageId)) {
                $message->update(['message_id' => $mailable->generatedMessageId]);
            }

            // 3. Status Update & Logging
            if (true) {
                $oldStatus = $inquiry->status;
                $newStatus = 'Responded';
                
                $protectedStatuses = ['Qualified Lead', 'Order Done', 'Closed', 'Rejected'];

                // Auto update status and create log
                if ($oldStatus != $newStatus && !in_array($oldStatus, $protectedStatuses)) {
                    $inquiry->update(['status' => $newStatus]);

                    CrmStatusLog::create([
                        'crm_email_id' => $inquiry->id,
                        'user_name' => $user->name,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                    ]);
                }
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Message sent to client successfully.',
                    'data' => $message->load('user')
                ]);
            }

            return redirect()->back()->with('success', 'Message sent to client successfully.');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send email: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to send email: ' . $e->getMessage());
        }
    }

    public function getMessages(Request $request, $id)
    {
        $inquiry = CrmEmail::findOrFail($id);

        $messages = $inquiry->messages()
            ->with('user')
            ->where('id', '>', $request->last_id ?? 0)
            ->get();

        // Mark incoming messages as read since they are being fetched
        CrmMessage::where('crm_email_id', $inquiry->id)
            ->where('sender_type', 'client')
            ->update(['is_read' => true]);

        // Update status to 'Viewed' if it's currently 'New'
        if ($inquiry->status == 'New') {
            $inquiry->update(['status' => 'Viewed']);
            
            try {
                \App\CrmStatusLog::create([
                    'crm_email_id' => $inquiry->id,
                    'user_name' => \Auth::guard('crm')->user()->name ?? 'Admin',
                    'old_status' => 'New',
                    'new_status' => 'Viewed',
                ]);
            } catch (\Exception $e) {
                // Ignore log failure
            }
        }

        return response()->json($messages)->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    public function findSocialProfiles(Request $request, $id, HunterService $hunterService)
    {
        $email = CrmEmail::findOrFail($id);

        $profiles = $hunterService->findSocialProfiles($email->client_email, $email->client_name);

        if (!empty($profiles)) {
            $email->update([
                'linkedin_url' => $profiles['linkedin'] ?? $email->linkedin_url,
                'twitter_url' => $profiles['twitter'] ?? $email->twitter_url,
                'facebook_url' => $profiles['facebook'] ?? $email->facebook_url,
                'instagram_url' => $profiles['instagram'] ?? $email->instagram_url,
                'social_investigated_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Social media profiles found and updated.');
        }

        $email->update(['social_investigated_at' => now()]);
        return redirect()->back()->with('error', 'No social profiles found for this email.');
    }

    public function assign(Request $request, $id)
    {
        $currentUser = Auth::guard('crm')->user();

        if (!$currentUser->canAssign()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'assigned_to' => 'required|exists:crm_users,id',
            'note' => 'nullable|string|max:500',
        ]);

        $email = CrmEmail::findOrFail($id);

        // Role check: Sales Manager can only assign to sales agents
        $assignee = CrmUser::inWorkspace()->find($request->assigned_to);
        if (!$assignee) {
            return response()->json(['success' => false, 'message' => 'Selected user is not a member of this workspace.'], 422);
        }
        if ($currentUser->isSalesManager() && !$assignee->isSales()) {
            return response()->json(['success' => false, 'message' => 'Sales Manager can only assign to Sales Agents.'], 403);
        }

        // Update assignment on email
        $email->update([
            'assigned_to' => $assignee->id,
            'assigned_by' => $currentUser->id,
            'assigned_at' => now(),
        ]);

        // Log the assignment
        DB::table('crm_assignment_logs')->insert([
            'crm_email_id' => $email->id,
            'assigned_by' => $currentUser->id,
            'assigned_to' => $assignee->id,
            'note' => $request->note,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Also add to global status logs
        \App\CrmStatusLog::create([
            'crm_email_id' => $email->id,
            'user_name'    => $currentUser->name,
            'old_status'   => 'Assigned to',
            'new_status'   => $assignee->name,
        ]);

        $recipientEmail = $assignee->email;
        $assigneeName = $assignee->name;
        $assignedByName = $currentUser->name;
        $clientName = $email->client_name;
        $productName = $email->product_name;
        $assignmentNote = $request->note;
        $emailUrl = url("/crm/email/{$email->id}");

        app()->terminating(function () use ($recipientEmail, $assigneeName, $assignedByName, $clientName, $productName, $assignmentNote, $emailUrl) {
            try {
                // (L10) SwiftMailer removed — Symfony Mailer manages its own transport timeout.
                Mail::raw(
                    "Hi {$assigneeName},\n\n" .
                    "A new email has been assigned to you by {$assignedByName}.\n\n" .
                    "Client: {$clientName}\n" .
                    "Subject: {$productName}\n" .
                    ($assignmentNote ? "Note: {$assignmentNote}\n\n" : "\n") .
                    "View it here: {$emailUrl}\n\n" .
                    "— CRM Team",
                    function ($message) use ($recipientEmail) {
                        $message->to($recipientEmail)
                            ->subject('New Email Assigned to You');
                    }
                );
            } catch (\Exception $exception) {
                \Log::error('Assignment notification email failed: ' . $exception->getMessage());
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Email assigned to {$assignee->name} successfully.",
            'assignee_name' => $assignee->name,
            'assignee_role' => $assignee->role,
        ]);
    }

    public function assignmentLogs($id)
    {
        $email = CrmEmail::findOrFail($id);

        $logs = DB::table('crm_assignment_logs as l')
            ->join('crm_users as assignedBy', 'l.assigned_by', '=', 'assignedBy.id')
            ->join('crm_users as assignedTo', 'l.assigned_to', '=', 'assignedTo.id')
            ->where('l.crm_email_id', $id)
            ->select(
                'l.id',
                'l.note',
                'l.created_at',
                'assignedBy.name as assigned_by_name',
                'assignedBy.role as assigned_by_role',
                'assignedTo.name as assigned_to_name',
                'assignedTo.role as assigned_to_role'
            )
            ->orderBy('l.created_at', 'desc')
            ->get();

        return response()->json($logs);
    }

    public function getAssignableUsers()
    {
        $currentUser = Auth::guard('crm')->user();

        if (!$currentUser->canAssign()) {
            return response()->json([], 403);
        }

        return response()->json($this->assignableUsersFor($currentUser));
    }

    /**
     * Post an internal note/message on an inquiry (admin/CEO <-> assigned agent thread).
     */
    public function storeNote(Request $request, $id)
    {
        $currentUser = Auth::guard('crm')->user();
        $email = CrmEmail::findOrFail($id);

        // Anyone who can view this inquiry (admin, manager, estimator, team lead,
        // the owning/assigned sales agent) may post an internal note.
        $isAssignedEstimator = ((int) $email->estimator_id === (int) $currentUser->id);
        $isTeamLeadReviewer = $this->teamLeadCanAccessInquiry($currentUser, $email);
        $isSalesOwner = $currentUser->isSales()
            && ((int) $email->created_by === (int) $currentUser->id
                || (int) $email->assigned_to === (int) $currentUser->id);
        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager()
            && !$isSalesOwner && !$isAssignedEstimator && !$isTeamLeadReviewer) {
            return redirect()->route('crm.emails.index')->with('error', 'You cannot post notes on this inquiry.');
        }

        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        \App\CrmInquiryNote::create([
            'crm_email_id' => $email->id,
            'sender_id'    => $currentUser->id,
            'sender_name'  => $currentUser->name,
            'sender_role'  => method_exists($currentUser, 'getRoleLabel') ? $currentUser->getRoleLabel() : $currentUser->role,
            'body'         => $data['body'],
        ]);

        // Mirror the note into Team Chat so the counterpart is pinged there too.
        $counterpartId = null;
        if ($email->assigned_to && (int) $email->assigned_to !== (int) $currentUser->id) {
            // Management -> assigned agent
            $counterpartId = (int) $email->assigned_to;
        } else {
            // Sender is the assigned agent (or unassigned): reply reaches the last other participant.
            $lastOther = \App\CrmInquiryNote::where('crm_email_id', $email->id)
                ->where('sender_id', '!=', $currentUser->id)
                ->latest('id')->first();
            $counterpartId = $lastOther->sender_id ?? ($email->assigned_by ?: null);
        }

        if ($counterpartId && (int) $counterpartId !== (int) $currentUser->id) {
            $ref = 'Inquiry #' . $email->id . ($email->client_name ? ' — ' . $email->client_name : '');
            $link = route('crm.emails.show', $email->id);
            // Inquiry reference becomes a clickable link; no raw URL line.
            $chatBody = '📋 <a href="' . $link . '" target="_blank" style="color:#4f46e5;font-weight:700;text-decoration:underline;">'
                . e($ref) . '</a>' . "\n" . e($data['body']);
            try {
                \Illuminate\Support\Facades\DB::table('crm_internal_messages')->insert([
                    'workspace_id'   => $email->workspace_id ?: session('crm_workspace_id'),
                    'sender_id'      => $currentUser->id,
                    'receiver_id'    => $counterpartId,
                    'crm_email_id'   => $email->id,
                    'message_body'   => $chatBody,
                    'is_forwarded'   => 0,
                    'is_read'        => 0,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Inquiry note -> team chat mirror failed: ' . $e->getMessage());
            }
        }

        return redirect()->to(route('crm.emails.show', $email->id) . '#inquiryNotes')
            ->with('success', 'Note sent to the agent (also in Team Chat).');
    }

    private function assignableUsersFor($currentUser)
    {
        $roles = $currentUser->isSalesManager()
            ? ['sales']
            : ['sales_manager', 'sales'];

        return CrmUser::inWorkspace(null, $roles)
            ->where('crm_users.id', '!=', $currentUser->id)
            ->get(['crm_users.id', 'crm_users.name', 'crm_users.role'])
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->activeWorkspaceRole(),
                ];
            })
            ->values();
    }

    public function bulkAssign(Request $request)
    {
        $currentUser = Auth::guard('crm')->user();
        if (!$currentUser->canAssign()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'email_ids'   => 'required|array',
            'assigned_to' => 'required|exists:crm_users,id',
        ]);

        $assignee = CrmUser::inWorkspace()->find($request->assigned_to);
        if (!$assignee) {
            return response()->json(['success' => false, 'message' => 'Selected user is not a member of this workspace.'], 422);
        }
        if ($currentUser->isSalesManager() && !$assignee->isSales()) {
            return response()->json(['success' => false, 'message' => 'Sales Manager can only assign to Sales Agents.'], 403);
        }

        foreach ($request->email_ids as $id) {
            $email = CrmEmail::find($id);
            if (!$email) continue;

            $email->update([
                'assigned_to' => $assignee->id,
                'assigned_by' => $currentUser->id,
                'assigned_at' => now(),
            ]);

            DB::table('crm_assignment_logs')->insert([
                'crm_email_id' => $email->id,
                'assigned_by'  => $currentUser->id,
                'assigned_to'  => $assignee->id,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            \App\CrmStatusLog::create([
                'crm_email_id' => $email->id,
                'user_name'    => $currentUser->name,
                'old_status'   => 'Assigned to',
                'new_status'   => $assignee->name,
            ]);
        }

        return response()->json(['success' => true, 'message' => count($request->email_ids) . ' emails assigned successfully.']);
    }

    public function editInquiry($id)
    {
        $currentUser = \Auth::guard('crm')->user();
        if (!$currentUser || (!$currentUser->isAdmin() && !$currentUser->isSalesManager() && !$currentUser->isSales())) {
            abort(403);
        }
        $inquiry = CrmEmail::findOrFail($id);
        $savedFinishingGroups = $this->savedFinishingGroups();
        return view('crm.emails.edit_inquiry', compact('inquiry', 'savedFinishingGroups'));
    }

    public function updateInquiry(Request $request, $id)
    {
        $currentUser = \Auth::guard('crm')->user();
        if (!$currentUser || (!$currentUser->isAdmin() && !$currentUser->isSalesManager() && !$currentUser->isSales())) {
            abort(403);
        }

        $inquiry = CrmEmail::findOrFail($id);
        $isReturnedInquiry = $inquiry->estimateTickets()->where('status', 'returned_to_sales')->exists()
            || optional($inquiry->designRequirementTicket)->status === 'returned_to_sales';

        if (!$request->filled('quantities') && $request->filled('quantity')) {
            $request->merge(['quantities' => [$request->quantity]]);
        }
        $data = $request->validate([
            'client_name'  => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_phone' => 'nullable|string|max:50',
            'source' => 'required|string|in:website,call,walk_in,social_media,whatsapp,live_chat,email',
            'shipping_address' => 'nullable|string|max:2000',
            'inquiry_currency' => 'required|string|in:USD,AED,GBP,EUR,CAD,AUD,PKR,SAR,QAR',
            'product_name' => 'required|string|max:255',
            'printing' => 'nullable|string|max:255',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'finish_size' => 'nullable|string|max:255',
            'open_size' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:30',
            'stock' => 'nullable|string|max:255',
            'quantities' => 'required|array|min:1',
            'quantities.*' => 'required|integer|min:1|max:100000000',
            'price_offered' => 'nullable|numeric|min:0',
            'finishing_options' => 'nullable|array|max:50',
            'finishing_options.*' => 'string|max:150',
            'message'      => 'nullable|string',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'file|max:51200',
        ]);

        $quantities = array_values(array_unique(array_map('intval', $data['quantities'])));

        DB::transaction(function () use ($inquiry, $data, $quantities, $request, $currentUser) {
            $inquiry->update([
                'client_name' => $data['client_name'],
                'client_email' => $data['client_email'], 'client_phone' => $data['client_phone'] ?? null,
                'source' => $data['source'],
                'shipping_address' => $data['shipping_address'] ?? null,
                'invoice_currency' => $data['inquiry_currency'],
                'product_name' => $data['product_name'], 'subject' => $data['product_name'],
                'printing' => $data['printing'] ?? null,
                'length' => $data['length'] ?? null, 'width' => $data['width'] ?? null,
                'height' => $data['height'] ?? null, 'finish_size' => $data['finish_size'] ?? null,
                'open_size' => $data['open_size'] ?? null, 'unit' => $data['unit'] ?? null,
                'stock' => $data['stock'] ?? null, 'quantity' => $quantities[0],
                'inquiry_quantities' => $quantities, 'price_offered' => $data['price_offered'] ?? null,
                'custom_specs' => !empty($data['finishing_options'])
                    ? ['Finishing Options' => array_values(array_unique($data['finishing_options']))]
                    : null,
                'message' => $data['message'] ?? $inquiry->message,
            ]);
            
            foreach ($request->file('attachments', []) as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                if (in_array($extension, ['php','phtml','phar','exe','sh','bat','cmd','js'])) {
                    abort(422, 'Executable attachments are not allowed.');
                }
                $size = $file->getSize();
                $mime = $file->getClientMimeType();
                $originalName = $file->getClientOriginalName();
                $directory = public_path('uploads/inquiries');
                if (!is_dir($directory)) mkdir($directory, 0755, true);
                $filename = uniqid('inq_', true).'.'.$extension;
                $file->move($directory, $filename);
                \App\InquiryAttachment::create([
                    'crm_email_id' => $inquiry->id, 'uploaded_by' => $currentUser->id, 'stage' => 'sales',
                    'original_name' => $originalName, 'file_path' => 'uploads/inquiries/'.$filename,
                    'mime_type' => $mime, 'file_size' => $size,
                ]);
            }

            // If there's an active estimate ticket returned to sales, resubmit it
            $estimateTicket = $inquiry->estimateTickets()->where('status', 'returned_to_sales')->first();
            if ($estimateTicket) {
                $estimateTicket->update([
                    'client_name' => $inquiry->client_name,
                    'client_email' => $inquiry->client_email,
                    'product_style' => $inquiry->product_name,
                    'length' => $inquiry->length, 'width' => $inquiry->width, 'height' => $inquiry->height,
                    'unit' => $inquiry->unit ?: 'inches', 'stock' => $inquiry->stock,
                    'printing' => $inquiry->printing, 'finish_size' => $inquiry->finish_size,
                    'flat_size' => $inquiry->open_size,
                    'currency' => $inquiry->invoice_currency ?: 'USD',
                    'status' => 'open',
                    'return_note' => null,
                    'returned_to' => null,
                    'returned_by' => null,
                    'returned_at' => null,
                    'requested_by' => $currentUser->id,
                ]);

                $estimateTicket->options()->delete();
                foreach ($quantities as $quantity) {
                    $estimateTicket->options()->create(['quantity' => $quantity]);
                }
                
                $inquiry->update(['estimate_status' => 'pending']);
                
                if ($estimateTicket->lead) {
                    $estimateTicket->lead->update(['estimate_status' => 'pending']);
                }
            }

            // If there's an active design ticket returned to sales, resubmit it
            $designTicket = $inquiry->designRequirementTicket;
            if ($designTicket && $designTicket->status === 'returned_to_sales') {
                $designTicket->update([
                    'status' => 'open',
                    'return_note' => null,
                    'returned_to' => null,
                    'returned_by' => null,
                    'returned_at' => null,
                ]);
            }
        });

        return redirect()->route('crm.inquiries.index')->with(
            'success',
            $isReturnedInquiry ? 'Inquiry updated and resubmitted successfully.' : 'Inquiry changes saved successfully.'
        );
    }

    public function updateOfferPrice(Request $request, $id)
    {
        $user = \Auth::guard('crm')->user();
        if (!$user || (!$user->isAdmin() && !$user->isSalesManager() && !$user->isSales())) {
            abort(403);
        }

        $inquiry = CrmEmail::findOrFail($id);
        if ($user->isSales() && (int) $inquiry->assigned_to !== (int) $user->id) {
            abort(403, 'You can only update offers for inquiries assigned to you.');
        }

        $options = is_array($inquiry->estimate_quantity_options)
            ? array_values($inquiry->estimate_quantity_options)
            : [];
        if (empty($options)) {
            return back()->with('error', 'Team Lead approved prices are required before adding a Sales offer.');
        }

        $data = $request->validate([
            'offer_prices' => 'required|array',
            'offer_prices.*' => 'required|numeric|min:0',
        ]);

        $updatedOptions = [];
        foreach ($options as $index => $option) {
            $floor = (float) ($option['team_lead_price'] ?? $option['price'] ?? 0);
            $offer = round((float) ($data['offer_prices'][$index] ?? -1), 2);
            if ($offer + 0.00001 < $floor) {
                return back()->withErrors([
                    'offer_prices.'.$index => 'Offer for '.number_format((int) ($option['quantity'] ?? 0))
                        .' pcs cannot be lower than '.($inquiry->invoice_currency ?: 'USD').' '
                        .number_format($floor, 2).'.',
                ])->withInput();
            }
            $quantity = (int) ($option['quantity'] ?? 0);
            $option['team_lead_price'] = round($floor, 2);
            $option['price'] = $offer;
            $option['unit_price'] = $quantity > 0 ? round($offer / $quantity, 4) : 0;
            $updatedOptions[] = $option;
        }

        $inquiry->update([
            'estimate_quantity_options' => $updatedOptions,
            'price_offered' => $updatedOptions[0]['price'] ?? null,
            'estimated_price' => $updatedOptions[0]['price'] ?? null,
        ]);

        \App\Services\WorkflowService::logApproval(
            $inquiry,
            'sales_offer_updated',
            'approved',
            'Sales Agent '.$user->name.' updated customer offer prices without changing Team Lead minimum prices.'
        );

        return back()->with('success', 'Sales offer prices saved. The client estimate PDF will use these prices.');
    }

    public function createInquiry(Request $request)
    {
        $currentUser = \Auth::guard('crm')->user();
        if (!$currentUser || (!$currentUser->isAdmin() && !$currentUser->isSalesManager() && !$currentUser->isSales())) {
            abort(403);
        }

        if (!$request->filled('quantities') && $request->filled('quantity')) {
            $request->merge(['quantities' => [$request->quantity]]);
        }
        $data = $request->validate([
            'client_name'  => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_phone' => 'nullable|string|max:50',
            'shipping_address' => 'nullable|string|max:2000',
            'csr_comment' => 'nullable|string|max:3000',
            'inquiry_currency' => 'required|string|in:USD,AED,GBP,EUR,CAD,AUD,PKR,SAR,QAR',
            'website' => 'nullable|string|max:100',
            'product_name' => 'required|string|max:255',
            'printing' => 'nullable|string|max:255',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'finish_size' => 'nullable|string|max:255',
            'open_size' => 'nullable|string|max:255',
            'unit' => 'nullable|string|max:30',
            'stock' => 'nullable|string|max:255',
            'quantities' => 'required|array|min:1',
            'quantities.*' => 'required|integer|min:1|max:100000000',
            'price_offered' => 'nullable|numeric|min:0',
            'finishing_options' => 'nullable|array|max:50',
            'finishing_options.*' => 'string|max:150',
            'message'      => 'nullable|string',
            'source'       => 'required|string|in:website,call,walk_in,social_media,whatsapp,live_chat,email',
            'inquiry_date' => 'required|date',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'file|max:51200',
            'route_to' => 'nullable|in:estimator,designer',
        ]);

        $inquiryDate = \Carbon\Carbon::parse($request->inquiry_date)
            ->setTimeFrom(now());
        $quantities = array_values(array_unique(array_map('intval', $data['quantities'])));
        // Al Massa sales pick the route explicitly; other workspaces keep the open-size rule.
        $toDesign = isset($data['route_to'])
            ? $data['route_to'] === 'designer'
            : empty($data['open_size']);

        $inquiry = DB::transaction(function () use ($request, $data, $currentUser, $inquiryDate, $quantities, $toDesign) {
            $attributes = [
                'source' => $data['source'], 'client_name' => $data['client_name'],
                'client_email' => $data['client_email'], 'client_phone' => $data['client_phone'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'csr_comment' => $data['csr_comment'] ?? null, 'website' => $data['website'] ?? null,
                'invoice_currency' => $data['inquiry_currency'],
                'product_name' => $data['product_name'], 'subject' => $data['product_name'],
                'printing' => $data['printing'] ?? null,
                'length' => $data['length'] ?? null, 'width' => $data['width'] ?? null,
                'height' => $data['height'] ?? null, 'finish_size' => $data['finish_size'] ?? null,
                'open_size' => $data['open_size'] ?? null, 'unit' => $data['unit'] ?? null,
                'stock' => $data['stock'] ?? null, 'quantity' => $quantities[0],
                'inquiry_quantities' => $quantities, 'price_offered' => $data['price_offered'] ?? null,
                'custom_specs' => !empty($data['finishing_options'])
                    ? ['Finishing Options' => array_values(array_unique($data['finishing_options']))]
                    : null,
                'message' => ($data['message'] ?? null) ?: 'Manually created inquiry.', 'status' => 'New',
                'assigned_to' => $currentUser->id, 'assigned_by' => $currentUser->id, 'assigned_at' => now(),
                'estimate_status' => $toDesign ? 'awaiting_design' : 'pending',
            ];
            if (\Schema::hasColumn('crm_emails', 'created_by')) {
                $attributes['created_by'] = $currentUser->id;
            }
            $inquiry = new CrmEmail($attributes);
            $inquiry->created_at = $inquiryDate;
            $inquiry->save();

            DB::table('crm_assignment_logs')->insert([
                'crm_email_id' => $inquiry->id, 'assigned_by' => $currentUser->id,
                'assigned_to' => $currentUser->id, 'note' => 'Auto-assigned upon manual creation.',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            \App\CrmStatusLog::create([
                'crm_email_id' => $inquiry->id, 'user_name' => $currentUser->name,
                'old_status' => 'Created', 'new_status' => $toDesign ? 'Waiting for Design' : 'Waiting for Estimate',
            ]);

            foreach ($request->file('attachments', []) as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                if (in_array($extension, ['php','phtml','phar','exe','sh','bat','cmd','js'])) {
                    abort(422, 'Executable attachments are not allowed.');
                }
                $size = $file->getSize();
                $mime = $file->getClientMimeType();
                $originalName = $file->getClientOriginalName();
                $directory = public_path('uploads/inquiries');
                if (!is_dir($directory)) mkdir($directory, 0755, true);
                $filename = uniqid('inq_', true).'.'.$extension;
                $file->move($directory, $filename);
                \App\InquiryAttachment::create([
                    'crm_email_id' => $inquiry->id, 'uploaded_by' => $currentUser->id, 'stage' => 'sales',
                    'original_name' => $originalName, 'file_path' => 'uploads/inquiries/'.$filename,
                    'mime_type' => $mime, 'file_size' => $size,
                ]);
            }

            if ($toDesign) {
                \App\DesignRequirementTicket::create([
                    'ticket_number' => $inquiry->workflow_number,
                    'crm_email_id' => $inquiry->id, 'requested_by' => $currentUser->id,
                    'status' => 'new', 'quantities' => $quantities, 'unit' => $data['unit'] ?? null,
                ]);
            } else {
                $this->createAutomaticEstimate($inquiry, $quantities, $currentUser->id);
            }
            return $inquiry;
        });

        return redirect()->route('crm.inquiries.index')
            ->with('success', $toDesign ? 'Inquiry saved and sent to Design.' : 'Inquiry saved and sent to Estimator.');
    }

    private function createAutomaticEstimate(CrmEmail $inquiry, array $quantities, $requestedBy)
    {
        $estimator = CrmUser::inWorkspace(null, ['estimator'])->orderBy('id')->first();
        if (!$estimator) abort(422, 'No estimator is configured for this project.');

        $attachments = $inquiry->inquiryAttachments()->pluck('file_path')->all();
        $ticket = \App\EstimateTicket::create([
            'ticket_number' => $inquiry->workflow_number,
            'crm_email_id' => $inquiry->id, 'client_name' => $inquiry->client_name,
            'client_email' => $inquiry->client_email, 'product_style' => $inquiry->product_name,
            'length' => $inquiry->length, 'width' => $inquiry->width, 'height' => $inquiry->height,
            'unit' => $inquiry->unit ?: 'inches', 'stock' => $inquiry->stock,
            'printing' => $inquiry->printing, 'finish_size' => $inquiry->finish_size,
            'flat_size' => $inquiry->open_size,
            'requirements' => trim($inquiry->message."\n".$this->finishingRequirements($inquiry)),
            'colors' => $inquiry->color, 'coating' => $inquiry->coating,
            'lamination' => $inquiry->lamination, 'die_cutting' => $inquiry->die,
            'gluing' => $inquiry->glue, 'shipping_region' => $inquiry->shipping_region,
            'currency' => $inquiry->invoice_currency ?: 'USD',
            'attachments' => $attachments, 'estimator_id' => null,
            'requested_by' => $requestedBy, 'status' => 'pending',
        ]);
        foreach ($quantities as $quantity) $ticket->options()->create(['quantity' => $quantity]);
        $inquiry->update(['estimator_id' => null, 'estimate_status' => 'pending']);
        return $ticket;
    }

    private function finishingRequirements(CrmEmail $inquiry)
    {
        $customSpecs = is_array($inquiry->custom_specs) ? $inquiry->custom_specs : [];
        $options = isset($customSpecs['Finishing Options']) && is_array($customSpecs['Finishing Options'])
            ? $customSpecs['Finishing Options']
            : [];
        return $options ? 'Finishing Options: '.implode(', ', $options) : '';
    }

    public function calculateCost(Request $request, $id)
    {
        $email = CrmEmail::findOrFail($id);

        $request->validate([
            'width' => 'required|numeric|min:0.1',
            'height' => 'required|numeric|min:0.1',
            'length' => 'required|numeric|min:0.1',
            'quantity' => 'required|integer|min:1',
            'material' => 'required|string',
            'colors' => 'required|string',
            'lamination' => 'required|string',
            'gluing' => 'required|string',
            'shipping_region' => 'required|string',
        ]);

        $costingEngine = new \App\Services\CostingEngine();
        $breakdown = $costingEngine->calculate($request->all());

        return response()->json([
            'success' => true,
            'breakdown' => $breakdown
        ]);
    }

    public function updateRetentionLog(Request $request, $id)
    {
        $currentUser = \Auth::guard('crm')->user();
        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager() && !$currentUser->isRetention()) {
            return back()->with('error', 'Only the Customer Retention Team is authorized to manage retention logs.');
        }

        $email = CrmEmail::findOrFail($id);
        $log = \App\CrmRejectionLog::where('crm_email_id', $email->id)->firstOrFail();

        $request->validate([
            'status' => 'required|in:pending,offered_options,resolved_interested,lost_quote',
            'offered_options' => 'nullable|array',
            'follow_up_notes' => 'nullable|string|max:1000',
        ]);

        $log->update([
            'status' => $request->status,
            'offered_options' => $request->offered_options,
            'follow_up_notes' => $request->follow_up_notes,
        ]);

        // If customer is interested again, re-open the lead status to "Viewed"
        if ($request->status === 'resolved_interested') {
            $email->update(['status' => 'Viewed']);
            \App\Services\WorkflowService::logApproval($email, 'customer_quote_review', 'pending', 'Retention follow-up: Customer interested again');
        } elseif ($request->status === 'lost_quote') {
            $email->update(['status' => 'Closed']);
            \App\Services\WorkflowService::logApproval($email, 'customer_quote_review', 'rejected', 'Retention follow-up: Lost quote');
        }

        return redirect()->back()->with('success', 'Retention follow-up log updated successfully.');
    }

    public function uploadProofRevision(Request $request, $id)
    {
        $currentUser = \Auth::guard('crm')->user();
        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager() && !$currentUser->isDesigner()) {
            return back()->with('error', 'Only the Design Department is authorized to manage proofs.');
        }

        $request->validate([
            'file' => 'required|file|max:15360', // 15MB max
            'feedback_notes' => 'nullable|string'
        ]);

        try {
            $email = CrmEmail::findOrFail($id);
            $file = $request->file('file');
            
            // Increment version number automatically
            $latestVersion = \App\ProofRevision::where('crm_email_id', $email->id)
                ->max('version_number') ?: 0;
            $nextVersion = $latestVersion + 1;

            $filename = time() . '_proof_v' . $nextVersion . '_' . preg_replace('/[^A-Za-z0-9.\-]/', '_', $file->getClientOriginalName());
            
            $uploadDir = 'uploads/proofs/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $file->move($uploadDir, $filename);

            $proof = \App\ProofRevision::create([
                'crm_email_id' => $email->id,
                'version_number' => $nextVersion,
                'file_path' => 'uploads/proofs/' . $filename,
                'feedback_notes' => $request->feedback_notes,
                'status' => 'pending',
                'uploaded_by' => \Auth::guard('crm')->id()
            ]);

            // Log workflow transition
            \App\Services\WorkflowService::logApproval($email, 'artwork_proof_review', 'pending', 'Uploaded Proof version ' . $nextVersion);

            return back()->with('success', 'Proof version ' . $nextVersion . ' uploaded successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to upload proof: ' . $e->getMessage());
        }
    }

    public function updateProofStatus(Request $request, $id)
    {
        $currentUser = \Auth::guard('crm')->user();
        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager() && !$currentUser->isDesigner()) {
            return back()->with('error', 'Only the Design Department is authorized to update proof status.');
        }

        $request->validate([
            'status' => 'required|in:approved,revision_needed',
            'feedback_notes' => 'nullable|string'
        ]);

        try {
            $proof = \App\ProofRevision::findOrFail($id);
            $proof->update([
                'status' => $request->status,
                'feedback_notes' => $request->feedback_notes ?: $proof->feedback_notes
            ]);

            $statusMap = [
                'approved' => 'approved',
                'revision_needed' => 'revision_requested'
            ];

            // Log workflow transition
            if ($proof->email) {
                \App\Services\WorkflowService::logApproval($proof->email, 'artwork_proof_review', $statusMap[$request->status], 'Proof v' . $proof->version_number . ' feedback: ' . $request->feedback_notes);
            }

            return back()->with('success', 'Proof status updated to ' . str_replace('_', ' ', $request->status));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update proof status: ' . $e->getMessage());
        }
    }

    public function submitQualityControl(Request $request, $id)
    {
        $currentUser = \Auth::guard('crm')->user();
        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager() && !$currentUser->isQC()) {
            return back()->with('error', 'Only the Quality Control (QC) Department is authorized to submit scorecards.');
        }

        try {
            $email = CrmEmail::findOrFail($id);
            
            $photoPath = null;
            if ($request->hasFile('photo_defect')) {
                $file = $request->file('photo_defect');
                $filename = time() . '_qc_defect_' . preg_replace('/[^A-Za-z0-9.\-]/', '_', $file->getClientOriginalName());
                $uploadDir = 'uploads/qc/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $file->move($uploadDir, $filename);
                $photoPath = 'uploads/qc/' . $filename;
            }

            $qc = \App\QualityControl::create([
                'crm_email_id' => $email->id,
                'qc_agent_id' => \Auth::guard('crm')->id(),
                'dimension_passed' => $request->has('dimension_passed'),
                'fold_color_passed' => $request->has('fold_color_passed'),
                'quantity_passed' => $request->has('quantity_passed'),
                'glue_strength_passed' => $request->has('glue_strength_passed'),
                'barcode_scan_passed' => $request->has('barcode_scan_passed'),
                'packaging_passed' => $request->has('packaging_passed'),
                'notes' => $request->notes,
                'photo_defect_path' => $photoPath,
            ]);

            // Determine if QC passed overall
            $passed = $qc->dimension_passed && $qc->fold_color_passed && $qc->quantity_passed && $qc->glue_strength_passed && $qc->barcode_scan_passed && $qc->packaging_passed;

            if ($passed) {
                // Log workflow transition
                \App\Services\WorkflowService::logApproval($email, 'quality_control', 'approved', 'QC checks passed successfully.');
                return back()->with('success', 'Quality Control check submitted. Order passed QC.');
            } else {
                // Log workflow transition
                \App\Services\WorkflowService::logApproval($email, 'quality_control', 'rejected', 'QC checks failed: ' . $request->notes);
                return back()->with('error', 'Quality Control check submitted. Order failed one or more QC criteria.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to submit QC: ' . $e->getMessage());
        }
    }

    public function updateProductionStatus(Request $request, $id)
    {
        $currentUser = \Auth::guard('crm')->user();
        $newStatus = $request->input('production_status');

        // Determine transition authorization based on flowchart role
        $isAuthorized = false;
        if ($currentUser->isAdmin() || $currentUser->isSalesManager()) {
            $isAuthorized = true;
        } elseif ($currentUser->isDesigner() && in_array($newStatus, ['pending_design'])) {
            $isAuthorized = true;
        } elseif ($currentUser->isPrepress() && in_array($newStatus, ['in_production', 'qc_check'])) {
            $isAuthorized = true;
        } elseif ($currentUser->isQC() && in_array($newStatus, ['qc_check', 'produced'])) {
            $isAuthorized = true;
        } elseif ($currentUser->isShipping() && in_array($newStatus, ['produced', 'shipping', 'shipped', 'delivered'])) {
            $isAuthorized = true;
        }

        if (!$isAuthorized) {
            return back()->with('error', 'You are not authorized to transition the order to the "' . str_replace('_', ' ', $newStatus) . '" stage.');
        }

        $request->validate([
            'production_status' => 'required|string',
        ]);

        try {
            $email = CrmEmail::findOrFail($id);

            // Enforce QC pass block before moving orders to Shipped/Delivered
            if (in_array($newStatus, ['shipping', 'shipped', 'delivered'])) {
                $latestQc = \App\QualityControl::where('crm_email_id', $email->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                $qcPassed = $latestQc && $latestQc->dimension_passed && $latestQc->fold_color_passed && $latestQc->quantity_passed && $latestQc->glue_strength_passed && $latestQc->barcode_scan_passed && $latestQc->packaging_passed;

                if (!$qcPassed) {
                    return back()->with('error', 'Cannot ship order. This order has not passed the Quality Control (QC) checks yet.');
                }
            }

            $email->update(['production_status' => $newStatus]);
            
            // Log workflow transition if status changed
            \App\Services\WorkflowService::logApproval($email, 'production_pipeline', 'approved', 'Production status updated to ' . $newStatus);

            return back()->with('success', 'Production status updated to ' . $newStatus);
        } catch (\Exception $e) {
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    private function webDocumentPath($relativePath)
    {
        $documentRoot = request()->server('DOCUMENT_ROOT');
        $root = $documentRoot && is_dir($documentRoot) ? $documentRoot : public_path();
        return rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.ltrim($relativePath, DIRECTORY_SEPARATOR);
    }

    private function attachmentPathCandidates($relativePath)
    {
        $relativePath = ltrim($relativePath, DIRECTORY_SEPARATOR);
        $documentRoot = request()->server('DOCUMENT_ROOT');

        return array_unique(array_filter([
            $documentRoot ? rtrim($documentRoot, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$relativePath : null,
            public_path($relativePath),
            base_path('public/'.$relativePath),
            base_path('public_html/'.$relativePath),
            dirname(base_path()).'/public_html/'.$relativePath,
        ]));
    }

    private function appendToImapSent($user, $mimeMessage)
    {
        if (!$mimeMessage || !function_exists('imap_open')) return;

        // config() (not env) — env is NULL when config is cached in production.
        $username = $user->email_user ?: config('crmimap.username');
        $password = $user->email_pass ?: config('crmimap.password');
        $host = $user->imap_host ?: config('crmimap.host', 'imap.hostinger.com');
        $port = $user->imap_port ?: config('crmimap.port', 993);
        $encryption = $user->imap_encryption ?: config('crmimap.encryption', 'ssl');
        if (!$username || !$password) return;

        $root = '{' . $host . ':' . $port . '/imap/' . $encryption . '/novalidate-cert}';
        imap_errors();
        $connection = @imap_open($root . 'INBOX', $username, $password);
        if (!$connection) {
            \Log::warning('Unable to open IMAP while saving outgoing email to Sent.', ['user_id' => $user->id]);
            return;
        }

        try {
            $candidates = [];
            $mailboxes = @imap_getmailboxes($connection, $root, '*') ?: [];
            foreach ($mailboxes as $mailbox) {
                $decoded = imap_utf7_decode($mailbox->name);
                $folder = preg_replace('/^\{[^}]+\}/', '', $decoded);
                $normalized = strtolower(trim($folder));
                if (preg_match('/(^|[.\/])sent( items| mail)?$/', $normalized)) {
                    $candidates[] = $mailbox->name;
                }
            }

            $candidates = array_unique(array_merge($candidates, [
                $root . 'Sent',
                $root . 'INBOX.Sent',
                $root . 'Sent Items',
            ]));

            $saved = false;
            foreach ($candidates as $mailbox) {
                if (@imap_append($connection, $mailbox, $mimeMessage, '\\Seen')) {
                    $saved = true;
                    break;
                }
            }

            if (!$saved) {
                \Log::warning('Outgoing email delivered but could not be copied to IMAP Sent.', ['user_id' => $user->id]);
            }
        } catch (\Exception $e) {
            \Log::warning('IMAP Sent copy failed: ' . $e->getMessage(), ['user_id' => $user->id]);
        } finally {
            imap_close($connection);
            imap_errors();
        }
    }

    private function buildSentMimeCopy($inquiry, $messageBody, array $attachmentPaths, $user, $recipientEmail, array $ccList, array $bccList, $customSubject, $messageId, $signatureHtml, $fromEmail)
    {
        $subject = $customSubject ?: ('Re: ' . ($inquiry->subject ?: 'Your Inquiry - My Box Printing'));
        $html = view('email.crm_client_message', [
            'inquiry' => $inquiry,
            'messageBody' => $messageBody,
            'agentUser' => $user,
            'signatureHtml' => $signatureHtml,
        ])->render();

        // L10 uses Symfony Mailer (SwiftMailer was removed). Build the raw MIME with Symfony Mime.
        $email = (new \Symfony\Component\Mime\Email())
            ->from(new \Symfony\Component\Mime\Address($fromEmail, (string) ($user->name ?? '')))
            ->to($recipientEmail)
            ->subject($subject)
            ->html($html);

        foreach ($ccList as $cc) {
            if (!empty($cc)) $email->addCc($cc);
        }
        foreach ($bccList as $bcc) {
            if (!empty($bcc)) $email->addBcc($bcc);
        }
        if ($messageId) {
            // Message-ID header expects the value without the surrounding angle brackets.
            $email->getHeaders()->addIdHeader('Message-ID', trim($messageId, '<>'));
        }

        foreach ($attachmentPaths as $path) {
            if (is_file($path)) {
                $email->attachFromPath($path);
            }
        }

        return $email->toString();
    }
}
