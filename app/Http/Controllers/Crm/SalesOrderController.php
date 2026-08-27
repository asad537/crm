<?php

namespace App\Http\Controllers\Crm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = \Auth::guard('crm')->user();
        $query = \App\SalesOrder::with('lead');

        if ($user->isSales()) {
            $query->where('sales_agent_id', $user->id);
        }

        $salesOrders = $query->orderBy('created_at', 'desc')->paginate(10);

        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('crm.sales_orders.table', compact('salesOrders'))->render();
        }

        return view('crm.sales_orders.index', compact('salesOrders'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'crm_email_id' => 'required|exists:crm_emails,id',
            'payment_term' => 'required|in:credit,100_deposit,50_advance',
            'credit_days' => 'nullable|integer|min:1',
            'estimate_option_index' => 'nullable|integer|min:0',
            'sales_offer_price' => 'required|numeric|min:0',
        ]);

        $user = \Auth::guard('crm')->user();
        $crmEmail = \App\CrmEmail::with('salesOrder')->findOrFail($request->crm_email_id);

        $mayCreateOrder = $user->isAdmin()
            || $user->isSalesManager()
            || ($user->isSales() && (int) $crmEmail->assigned_to === (int) $user->id);
        abort_unless($mayCreateOrder, 403, 'You are not allowed to create an order for this lead.');

        if ($crmEmail->salesOrder) {
            return back()->with('error', 'A sales order already exists for this lead.');
        }

        $estimateOptions = is_array($crmEmail->estimate_quantity_options)
            ? array_values($crmEmail->estimate_quantity_options)
            : [];

        if (empty($estimateOptions)) {
            $ticket = \App\EstimateTicket::with('options')
                ->where('crm_email_id', $crmEmail->id)
                ->whereIn('status', ['estimated', 'completed'])
                ->latest('id')
                ->first();

            if ($ticket) {
                $estimateOptions = $ticket->options->map(function ($option) {
                    $approvedTotal = $option->offer_price !== null
                        ? (float) $option->offer_price
                        : ($option->discounted_price !== null ? (float) $option->discounted_price : (float) $option->total_price);
                    return [
                        'quantity' => (int) $option->quantity,
                        'price' => $approvedTotal,
                        'unit_price' => $option->quantity > 0 ? $approvedTotal / $option->quantity : 0,
                    ];
                })->values()->all();
            }
        }

        if ($crmEmail->estimate_status !== 'approved' && empty($estimateOptions)) {
            return back()->with('error', 'A completed estimate is required before creating a sales order.');
        }

        $optionIndex = (int) $request->input('estimate_option_index', 0);
        $selectedOption = $estimateOptions[$optionIndex] ?? null;
        if (!empty($estimateOptions) && !$selectedOption) {
            return back()->withErrors(['estimate_option_index' => 'Please select a valid estimate option.'])->withInput();
        }

        $teamLeadFloor = (float) ($selectedOption['team_lead_price'] ?? $selectedOption['price'] ?? $crmEmail->estimated_price ?? 0);
        $salesOffer = round((float) $request->sales_offer_price, 2);
        if ($salesOffer + 0.00001 < $teamLeadFloor) {
            return back()->withErrors([
                'sales_offer_price' => 'Sales offer cannot be lower than the Team Lead approved price of '
                    .($crmEmail->invoice_currency ?: 'USD').' '.number_format($teamLeadFloor, 2).'.',
            ])->withInput();
        }

        $salesOrder = \App\SalesOrder::create([
            'crm_email_id' => $request->crm_email_id,
            'sales_agent_id' => $user->id,
            'payment_term' => $request->payment_term,
            'credit_days' => $request->payment_term === 'credit' ? $request->credit_days : null,
            'payment_status' => 'pending',
            'status' => 'pending_payment'
        ]);

        $qty = $selectedOption['quantity'] ?? ($crmEmail->quantity ?: 1);
        $selectedTotal = $salesOffer;
        $unitPrice = $selectedOption['unit_price'] ?? ($qty > 0 ? ($selectedTotal / $qty) : $selectedTotal);
        $unitPrice = $qty > 0 ? ($selectedTotal / $qty) : $selectedTotal;

        $crmEmail->update([
            'is_rejected' => false,
            'order_price' => $unitPrice,
            'order_quantity' => $qty,
            'payment_status' => 'Unpaid'
        ]);

        \App\Services\WorkflowService::logApproval(
            $crmEmail,
            'sales_order_created',
            'pending',
            'Sales order created. Team Lead floor: '.($crmEmail->invoice_currency ?: 'USD').' '
                .number_format($teamLeadFloor, 2).'; Sales offer: '
                .($crmEmail->invoice_currency ?: 'USD').' '.number_format($salesOffer, 2)
                .'; Payment term: '.str_replace('_', ' ', $request->payment_term)
        );

        return redirect()->route('crm.sales_orders.index')
            ->with('success', 'Sales order request created. It will appear in Orders after payment or credit approval.');
    }

    public function updatePaymentStatus(Request $request, $id)
    {
        $order = \App\SalesOrder::findOrFail($id);
        
        $order->update([
            'payment_status' => $order->payment_term === 'credit' ? 'approved' : 'received',
            'status' => 'pending_artwork'
        ]);

        if ($order->lead) {
            $order->lead->update([
                'status' => 'Order Done',
                'payment_status' => 'Paid',
                'order_marked_at' => now(),
                'order_marked_by' => optional($order->agent)->name
                    ?: optional(\Auth::guard('crm')->user())->name,
            ]);
        }

        \App\Services\WorkflowService::logApproval(
            $order->lead,
            'payment_cleared',
            'approved',
            'Payment/Credit cleared. Waiting for artwork upload.'
        );

        return back()->with('success', 'Payment status updated! You can now upload the artwork.');
    }

    public function uploadArtwork(Request $request, $id)
    {
        $request->validate([
            'artwork_file' => 'required|file|mimes:zip,pdf,ai,eps,png,jpg,jpeg|max:50000',
            'design_notes' => 'nullable|string'
        ]);

        $order = \App\SalesOrder::with('lead')->findOrFail($id);

        if ($request->hasFile('artwork_file')) {
            $file = $request->file('artwork_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();
            $fileSize = $file->getSize();
            $file->move('uploads/artwork', $filename);

            \Illuminate\Support\Facades\DB::transaction(function () use (
                $order,
                $request,
                $filename,
                $originalName,
                $mimeType,
                $fileSize
            ) {
                $order->update([
                    'artwork_file_path' => 'uploads/artwork/' . $filename,
                    'design_notes' => $request->design_notes,
                    'status' => 'in_design'
                ]);

                $designTicket = \App\DesignRequirementTicket::create([
                    'ticket_number' => 'ART-' . now()->format('ymd') . '-' . strtoupper(substr(uniqid(), -5)),
                    'crm_email_id' => $order->crm_email_id,
                    'requested_by' => \Auth::guard('crm')->id(),
                    'status' => 'new',
                    'quantities' => [(int) ($order->lead->order_quantity ?: $order->lead->quantity ?: 1)],
                    'open_size' => $order->lead->open_size,
                    'unit' => $order->lead->unit,
                    'designer_notes' => $request->design_notes,
                ]);

                \App\InquiryAttachment::create([
                    'crm_email_id' => $order->crm_email_id,
                    'design_ticket_id' => $designTicket->id,
                    'uploaded_by' => \Auth::guard('crm')->id(),
                    'stage' => 'order_artwork',
                    'original_name' => $originalName,
                    'file_path' => 'uploads/artwork/' . $filename,
                    'mime_type' => $mimeType,
                    'file_size' => $fileSize,
                ]);
            });

            \App\Services\WorkflowService::logApproval(
                $order->lead,
                'artwork_uploaded',
                'approved',
                'Final customer artwork uploaded and a ticket was added to the Design queue.'
            );
        }

        return back()->with('success', 'Artwork uploaded and sent to the Designer queue.');
    }

    public function approveProof(Request $request, $id)
    {
        $order = \App\SalesOrder::findOrFail($id);
        
        $order->update([
            'status' => 'prepress'
        ]);

        \App\Services\WorkflowService::logApproval(
            $order->lead,
            'client_proof_approved',
            'approved',
            'Sales Agent confirmed that client approved the design proof. Order sent to Prepress.'
        );

        return back()->with('success', 'Proof approved! The order has been sent to Prepress.');
    }

    public function rejectProof(Request $request, $id)
    {
        $request->validate([
            'revision_notes' => 'required|string',
            'revision_attachment' => 'nullable|file|max:20480',
        ]);

        $order = \App\SalesOrder::findOrFail($id);

        $newNotes = "Revision Requested: " . $request->revision_notes . "\n\n" . $order->design_notes;

        $updateData = [
            'status' => 'in_design',
            'design_notes' => $newNotes
        ];

        if ($request->hasFile('revision_attachment')) {
            $file     = $request->file('revision_attachment');
            $filename = time() . '_sales_revision_' . $id . '.' . $file->getClientOriginalExtension();
            $file->move('uploads/sales_revisions', $filename);
            $updateData['sales_revision_attachment'] = 'uploads/sales_revisions/' . $filename;
        } else {
            $updateData['sales_revision_attachment'] = null;
        }

        $order->update($updateData);

        \App\Services\WorkflowService::logApproval(
            $order->lead,
            'proof_rejected',
            'revision_requested',
            'Sales Agent requested revisions: ' . $request->revision_notes
        );

        return back()->with('success', 'Revision requested! The Design Department has been notified.');
    }
}
