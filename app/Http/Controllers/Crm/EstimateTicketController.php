<?php

namespace App\Http\Controllers\Crm;
use Illuminate\Support\Str;

use App\CrmEmail;
use App\CrmUser;
use App\EstimateTicket;
use App\CrmEstimationRateMatrix;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EstimateTicketController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('crm')->user();
        $tab = $request->input('tab', 'active');
        $query = EstimateTicket::with(['estimator', 'requester', 'teamLead'])->withCount('options')->latest();

        if ($user->isSales()) {
            $query->where('requested_by', $user->id);
        }

        if ($user->isTeamLead()) {
            if ($tab === 'history') $query->whereIn('status', ['estimated', 'completed'])->where('team_lead_id', $user->id);
            elseif ($tab === 'mine') $query->where('status', 'team_lead_open')->where('team_lead_id', $user->id);
            else $query->where('status', 'team_lead_review')->whereNull('team_lead_id');
        } elseif ($tab === 'approvals') {
            $query->where('status', 'owner_review');
        } elseif ($tab === 'history') {
            $query->whereIn('status', $user->isEstimator()
                ? ['team_lead_review', 'team_lead_open', 'owner_review', 'estimated', 'completed', 'returned_to_design', 'returned_to_sales']
                : ['owner_review', 'estimated', 'completed', 'returned_to_sales']);
            if ($user->isEstimator()) $query->where('estimator_id', $user->id);
        } elseif ($tab === 'mine') {
            $query->whereIn('status', ['open', 'revision_requested']);
            if ($user->isEstimator()) $query->where('estimator_id', $user->id);
            // Al Massa admins/owners see every in-progress (open) ticket for oversight.
            elseif ($user->isAdmin() && $this->isAlMassaWorkspace()) { /* all open/revision */ }
            else $query->whereRaw('1 = 0');
        } else {
            $query->where('status', 'pending');
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $inquiryId = preg_match('/^(?:INQ[-\s]*)?0*(\d+)$/i', $search, $matches)
                ? (int) $matches[1]
                : null;
            $query->where(function ($q) use ($search, $user, $inquiryId) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%")
                    ->orWhere('product_style', 'like', "%{$search}%");
                if ($inquiryId) {
                    $q->orWhere('crm_email_id', $inquiryId);
                }
                if (!$user->isEstimator()) {
                    $q->orWhere('client_email', 'like', "%{$search}%");
                }
            });
        }

        // Tab counts — combined into ONE grouped query (was 4 separate SELECT COUNT queries).
        $countBase = EstimateTicket::query();
        if ($user->isSales()) $countBase->where('requested_by', $user->id);
        if ($user->isTeamLead()) $countBase->where('team_lead_id', $user->id);
        elseif ($user->isEstimator()) $countBase->where('estimator_id', $user->id);

        $rawCounts = (clone $countBase)
            ->selectRaw('status, team_lead_id, COUNT(*) as c')
            ->groupBy('status', 'team_lead_id')
            ->get();

        $sumBy = function ($predicate) use ($rawCounts) {
            return (int) $rawCounts->filter($predicate)->sum('c');
        };

        if ($user->isTeamLead()) {
            $activeCount   = $sumBy(fn ($r) => $r->status === 'team_lead_review' && $r->team_lead_id === null);
            $mineCount     = $sumBy(fn ($r) => $r->status === 'team_lead_open');
            $historyStatuses = ['team_lead_review', 'team_lead_open', 'owner_review', 'estimated', 'completed', 'returned_to_design', 'returned_to_sales'];
            $historyCount  = $sumBy(fn ($r) => in_array($r->status, $historyStatuses, true));
        } else {
            $activeCount   = $sumBy(fn ($r) => $r->status === 'pending');
            $mineCount     = $sumBy(fn ($r) => in_array($r->status, ['open', 'revision_requested'], true));
            $historyStatuses = $user->isEstimator()
                ? ['team_lead_review', 'team_lead_open', 'owner_review', 'estimated', 'completed', 'returned_to_design', 'returned_to_sales']
                : ['owner_review', 'estimated', 'completed', 'returned_to_sales'];
            $historyCount  = $sumBy(fn ($r) => in_array($r->status, $historyStatuses, true));
        }
        $approvalsCount = $sumBy(fn ($r) => $r->status === 'owner_review');

        $tickets = $query->paginate(15)->appends($request->all());
        return view('crm.estimate_tickets.index', compact('tickets', 'tab', 'activeCount', 'mineCount', 'historyCount', 'approvalsCount'));
    }

    public function create()
    {
        $this->requireSalesRole();
        $workspaceId = \App\Support\CrmWorkspaceContext::id();
        $estimators = CrmUser::inWorkspace(null, ['estimator'])->orderBy('name')->get();
        $leads = CrmEmail::where('workspace_id', $workspaceId)
            ->where('is_spam', false)->where('is_rejected', false)
            ->latest()->limit(100)->get(['id', 'client_name', 'client_email', 'product_name', 'length', 'width', 'height', 'unit', 'stock', 'color', 'coating']);
        return view('crm.estimate_tickets.create', compact('estimators', 'leads'));
    }

    public function store(Request $request)
    {
        $this->requireSalesRole();
        $workspaceId = \App\Support\CrmWorkspaceContext::id();
        $data = $request->validate([
            'crm_email_id' => ['nullable', Rule::exists('crm_emails', 'id')->where('workspace_id', $workspaceId)],
            'client_name' => 'required|string|max:255',
            'client_email' => 'nullable|email|max:255', 'product_style' => 'required|string|max:255',
            'stock' => 'nullable|string|max:255', 'printing' => 'nullable|string|max:255',
            'finish_size' => 'nullable|string|max:255', 'flat_size' => 'nullable|string|max:255',
            'shipping' => 'nullable|string|max:255', 'weight' => 'nullable|string|max:255',
            'estimator_id' => 'required|exists:crm_users,id', 'quantities' => 'required|array|min:1',
            'quantities.*' => 'required|integer|min:1',
        ]);
        if (!CrmUser::inWorkspace(null, ['estimator'])->where('id', $data['estimator_id'])->exists()) {
            return back()->withInput()->with('error', 'Please select a valid estimator.');
        }

        $ticket = DB::transaction(function () use ($data) {
            $linkedInquiry = !empty($data['crm_email_id'])
                ? CrmEmail::find($data['crm_email_id'])
                : null;
            $ticket = EstimateTicket::create(array_merge($data, [
                'ticket_number' => $linkedInquiry
                    ? $linkedInquiry->workflow_number
                    : 'EST-' . now()->format('ymd') . '-' . strtoupper(substr(uniqid(), -5)),
                'requested_by' => Auth::guard('crm')->id(), 'status' => 'pending',
            ]));
            foreach ($data['quantities'] as $quantity) {
                $ticket->options()->create(['quantity' => $quantity]);
            }
            return $ticket;
        });

        return redirect()->route('crm.estimate_tickets.show', $ticket->id)->with('success', 'Estimate ticket sent to estimator.');
    }

    public function show($id)
    {
        $ticket = EstimateTicket::with(['options', 'estimator', 'requester', 'teamLead', 'lead'])->findOrFail($id);
        $this->authorizeTicket($ticket);
        if ($this->isAlMassaWorkspace()) {
            return view('crm.estimate_tickets.show_al_massa', compact('ticket'));
        }
        $rateMatrices = CrmEstimationRateMatrix::orderBy('type')->orderBy('paper_size')->orderBy('gsm')->get();
        return view('crm.estimate_tickets.show', compact('ticket', 'rateMatrices'));
    }

    protected function isAlMassaWorkspace()
    {
        return optional(request()->attributes->get('crm_workspace'))->slug === 'mybox-packaging-app';
    }

    // Build a human-friendly PDF file name from the client's name (falls back to the ticket number).
    protected function estimateFileName(EstimateTicket $ticket, $suffix = 'estimate')
    {
        $clientSlug = \Illuminate\Support\Str::slug($ticket->client_name ?: '');
        $base = $clientSlug !== '' ? $clientSlug : strtolower($ticket->ticket_number);
        return $base . '-' . $suffix . '.pdf';
    }

    public function indexRateMatrix()
    {
        $user = Auth::guard('crm')->user();
        if (!in_array($user->activeWorkspaceRole(), ['admin', 'super_admin', 'estimator', 'sales_manager'], true)) {
            abort(403, 'Only authorized team members can manage estimation rate matrices.');
        }

        $rateMatrices = CrmEstimationRateMatrix::orderBy('type')->orderBy('paper_size')->orderBy('gsm')->get();
        return view('crm.estimate_tickets.rates', compact('rateMatrices'));
    }

    public function storeRateMatrix(Request $request)
    {
        $user = Auth::guard('crm')->user();
        if (!in_array($user->activeWorkspaceRole(), ['admin', 'super_admin', 'estimator', 'sales_manager'], true)) {
            abort(403, 'Only authorized team members can manage estimation rate matrices.');
        }

        $data = $request->validate([
            'type' => 'required|in:paper_gsm,printing_color,ctp_plate,lamination,paper_type_rate,die_cutting,cardboard_pasting,corrugated_pasting,box_pasting,uv_rate,foiling_rate',
            'paper_type' => 'nullable|string|max:60',
            'paper_size' => 'nullable|string|max:50',
            'gsm' => 'nullable|string|max:30',
            'thickness_unit' => 'nullable|in:in,mm,cm',
            'name' => 'nullable|string|max:100',
            'ctp_plate_name' => 'nullable|string|max:100',
            'currency' => 'nullable|in:USD,AED,GBP,EUR,CAD,AUD,PKR,SAR,QAR',
            'rate' => 'nullable|numeric|min:0',
            'rate_large' => 'nullable|numeric|min:0',
            'threshold_short' => 'nullable|numeric|min:0',
            'threshold_long' => 'nullable|numeric|min:0',
            'weight_divisor' => 'nullable|numeric|min:0',
            'weight_sheets' => 'nullable|integer|min:1',
        ]);

        $data['currency'] = $data['currency'] ?? session('crm_currency', 'USD');
        $data['rate'] = $data['rate'] ?? 0;
        $data['created_by'] = $user->id;
        CrmEstimationRateMatrix::create($data);

        return back()->with('success', 'Estimation rate added successfully.');
    }

    public function bulkConvertRateMatrix(Request $request)
    {
        $user = Auth::guard('crm')->user();
        if (!in_array($user->activeWorkspaceRole(), ['admin', 'super_admin', 'estimator', 'sales_manager'], true)) {
            abort(403, 'Only authorized team members can manage estimation rate matrices.');
        }

        $data = $request->validate([
            'currency' => 'required|in:USD,AED,GBP,EUR,CAD,AUD,PKR,SAR,QAR',
            'updates' => 'required|array|min:1',
            'updates.*.id' => 'required|integer',
            'updates.*.rate' => 'required|numeric|min:0',
        ]);

        $ids = array_column($data['updates'], 'id');
        $rows = CrmEstimationRateMatrix::whereIn('id', $ids)->get()->keyBy('id');

        $saved = 0;
        DB::transaction(function () use ($data, $rows, &$saved) {
            foreach ($data['updates'] as $u) {
                $row = $rows->get($u['id']);
                if ($row) {
                    $row->currency = $data['currency'];
                    $row->rate = $u['rate'];
                    $row->save();
                    $saved++;
                }
            }
        });

        return back()->with('success', $saved . ' rate(s) converted to ' . $data['currency'] . ' and saved.');
    }

    public function bulkConvertUnitMatrix(Request $request)
    {
        $user = Auth::guard('crm')->user();
        if (!in_array($user->activeWorkspaceRole(), ['admin', 'super_admin', 'estimator', 'sales_manager'], true)) {
            abort(403, 'Only authorized team members can manage estimation rate matrices.');
        }

        $data = $request->validate([
            'unit' => 'required|in:in,mm,cm',
            'updates' => 'required|array|min:1',
            'updates.*.id' => 'required|integer',
            'updates.*.paper_size' => 'required|string|max:50',
        ]);

        $ids = array_column($data['updates'], 'id');
        $rows = CrmEstimationRateMatrix::whereIn('id', $ids)->where('type', 'paper_gsm')->get()->keyBy('id');

        $saved = 0;
        DB::transaction(function () use ($data, $rows, &$saved) {
            foreach ($data['updates'] as $u) {
                $row = $rows->get($u['id']);
                if ($row) {
                    $row->paper_size = $u['paper_size'];
                    $row->thickness_unit = $data['unit'];
                    $row->save();
                    $saved++;
                }
            }
        });

        return back()->with('success', $saved . ' paper size(s) converted to ' . $data['unit'] . ' and saved.');
    }

    public function destroyRateMatrix($id)
    {
        $user = Auth::guard('crm')->user();
        if (!in_array($user->activeWorkspaceRole(), ['admin', 'super_admin', 'estimator', 'sales_manager'], true)) {
            abort(403, 'Only authorized team members can manage estimation rate matrices.');
        }

        $matrix = CrmEstimationRateMatrix::findOrFail($id);
        $matrix->delete();

        return back()->with('success', 'Estimation rate deleted successfully.');
    }

    public function updateRateMatrix(Request $request, $id)
    {
        $user = Auth::guard('crm')->user();
        if (!in_array($user->activeWorkspaceRole(), ['admin', 'super_admin', 'estimator', 'sales_manager'], true)) {
            abort(403, 'Only authorized team members can manage estimation rate matrices.');
        }

        $matrix = CrmEstimationRateMatrix::findOrFail($id);

        $data = $request->validate([
            'paper_type' => 'nullable|string|max:60',
            'paper_size' => 'nullable|string|max:50',
            'gsm' => 'nullable|string|max:30',
            'thickness_unit' => 'nullable|in:in,mm,cm',
            'name' => 'nullable|string|max:100',
            'ctp_plate_name' => 'nullable|string|max:100',
            'currency' => 'nullable|in:USD,AED,GBP,EUR,CAD,AUD,PKR,SAR,QAR',
            'rate' => 'nullable|numeric|min:0',
            'rate_large' => 'nullable|numeric|min:0',
            'threshold_short' => 'nullable|numeric|min:0',
            'threshold_long' => 'nullable|numeric|min:0',
            'weight_divisor' => 'nullable|numeric|min:0',
            'weight_sheets' => 'nullable|integer|min:1',
        ]);

        $data['currency'] = $data['currency'] ?? ($matrix->currency ?: session('crm_currency', 'USD'));
        $data['rate'] = $data['rate'] ?? 0;

        $matrix->update($data);

        return back()->with('success', 'Estimation rate updated successfully.');
    }

    public function edit($id)
    {
        $this->requireSalesRole();
        $ticket = EstimateTicket::with('options')->findOrFail($id);
        if ($ticket->status !== 'returned_to_sales') abort(403, 'Ticket is not in a return state.');
        $estimators = CrmUser::inWorkspace(null, ['estimator'])->orderBy('name')->get();
        return view('crm.estimate_tickets.edit', compact('ticket', 'estimators'));
    }

    public function update(Request $request, $id)
    {
        $this->requireSalesRole();
        $ticket = EstimateTicket::with('options')->findOrFail($id);
        if ($ticket->status !== 'returned_to_sales') abort(403, 'Ticket is not in a return state.');

        $data = $request->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'nullable|email|max:255', 'product_style' => 'required|string|max:255',
            'stock' => 'nullable|string|max:255', 'printing' => 'nullable|string|max:255',
            'finish_size' => 'nullable|string|max:255', 'flat_size' => 'nullable|string|max:255',
            'shipping' => 'nullable|string|max:255', 'weight' => 'nullable|string|max:255',
            'estimator_id' => 'required|exists:crm_users,id', 'quantities' => 'required|array|min:1',
            'quantities.*' => 'required|integer|min:1',
        ]);

        if (!CrmUser::inWorkspace(null, ['estimator'])->where('id', $data['estimator_id'])->exists()) {
            return back()->withInput()->with('error', 'Please select a valid estimator.');
        }

        DB::transaction(function () use ($ticket, $data) {
            $ticket->update(array_merge($data, [
                'status' => 'pending', 
                'return_note' => null,
                'returned_to' => null,
                'returned_by' => null,
                'returned_at' => null,
                'requested_by' => Auth::guard('crm')->id(),
            ]));
            
            $ticket->options()->delete();
            foreach ($data['quantities'] as $quantity) {
                $ticket->options()->create(['quantity' => $quantity]);
            }
            
            if ($ticket->lead) {
                $ticket->lead->update(['estimate_status' => 'pending', 'estimator_id' => $data['estimator_id']]);
            }
        });

        return redirect()->route('crm.estimate_tickets.show', $ticket->id)->with('success', 'Estimate ticket updated and resubmitted to estimator.');
    }

    public function claim($id)
    {
        $user = Auth::guard('crm')->user();
        // Al Massa admins may open/claim and estimate a ticket themselves; other workspaces stay estimator/team-lead only.
        $adminCanClaim = $user->isAdmin() && $this->isAlMassaWorkspace();
        if (!$user->isEstimator() && !$user->isTeamLead() && !$adminCanClaim) {
            abort(403, 'Only an Estimator or Team Lead can open and claim this ticket.');
        }

        DB::transaction(function () use ($id, $user) {
            $ticket = EstimateTicket::where('id', $id)->lockForUpdate()->firstOrFail();
            if ($user->isTeamLead()) {
                if ($ticket->status !== 'team_lead_review') {
                    if ((int)$ticket->team_lead_id === (int)$user->id && $ticket->status === 'team_lead_open') return;
                    abort(409, 'This review ticket has already been opened.');
                }
                $ticket->update(['status' => 'team_lead_open', 'team_lead_id' => $user->id]);
                return;
            }
            if ($ticket->status !== 'pending') {
                if ((int)$ticket->estimator_id === (int)$user->id && in_array($ticket->status, ['open', 'revision_requested'])) return;
                abort(409, 'This estimate ticket has already been opened.');
            }
            $ticket->update(['status' => 'open', 'estimator_id' => $user->id]);
            if ($ticket->lead) $ticket->lead->update(['estimator_id' => $user->id, 'estimate_status' => 'pending']);
        });

        return redirect()->route('crm.estimate_tickets.show', $id)
            ->with('success', 'Estimate ticket opened and assigned to you.');
    }

    public function submit(Request $request, $id)
    {
        $ticket = EstimateTicket::with('options')->findOrFail($id);
        $user = Auth::guard('crm')->user();
        $isDraft = $request->input('save_mode') === 'draft';
        if (!$user->isAdmin() && (!$user->isEstimator() || (int)$ticket->estimator_id !== (int)$user->id)) abort(403);
        if (!in_array($ticket->status, ['open', 'revision_requested'])) {
            return back()->with('error', 'Open this ticket before submitting its estimate.');
        }

        $data = $request->validate([
            'prices' => 'required|array', 'prices.*' => ($isDraft ? 'nullable' : 'required').'|numeric|min:0',
            'discounts' => 'nullable|array', 'discounts.*' => 'nullable|numeric|min:0|max:100',
            'new_quantities' => 'nullable|array|max:20', 'new_quantities.*' => ($isDraft ? 'nullable' : 'required').'|integer|min:1',
            'new_prices' => ($isDraft ? 'nullable' : 'required_with:new_quantities').'|array',
            'new_prices.*' => ($isDraft ? 'nullable' : 'required').'|numeric|min:0',
            'new_discounts' => 'nullable|array', 'new_discounts.*' => 'nullable|numeric|min:0|max:100',
            'cost_names' => 'required|array|min:1|max:30', 'cost_names.*' => 'required|string|max:100',
            'cost_details' => 'required|array', 'cost_details.*' => 'nullable|string|max:500',
            'cost_quantities' => 'required|array', 'cost_quantities.*' => 'nullable|numeric|min:0',
            'cost_unit_prices' => 'required|array', 'cost_unit_prices.*' => 'nullable|numeric|min:0',
            'cost_prices' => 'required|array', 'cost_prices.*' => 'nullable|numeric|min:0',
            'cost_sections' => 'required|array', 'cost_sections.*' => 'required|in:variable,fixed',
            'extra_names' => 'nullable|array|max:20', 'extra_names.*' => 'nullable|string|max:100',
            'extra_details' => 'nullable|array', 'extra_details.*' => 'nullable|string|max:500',
            'extra_quantities' => 'nullable|array', 'extra_quantities.*' => 'nullable|numeric|min:0',
            'extra_unit_prices' => 'nullable|array', 'extra_unit_prices.*' => 'nullable|numeric|min:0',
            'extra_prices' => 'nullable|array', 'extra_prices.*' => 'nullable|numeric|min:0',
            'fixed_extra_names' => 'nullable|array|max:20', 'fixed_extra_names.*' => 'nullable|string|max:100',
            'fixed_extra_details' => 'nullable|array', 'fixed_extra_details.*' => 'nullable|string|max:500',
            'fixed_extra_prices' => 'nullable|array', 'fixed_extra_prices.*' => 'nullable|numeric|min:0',
            'waste_material_percentage' => 'nullable|numeric|min:0|max:100',
            'production_cost_percentage' => 'nullable|numeric|min:0|max:1000',
            'vat_percentage' => 'nullable|numeric|min:0|max:100',
            'calculator_state' => 'nullable|array',
            'calculator_state.*' => 'nullable|array',
            'calculator_state.*.*' => 'nullable|string|max:150',
            'redirect_to' => 'nullable|string|max:2048',
            'estimator_notes' => 'nullable|string|max:3000',
            'currency' => 'required|string|in:USD,AED,GBP,EUR,CAD,AUD,PKR,SAR,QAR',
            'profit_margin_percentage' => 'nullable|numeric|min:0|max:1000',
        ]);

        // Al Massa estimators price with their own margin and skip the Team Lead review step.
        $isAlMassa = $this->isAlMassaWorkspace();

        $breakdown = [];
        foreach ($data['cost_names'] as $index => $name) {
            $section = $data['cost_sections'][$index] ?? 'variable';
            if ($section === 'fixed') {
                // Fixed rows (Die/Block = Qty, Shipping = Weight) submit a total price plus an optional qty/weight.
                $rawQty = $data['cost_quantities'][$index] ?? null;
                $hasQty = !($rawQty === null || $rawQty === '');
                $costItem = [
                    'name' => $name,
                    'detail' => $data['cost_details'][$index] ?? '',
                    'quantity' => $hasQty ? (float) $rawQty : null, // Die/Block = qty, Shipping = weight (kg)
                    'price' => round((float) ($data['cost_prices'][$index] ?? 0), 2),
                    'section' => 'fixed',
                ]; // no 'unit_price' key -> read-only view derives price/qty (per die / per kg)
            } else {
                $rawQty = $data['cost_quantities'][$index] ?? null;
                $hasQty = !($rawQty === null || $rawQty === '');
                $quantity = $hasQty ? (float) $rawQty : 1.0; // blank behaves as 1 for pricing
                $unitPrice = (float) ($data['cost_unit_prices'][$index] ?? 0);
                $costItem = [
                    'name' => $name,
                    'detail' => $data['cost_details'][$index] ?? '',
                    'quantity' => $hasQty ? $quantity : null, // store null when blank so the field stays empty on reload
                    'unit_price' => round($unitPrice, 4),
                    'price' => round($quantity * $unitPrice, 2),
                    'section' => 'variable',
                ];
            }
            if ($name === 'Paper Dimensions') {
                $costItem['calculator_state'] = $data['calculator_state'] ?? [];
            }
            $breakdown[] = $costItem;
        }
        foreach (($data['extra_names'] ?? []) as $index => $name) {
            if (!trim((string)$name)) continue;
            $quantity = (float) ($data['extra_quantities'][$index] ?? 1);
            $unitPrice = (float) ($data['extra_unit_prices'][$index] ?? 0);
            $breakdown[] = [
                'name' => trim($name),
                'detail' => $data['extra_details'][$index] ?? '',
                'quantity' => $quantity,
                'unit_price' => round($unitPrice, 4),
                'price' => round($quantity * $unitPrice, 2),
            ];
        }
        foreach (($data['fixed_extra_names'] ?? []) as $index => $name) {
            if (!trim((string)$name)) continue;
            $breakdown[] = [
                'name' => trim($name),
                'detail' => $data['fixed_extra_details'][$index] ?? '',
                'price' => round((float)($data['fixed_extra_prices'][$index] ?? 0), 2),
                'section' => 'fixed',
            ];
        }
        $variableCost = collect($breakdown)->filter(function ($item) {
            return ($item['section'] ?? 'variable') === 'variable';
        })->sum('price');
        $fixedCost = collect($breakdown)->where('section', 'fixed')->sum('price');
        $wastePercentage = (float)($data['waste_material_percentage'] ?? 0);
        $wasteAmount = round(($variableCost + $fixedCost) * ($wastePercentage / 100), 2);
        $productionPercentage = (float)($data['production_cost_percentage'] ?? 0);
        $productionAmount = round(($variableCost + $fixedCost + $wasteAmount) * ($productionPercentage / 100), 2);
        $breakdown[] = [
            'name' => 'Production Cost',
            'detail' => rtrim(rtrim(number_format($productionPercentage, 2, '.', ''), '0'), '.').'% of base cost and wastage',
            'price' => $productionAmount,
            'percentage' => $productionPercentage,
            'section' => 'production',
        ];

        if (!$isDraft) {
            foreach ($ticket->options as $option) {
                if (!array_key_exists($option->id, $data['prices']) || (float)$data['prices'][$option->id] < 0) {
                    return back()->withInput()->with('error', 'Price is required for every quantity option.');
                }
            }
            if (count($data['new_quantities'] ?? []) !== count($data['new_prices'] ?? [])) {
                return back()->withInput()->with('error', 'Price is required for every extra quantity.');
            }
        }
        $allQuantities = $ticket->options->pluck('quantity')->map(function ($quantity) {
            return (int) $quantity;
        })->all();
        foreach (($data['new_quantities'] ?? []) as $quantity) {
            if ($isDraft && ($quantity === null || $quantity === '')) continue;
            $quantity = (int) $quantity;
            if (in_array($quantity, $allQuantities, true)) {
                return back()->withInput()->with('error', 'Each quantity option must be unique.');
            }
            $allQuantities[] = $quantity;
        }

        DB::transaction(function () use ($ticket, $data, $breakdown, $wastePercentage, $wasteAmount, $isDraft, $isAlMassa) {
            foreach ($ticket->options as $option) {
                $submittedPrice = $data['prices'][$option->id] ?? null;
                if ($isDraft && ($submittedPrice === null || $submittedPrice === '')) continue;
                $price = (float) $submittedPrice;
                $discount = (float)($data['discounts'][$option->id] ?? 0);
                $option->update([
                    'total_price' => $price,
                    'unit_price' => $option->quantity > 0 ? $price / $option->quantity : 0,
                    'discount_percentage' => $discount,
                    'discounted_price' => round($price * (1 - ($discount / 100)), 2),
                    'estimator_notes' => null,
                ]);
            }
            foreach (($data['new_quantities'] ?? []) as $index => $quantity) {
                $newPrice = $data['new_prices'][$index] ?? null;
                if ($isDraft && (($quantity === null || $quantity === '') || ($newPrice === null || $newPrice === ''))) {
                    continue;
                }
                $price = (float)$newPrice;
                $discount = (float)($data['new_discounts'][$index] ?? 0);
                $ticket->options()->create([
                    'quantity' => (int)$quantity,
                    'total_price' => $price,
                    'unit_price' => $quantity > 0 ? $price / $quantity : 0,
                    'discount_percentage' => $discount,
                    'discounted_price' => round($price * (1 - ($discount / 100)), 2),
                ]);
            }
            $margin = (float) ($data['profit_margin_percentage'] ?? 0);
            $vat = (float) ($data['vat_percentage'] ?? 0);
            if ($isAlMassa) {
                $offerOptions = [];
                foreach ($ticket->options()->get() as $option) {
                    if ($option->total_price === null) continue;
                    $estimatorPrice = $option->discounted_price !== null
                        ? (float) $option->discounted_price
                        : (float) $option->total_price;
                    // Offer is VAT-inclusive — the customer PDF prints VAT as "(included)".
                    $offer = round($estimatorPrice * (1 + ($margin / 100)) * (1 + ($vat / 100)), 2);
                    $unitOffer = $option->quantity > 0 ? $offer / $option->quantity : 0;
                    $option->update([
                        'profit_margin_percentage' => $margin,
                        'offer_price' => $offer,
                        'offer_unit_price' => $unitOffer,
                    ]);
                    $offerOptions[] = [
                        'quantity' => $option->quantity,
                        'price' => round($offer, 2),
                        'team_lead_price' => round($offer, 2),
                        'unit_price' => round($unitOffer, 4),
                        'profit_margin_percentage' => round($margin, 2),
                    ];
                }
            }

            $ticket->update([
                'cost_breakdown' => $breakdown,
                'waste_material_percentage' => $wastePercentage,
                'waste_material_amount' => $wasteAmount,
                'estimator_notes' => $data['estimator_notes'] ?? null,
                'currency' => $data['currency'],
                'team_lead_id' => $isDraft ? $ticket->team_lead_id : null,
                // Al Massa quotes go to the owner/admin for approval before sales sees them.
                'status' => $isDraft ? 'open' : ($isAlMassa ? 'owner_review' : 'team_lead_review'),
                'submitted_at' => $isDraft ? $ticket->submitted_at : now(),
            ]);
            if ($ticket->lead) {
                $ticket->lead->update([
                    'estimate_status' => $isDraft ? 'pending' : ($isAlMassa ? 'owner_review' : 'team_lead_review'),
                    'vat_percentage' => (float) ($data['vat_percentage'] ?? 0),
                ]);
            }
        });

        if ($isDraft) {
            $redirectTo = (string) ($data['redirect_to'] ?? '');
            if ($redirectTo !== '' && preg_match('#^/crm(?:/|$)#', $redirectTo)) {
                return redirect()->to($redirectTo)
                    ->with('success', 'Estimate draft saved. The ticket remains open.');
            }
            return redirect()->route('crm.estimate_tickets.index')
                ->with('success', 'Estimate draft saved. The ticket remains open.');
        }

        return redirect()->route('crm.estimate_tickets.index', ['tab' => 'history'])
            ->with('success', $isAlMassa
                ? 'Quote sent to the owner for approval.'
                : 'Estimate submitted to Team Lead for final offer review.');
    }

    public function ownerApprove($id)
    {
        $user = Auth::guard('crm')->user();
        // Owner (super_admin), Admin, and Sales Manager may approve a quote.
        if (!$user->isAdmin() && !$user->isSuperAdmin() && !$user->isSalesManager()) {
            abort(403, 'Only owner / admin / sales manager can approve quotes.');
        }
        $ticket = EstimateTicket::with(['options', 'lead'])->findOrFail($id);
        if ($ticket->status !== 'owner_review') {
            return back()->with('error', 'Only a quote awaiting owner approval can be approved.');
        }

        DB::transaction(function () use ($ticket) {
            $offerOptions = [];
            foreach ($ticket->options as $option) {
                if ($option->offer_price === null) continue;
                $offerOptions[] = [
                    'quantity' => $option->quantity,
                    'price' => round((float) $option->offer_price, 2),
                    'team_lead_price' => round((float) $option->offer_price, 2),
                    'unit_price' => round((float) $option->offer_unit_price, 4),
                    'profit_margin_percentage' => round((float) $option->profit_margin_percentage, 2),
                ];
            }
            $ticket->update(['status' => 'estimated']);
            if ($ticket->lead) {
                $ticket->lead->update([
                    'price_offered' => $offerOptions[0]['price'] ?? null,
                    'invoice_currency' => $ticket->currency,
                    'estimate_quantity_options' => $offerOptions,
                    'estimated_price' => $offerOptions[0]['price'] ?? null,
                    'estimate_status' => 'estimated',
                ]);
            }
        });

        return redirect()->route('crm.estimate_tickets.index', ['tab' => 'history'])
            ->with('success', 'Quote approved and released to sales.');
    }

    public function submitTeamLeadOffer(Request $request, $id)
    {
        $user = Auth::guard('crm')->user();
        $ticket = EstimateTicket::with(['options', 'lead'])->findOrFail($id);
        if (!$user->isAdmin() && (!$user->isTeamLead() || (int)$ticket->team_lead_id !== (int)$user->id)) abort(403);
        if ($ticket->status !== 'team_lead_open') return back()->with('error', 'Open this review ticket first.');

        $data = $request->validate([
            'profit_margins' => 'required|array',
            'profit_margins.*' => 'required|numeric|min:0|max:1000',
            'team_lead_notes' => 'nullable|string|max:3000',
        ]);
        $offerOptions = [];
        foreach ($ticket->options as $option) {
            if (!array_key_exists($option->id, $data['profit_margins'])) {
                return back()->withInput()->with('error', 'Profit margin is required for every quantity.');
            }
            $margin = (float)$data['profit_margins'][$option->id];
            $estimatorPrice = $option->discounted_price !== null
                ? (float)$option->discounted_price
                : (float)$option->total_price;
            $offer = round($estimatorPrice * (1 + ($margin / 100)), 2);
            $unitOffer = $option->quantity > 0 ? $offer / $option->quantity : 0;
            $option->update([
                'profit_margin_percentage' => $margin,
                'offer_price' => $offer,
                'offer_unit_price' => $unitOffer,
            ]);
            $offerOptions[] = [
                'quantity' => $option->quantity,
                'price' => round($offer, 2),
                'team_lead_price' => round($offer, 2),
                'unit_price' => round($unitOffer, 4),
                'profit_margin_percentage' => round($margin, 2),
            ];
        }
        $ticket->update([
            'team_lead_notes' => $data['team_lead_notes'] ?? null,
            'team_lead_reviewed_at' => now(),
            'status' => 'estimated',
        ]);
        if ($ticket->lead) {
            $ticket->lead->update([
                'price_offered' => $offerOptions[0]['price'] ?? null,
                'invoice_currency' => $ticket->currency,
                'estimate_quantity_options' => $offerOptions,
                'estimated_price' => $offerOptions[0]['price'] ?? null,
                'estimate_status' => 'estimated',
            ]);
        }
        return redirect()->route('crm.estimate_tickets.index', ['tab' => 'history'])
            ->with('success', 'Final offer approved and sent to the sales inquiry.');
    }

    public function revision(Request $request, $id)
    {
        $this->requireSalesRole();
        $ticket = EstimateTicket::findOrFail($id); $this->authorizeTicket($ticket);
        $request->validate(['revision_note' => 'required|string|max:2000']);
        $ticket->update(['status' => 'revision_requested', 'team_lead_id' => null, 'requirements' => trim($ticket->requirements . "\n\nRevision: " . $request->revision_note)]);
        return back()->with('success', 'Revision sent to estimator.');
    }

    public function returnTicket(Request $request, $id)
    {
        $user = Auth::guard('crm')->user();
        $data = $request->validate([
            'returned_to' => 'required|in:design,sales,estimator',
            'return_note' => 'required|string|max:3000',
        ]);
        $ticket = EstimateTicket::with(['lead', 'options'])->findOrFail($id);
        $returningToEstimator = $data['returned_to'] === 'estimator';
        if ($returningToEstimator) {
            if (!$user->isAdmin()
                && (!$user->isTeamLead() || (int)$ticket->team_lead_id !== (int)$user->id)) {
                abort(403);
            }
            if ($ticket->status !== 'team_lead_open') {
                return back()->with('error', 'Only an open Team Lead review can be returned to the estimator.');
            }
        } else {
            if (!$user->isAdmin()
                && (!$user->isEstimator() || (int)$ticket->estimator_id !== (int)$user->id)) {
                abort(403);
            }
            if (!in_array($ticket->status, ['open', 'revision_requested'])) {
                return back()->with('error', 'Only an open estimate ticket can be returned.');
            }
        }
        if (!$ticket->lead) {
            return back()->with('error', 'This estimate is not linked to an inquiry.');
        }

        DB::transaction(function () use ($ticket, $user, $data) {
            $status = $data['returned_to'] === 'estimator'
                ? 'revision_requested'
                : ($data['returned_to'] === 'design' ? 'returned_to_design' : 'returned_to_sales');
            $ticket->update([
                'status' => $status,
                'team_lead_id' => $data['returned_to'] === 'estimator' ? null : $ticket->team_lead_id,
                'returned_to' => $data['returned_to'],
                'return_note' => $data['return_note'],
                'returned_by' => $user->id,
                'returned_at' => now(),
            ]);

            if ($data['returned_to'] === 'design') {
                $designTicket = \App\DesignRequirementTicket::where('crm_email_id', $ticket->crm_email_id)->first();
                if (!$designTicket) {
                    $designTicket = new \App\DesignRequirementTicket([
                        'ticket_number' => $ticket->ticket_number,
                        'crm_email_id' => $ticket->crm_email_id,
                        'requested_by' => $ticket->requested_by,
                        'quantities' => $ticket->options->pluck('quantity')->map(function ($quantity) {
                            return (int)$quantity;
                        })->all(),
                    ]);
                }
                $isClaimed = $designTicket->exists && $designTicket->claimed_by;
                $designTicket->fill([
                    'estimate_ticket_id' => $ticket->id,
                    'status' => $isClaimed ? 'open' : 'new',
                    'claimed_by' => $isClaimed ? $designTicket->claimed_by : null,
                    'designer_notes' => $data['return_note'],
                    'return_note' => $data['return_note'],
                    'returned_by' => $user->id,
                    'returned_at' => now(),
                    'opened_at' => $isClaimed ? ($designTicket->opened_at ?: now()) : null,
                    'completed_at' => null,
                    'forwarded_at' => null,
                ])->save();
            }

            $ticket->lead->update($data['returned_to'] === 'estimator'
                ? ['estimate_status' => $status]
                : ['estimator_id' => null, 'estimate_status' => $status]);

            if ($status === 'returned_to_sales' && $ticket->crm_email_id) {
                \App\CrmEmail::where('id', $ticket->crm_email_id)->update(['updated_at' => now()]);
            }
        });

        return redirect()->route('crm.estimate_tickets.index', ['tab' => 'history'])
            ->with('success', 'Ticket returned to '.ucfirst($data['returned_to']).' with your note.');
    }

    public function complete($id)
    {
        $this->requireSalesRole();
        $ticket = EstimateTicket::findOrFail($id); $this->authorizeTicket($ticket);
        $ticket->update(['status' => 'completed', 'completed_at' => now()]);
        if ($ticket->lead) $ticket->lead->update(['estimate_status' => 'completed']);
        return back()->with('success', 'Estimate ticket completed.');
    }

    public function sendToChat($id)
    {
        $this->requireSalesRole();
        $ticket = EstimateTicket::with(['options', 'estimator', 'requester', 'lead'])->findOrFail($id);
        $this->authorizeTicket($ticket);

        if (!$ticket->lead) {
            return back()->with('error', 'This estimate is not linked to a lead. Select an existing lead before sending it to chat.');
        }
        if (!in_array($ticket->status, ['estimated', 'completed'])) {
            return back()->with('error', 'Estimator prices must be submitted before sending the estimate to chat.');
        }

        $filename = $this->estimateFileName($ticket);
        $brandName = optional($ticket->workspace)->slug === 'mybox-packaging-app'
            ? 'Al Massa Packaging'
            : 'My Box Printing';
        try {
            $pdf = (new \App\Services\EstimatePdfService())->generate($ticket);
        } catch (\Throwable $exception) {
            \Log::error('Estimate PDF generation failed while preparing client reply.', [
                'estimate_ticket_id' => $ticket->id,
                'message' => $exception->getMessage(),
            ]);
            return back()->with('error', 'The estimate PDF could not be generated. Please try again.');
        }

        return redirect()->route('crm.emails.show', $ticket->crm_email_id)
            ->with('estimate_draft', [
                'body' => '<p>Hi ' . e($ticket->client_name ?: 'there') . ',</p>'
                    . '<p>Your custom packaging estimate <strong>' . e($ticket->ticket_number) . '</strong> is ready. Please review the attached PDF and let us know which quantity option you would like to proceed with.</p>'
                    . '<p>Best regards,<br>' . e($brandName) . '</p>',
                'attachment_url' => route('crm.estimate_tickets.draft_pdf', $ticket->id, false),
                'attachment_name' => $filename,
                'attachment_base64' => base64_encode($pdf),
            ])
            ->with('success', 'Estimate PDF added to the reply draft. Review the message before sending.');
    }

    public function draftPdf($id)
    {
        $this->requireSalesRole();
        $ticket = EstimateTicket::with(['options', 'estimator', 'requester', 'lead'])->findOrFail($id);
        $this->authorizeTicket($ticket);

        if (!$ticket->lead || !in_array($ticket->status, ['estimated', 'completed'])) {
            abort(404);
        }

        $filename = $this->estimateFileName($ticket);
        $pdf = (new \App\Services\EstimatePdfService())->generate($ticket);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Content-Length' => strlen($pdf),
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function exportInternalPdf(Request $request, $id)
    {
        $ticket = EstimateTicket::with(['options', 'estimator', 'requester', 'teamLead', 'lead', 'workspace'])
            ->findOrFail($id);
        $this->authorizeTicket($ticket);

        $breakdown = collect();
        if ($request->has('cost_names')) {
            foreach ((array) $request->input('cost_names', []) as $index => $name) {
                $breakdown->push([
                    'name' => $name,
                    'detail' => $request->input('cost_details.'.$index),
                    'section' => $request->input('cost_sections.'.$index, 'variable'),
                    'price' => (float) $request->input('cost_prices.'.$index, 0),
                ]);
            }
            foreach ((array) $request->input('extra_names', []) as $index => $name) {
                if (trim((string) $name) === '') continue;
                $breakdown->push([
                    'name' => $name,
                    'detail' => $request->input('extra_details.'.$index),
                    'section' => 'variable',
                    'price' => (float) $request->input('extra_prices.'.$index, 0),
                ]);
            }
            foreach ((array) $request->input('fixed_extra_names', []) as $index => $name) {
                if (trim((string) $name) === '') continue;
                $breakdown->push([
                    'name' => $name,
                    'detail' => $request->input('fixed_extra_details.'.$index),
                    'section' => 'fixed',
                    'price' => (float) $request->input('fixed_extra_prices.'.$index, 0),
                ]);
            }
            $requestVariableCost = (float) $breakdown->filter(function ($item) {
                return ($item['section'] ?? 'variable') === 'variable';
            })->sum('price');
            $requestFixedCost = (float) $breakdown->where('section', 'fixed')->sum('price');
            $requestWastePercentage = (float) $request->input('waste_material_percentage', 0);
            $requestWasteAmount = ($requestVariableCost + $requestFixedCost) * ($requestWastePercentage / 100);
            $requestProductionPercentage = (float) $request->input('production_cost_percentage', 0);
            $requestProductionAmount = ($requestVariableCost + $requestFixedCost + $requestWasteAmount)
                * ($requestProductionPercentage / 100);
            $breakdown->push([
                'name' => 'Production Cost',
                'detail' => rtrim(rtrim(number_format($requestProductionPercentage, 2, '.', ''), '0'), '.').'% of base cost and wastage',
                'price' => round($requestProductionAmount, 2),
                'percentage' => $requestProductionPercentage,
                'section' => 'production',
            ]);

            $exportOptions = collect();
            foreach ($ticket->options as $option) {
                $copy = clone $option;
                $price = $request->input('prices.'.$option->id, $option->total_price);
                $discount = $request->input('discounts.'.$option->id, $option->discount_percentage);
                $copy->total_price = $price;
                $copy->unit_price = $copy->quantity > 0 ? (float)$price / $copy->quantity : 0;
                $copy->discount_percentage = (float)$discount;
                $copy->discounted_price = round((float)$price * (1 - ((float)$discount / 100)), 2);
                $exportOptions->push($copy);
            }
            foreach ((array) $request->input('new_quantities', []) as $index => $quantity) {
                if (!(int) $quantity) continue;
                $price = (float) $request->input('new_prices.'.$index, 0);
                $discount = (float) $request->input('new_discounts.'.$index, 0);
                $copy = new \App\EstimateTicketOption([
                    'quantity' => (int) $quantity,
                    'total_price' => $price,
                    'unit_price' => $quantity > 0 ? $price / $quantity : 0,
                    'discount_percentage' => $discount,
                    'discounted_price' => round($price * (1 - ($discount / 100)), 2),
                ]);
                $exportOptions->push($copy);
            }
            $ticket->setRelation('options', $exportOptions);
            $ticket->currency = $request->input('currency', $ticket->currency);
            $ticket->estimator_notes = $request->input('estimator_notes', $ticket->estimator_notes);
            if ($ticket->lead) {
                $ticket->lead->vat_percentage = (float) $request->input(
                    'vat_percentage',
                    $ticket->lead->vat_percentage ?? 5
                );
            }
            $ticket->waste_material_percentage = (float) $request->input(
                'waste_material_percentage',
                $ticket->waste_material_percentage
            );
            $ticket->cost_breakdown = $breakdown->values()->all();
        } else {
            $breakdown = collect(is_array($ticket->cost_breakdown) ? $ticket->cost_breakdown : []);
        }

        $variableCost = (float) $breakdown->filter(function ($item) {
            return ($item['section'] ?? 'variable') === 'variable'
                && !in_array(($item['name'] ?? ''), ['Machine Cost', 'Rent', 'Power'], true);
        })->sum('price');
        $fixedCost = (float) $breakdown->filter(function ($item) {
            return ($item['section'] ?? '') === 'fixed';
        })->sum('price');
        $baseCost = $variableCost + $fixedCost;
        $wasteAmount = $request->has('cost_names')
            ? $baseCost * ((float)$ticket->waste_material_percentage / 100)
            : (float) $ticket->waste_material_amount;
        $ticket->waste_material_amount = $wasteAmount;
        $productionCostItem = $breakdown->first(function ($item) {
            return ($item['name'] ?? '') === 'Production Cost';
        });
        $productionPercentage = (float) ($productionCostItem['percentage'] ?? 0);
        if (!$productionPercentage && !empty($productionCostItem['detail'])
            && preg_match('/([0-9]+(?:\.[0-9]+)?)\s*%/', $productionCostItem['detail'], $matches)) {
            $productionPercentage = (float) $matches[1];
        }
        $productionAmount = (float) ($productionCostItem['price']
            ?? (($baseCost + $wasteAmount) * ($productionPercentage / 100)));
        $productionCost = $baseCost + $wasteAmount + $productionAmount;
        $vatPercentage = (float) (optional($ticket->lead)->vat_percentage ?? 5);
        $vatAmount = $productionCost * ($vatPercentage / 100);
        $finalCost = $productionCost + $vatAmount;
        $generatedAt = now();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'crm.estimate_tickets.export_pdf',
            compact(
                'ticket', 'breakdown', 'variableCost', 'fixedCost',
                'baseCost', 'wasteAmount', 'productionPercentage', 'productionAmount',
                'productionCost', 'vatPercentage', 'vatAmount', 'finalCost', 'generatedAt'
            )
        )->setPaper('a4', 'portrait');

        return $pdf->download($this->estimateFileName($ticket, 'internal-estimate'));
    }

    private function requireSalesRole()
    {
        $u = Auth::guard('crm')->user();
        if (!$u->isAdmin() && !$u->isSalesManager() && !$u->isSales()) abort(403);
    }

    private function authorizeTicket(EstimateTicket $ticket)
    {
        $u = Auth::guard('crm')->user();
        if ($u->isAdmin() || $u->isSalesManager()) return;
        if ($u->isEstimator() && (int)$ticket->estimator_id === (int)$u->id) return;
        if ($u->isTeamLead() && (($ticket->status === 'team_lead_review' && !$ticket->team_lead_id) || (int)$ticket->team_lead_id === (int)$u->id)) return;
        if ($u->isSales() && (int)$ticket->requested_by === (int)$u->id) return;
        abort(403);
    }
}
