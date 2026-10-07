<?php

namespace App\Http\Controllers\Crm;

use App\CrmManualOrder;
use App\CrmManualOrderProductionBrief;
use App\CrmPrintReadyTicket;
use App\CrmUser;
use App\Http\Controllers\Controller;
use App\Mail\ProductionBriefMail;
use App\Http\Controllers\Crm\PrintReadyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * "Send to Production" for paid TCB manual orders: a write-once job briefing
 * (admins may edit later) that the production team receives by email and in the CRM.
 */
class ProductionBriefController extends Controller
{
    private function user()
    {
        $user = Auth::guard('crm')->user();
        if (!$user) abort(403);
        return $user;
    }

    /** Who may create / send a brief for this order. */
    private function canSend(CrmManualOrder $order, $user): bool
    {
        if ($user->isAdmin() || $user->isSalesManager()) return true;
        return $user->isSales() && (int) $order->created_by === (int) $user->id;
    }

    /** Who may view briefs. */
    private function canView($user): bool
    {
        return $user->isAdmin() || $user->isSalesManager() || $user->isSales() || $user->isProductionManager()
            || (method_exists($user, 'isAccounts') && $user->isAccounts());
    }

    /** Production list for the production team / admins. */
    public function index(Request $request)
    {
        $user = $this->user();
        if (!$user->isAdmin() && !$user->isProductionManager() && !$user->isSalesManager()) abort(403);

        $query = CrmManualOrderProductionBrief::with(['order', 'sender'])->latest('sent_at');
        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('job_number', 'like', "%{$s}%")->orWhere('client_name', 'like', "%{$s}%")->orWhere('products', 'like', "%{$s}%");
            });
        }
        if ($request->filled('type')) $query->where('production_type', $request->input('type'));

        return view('crm.orders.production_index', [
            'briefs' => $query->paginate(25)->appends($request->all()),
            'filters' => ['search' => $request->input('search', ''), 'type' => $request->input('type', '')],
        ]);
    }

    public function create($id)
    {
        $user = $this->user();
        $order = CrmManualOrder::findOrFail($id);
        if (!$this->canSend($order, $user)) abort(403);
        if ($order->invoice_status !== 'paid' && !$user->isAdmin()) {
            return redirect()->route('crm.orders.manual.index')->with('error', 'Only paid orders can be sent to production.');
        }
        if ($order->productionBrief) {
            return redirect()->route('crm.orders.manual.production.show', $order->id)->with('error', 'This order has already been sent to production.');
        }
        return view('crm.orders.production_form', [
            'order' => $order,
            'brief' => null,
            'prefill' => $this->prefill($order),
            'designers' => $this->printReadyDesigners(),
        ]);
    }

    public function store(Request $request, $id)
    {
        $user = $this->user();
        $order = CrmManualOrder::findOrFail($id);
        if (!$this->canSend($order, $user)) abort(403);
        if ($order->invoice_status !== 'paid' && !$user->isAdmin()) {
            return redirect()->route('crm.orders.manual.index')->with('error', 'Only paid orders can be sent to production.');
        }
        if ($order->productionBrief) {
            return redirect()->route('crm.orders.manual.production.show', $order->id)->with('error', 'This order has already been sent to production.');
        }

        $data = $this->validated($request);
        $brief = new CrmManualOrderProductionBrief($data + [
            'manual_order_id' => $order->id,
            'workspace_id' => $order->workspace_id,
            'status' => 'sent',
            'sent_by' => $user->id,
            'sent_at' => now(),
        ]);
        $brief->save();

        $note = $this->notifyProduction($brief, $user);
        $note .= $this->createPrintReadyTicket($brief, $order, $user, $request->input('assigned_designer_id'));
        return redirect()->route('crm.orders.manual.production.show', $order->id)
            ->with('success', "Order {$brief->job_number} sent to production." . $note);
    }

    /** Designers with Print Ready access (for the optional "Assign designer" dropdown). */
    private function printReadyDesigners()
    {
        $workspaceId = session('crm_workspace_id') ?: \App\Support\CrmWorkspaceContext::id();
        return CrmUser::inWorkspace($workspaceId, ['designer'])->where('print_ready_access', 1)->orderBy('name')->get(['crm_users.id', 'crm_users.name']);
    }

    /** Every sent brief opens a Print Ready ticket for the design team (optionally pre-assigned). */
    private function createPrintReadyTicket(CrmManualOrderProductionBrief $brief, CrmManualOrder $order, $user, $designerId = null): string
    {
        $designerId = $designerId ? (int) $designerId : null;
        if ($designerId && !$this->printReadyDesigners()->contains('id', $designerId)) $designerId = null;

        $ticket = CrmPrintReadyTicket::create([
            'workspace_id' => $order->workspace_id,
            'manual_order_id' => $order->id,
            'production_brief_id' => $brief->id,
            'ticket_number' => 'PR-' . str_pad((string) $brief->id, 4, '0', STR_PAD_LEFT),
            'job_number' => $brief->job_number,
            'client_name' => $brief->client_name,
            'products' => $brief->products,
            'folder_path' => $brief->folder_path,
            'printers_deadline' => $brief->printers_deadline,
            'clients_deadline' => $brief->clients_deadline,
            'brief_notes' => $brief->additional_requirements,
            'status' => $designerId ? 'in_progress' : 'requested',
            'assigned_designer_id' => $designerId,
            'created_by' => $user->id,
            'claimed_at' => $designerId ? now() : null,
        ]);
        $ticket->addNote($user->id, 'status', 'Ticket created from Send to Production' . ($designerId ? ' · assigned to ' . optional(CrmUser::find($designerId))->name : ''));
        $mail = PrintReadyController::notify($ticket, 'created', $user);
        return " Print Ready ticket {$ticket->ticket_number} created" . ($designerId ? ' and assigned' : '') . '.' . $mail;
    }

    public function show($id)
    {
        $user = $this->user();
        if (!$this->canView($user)) abort(403);
        $order = CrmManualOrder::findOrFail($id);
        $brief = $order->productionBrief;
        abort_unless($brief, 404);
        return view('crm.orders.production_show', ['order' => $order, 'brief' => $brief, 'canEdit' => $user->isAdmin()]);
    }

    /** Admin-only edit of a sent brief. */
    public function edit($id)
    {
        $user = $this->user();
        if (!$user->isAdmin()) abort(403, 'Production briefs are locked once sent. Only an admin can edit them.');
        $order = CrmManualOrder::findOrFail($id);
        $brief = $order->productionBrief;
        abort_unless($brief, 404);
        return view('crm.orders.production_form', ['order' => $order, 'brief' => $brief, 'prefill' => []]);
    }

    public function update(Request $request, $id)
    {
        $user = $this->user();
        if (!$user->isAdmin()) abort(403);
        $order = CrmManualOrder::findOrFail($id);
        $brief = $order->productionBrief;
        abort_unless($brief, 404);
        $brief->fill($this->validated($request) + ['updated_by' => $user->id]);
        $brief->save();
        return redirect()->route('crm.orders.manual.production.show', $order->id)->with('success', 'Production brief updated.');
    }

    public function pdf($id)
    {
        $user = $this->user();
        if (!$this->canView($user)) abort(403);
        $order = CrmManualOrder::findOrFail($id);
        $brief = $order->productionBrief;
        abort_unless($brief, 404);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('crm.orders.production_pdf', ['brief' => $brief, 'order' => $order])->setPaper('a4');
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="production-job-' . preg_replace('/[^A-Za-z0-9_-]+/', '', $brief->job_number ?: $order->id) . '.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'job_number' => 'required|string|max:60',
            'brief_date' => 'required|date',
            'production_type' => 'required|string|max:60',
            'job_type' => 'required|string|max:40',
            'client_name' => 'required|string|max:190',
            'products' => 'required|array|min:1',
            'products.*.product' => 'required|string|max:190',
            'products.*.material' => 'nullable|string|max:190',
            'products.*.quantity' => 'nullable|string|max:60',
            'products.*.finish_size' => 'nullable|string|max:120',
            'products.*.lamination' => 'nullable|string|max:190',
            'products.*.printing' => 'nullable|string|max:190',
            'products.*.diecut_window' => 'nullable|string|max:190',
            'products.*.plastic_film' => 'nullable|string|max:190',
            'products.*.pasting' => 'nullable|string|max:190',
            'products.*.spot_uv' => 'nullable|string|max:190',
            'products.*.deboss_emboss' => 'nullable|string|max:190',
            'products.*.raised_ink_foiling' => 'nullable|string|max:190',
            'products.*.additional_requirements' => 'nullable|string|max:3000',
            'folder_path' => 'nullable|string|max:500',
            'job_forwarding_date' => 'nullable|date',
            'printers_deadline' => 'nullable|date',
            'clients_deadline' => 'nullable|date',
            'additional_requirements' => 'nullable|string|max:5000',
        ], [
            'products.required' => 'Add at least one product.',
            'products.*.product.required' => 'Each product block needs a product name.',
        ]);

        // Normalise product blocks: keep known keys only, trim, drop fully empty blocks.
        $products = [];
        foreach ((array) $data['products'] as $row) {
            $clean = [];
            foreach (array_keys(CrmManualOrderProductionBrief::PRODUCT_FIELDS) as $k) {
                $clean[$k] = trim((string) ($row[$k] ?? ''));
            }
            if ($clean['product'] !== '') $products[] = $clean;
        }
        $data['products'] = $products;
        return $data;
    }

    /** Prefill the briefing from the order (header + one product block per line item). */
    private function prefill(CrmManualOrder $order): array
    {
        $products = [];
        foreach ((array) $order->line_items as $it) {
            if (empty($it['box_style']) && empty($it['qty'])) continue;
            $size = implode(' x ', array_filter([$it['length'] ?? null, $it['width'] ?? null, $it['height'] ?? null], fn ($v) => $v !== null && $v !== ''));
            $finishing = (string) ($it['finishing'] ?? '');
            $pick = function (array $needles) use ($finishing) {
                foreach (preg_split('/\s*,\s*/', $finishing) as $part) {
                    foreach ($needles as $n) if ($part !== '' && stripos($part, $n) !== false) return $part;
                }
                return '';
            };
            $products[] = [
                'product' => $it['box_style'] ?? '',
                'material' => $it['stock'] ?? '',
                'quantity' => isset($it['qty']) ? rtrim(rtrim(number_format((float) $it['qty'], 2, '.', ''), '0'), '.') : '',
                'finish_size' => trim($size . ' ' . ($it['unit'] ?? '')),
                'lamination' => $pick(['lamination', 'soft touch', 'matte', 'gloss']),
                'printing' => $it['color'] ?? '',
                'diecut_window' => $pick(['window', 'die cut', 'die-cut']),
                'plastic_film' => $pick(['film', 'pvc']),
                'pasting' => $pick(['gluing', 'glue', 'pasting', 'lock bottom']),
                'spot_uv' => $pick(['uv']),
                'deboss_emboss' => $pick(['emboss', 'deboss']),
                'raised_ink_foiling' => $pick(['foil', 'raised']),
                'additional_requirements' => trim(($it['additional_info'] ?? '') . ($finishing ? ("\nFinishing: " . $finishing) : '')),
            ];
        }
        if (!$products) $products[] = array_fill_keys(array_keys(CrmManualOrderProductionBrief::PRODUCT_FIELDS), '');

        return [
            'job_number' => $order->invoice_number ? 'TCB-' . $order->invoice_number : 'TCB-' . $order->id,
            'brief_date' => now()->format('Y-m-d'),
            'client_name' => data_get($order->billing, 'name') ?: optional($order->customer)->name,
            'job_type' => 'Standard',
            'production_type' => '',
            'products' => $products,
            'additional_requirements' => (string) $order->additional_info,
        ];
    }

    private function notifyProduction(CrmManualOrderProductionBrief $brief, $user): string
    {
        try {
            $to = CrmUser::inWorkspace(null, ['production_manager'])->pluck('email')->filter()->unique()->values()->all();
            $cc = CrmUser::inWorkspace(null, ['admin'])->pluck('email')->filter()->unique()->values()->all();
            if (!$to && !$cc) return '';
            if (!$to) { $to = $cc; $cc = []; }
            Mail::to($to)->cc(array_values(array_diff($cc, $to)))->send(new ProductionBriefMail($brief, $user));
            return ' Production team notified by email (' . implode(', ', $to) . ').';
        } catch (\Throwable $e) {
            Log::warning('Production brief email failed for order #' . $brief->manual_order_id . ': ' . $e->getMessage());
            return ' (Email to production team failed: ' . $e->getMessage() . ')';
        }
    }
}
