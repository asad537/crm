<?php

namespace App\Http\Controllers\Crm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DesignTicketController extends Controller
{
    public function index(Request $request)
    {
        $user = \Auth::guard('crm')->user();
        $tab = request('tab', 'active');
        $query = \App\DesignRequirementTicket::with(['inquiry.inquiryAttachments', 'designer', 'requester']);
        if ($tab === 'history') {
            $query->whereIn('status', ['completed', 'forwarded', 'returned_to_sales']);
        } elseif ($tab === 'mine') {
            if ($user->isDesigner()) {
                $query->where('claimed_by', $user->id)->whereIn('status', ['open', 'on_hold']);
            } else {
                $query->whereRaw('1 = 0');
            }
        } else {
            // Shared queue contains only unclaimed tickets. Once a designer opens
            // one, it moves exclusively to that designer's "My Open Tickets" tab.
            $query->where('status', 'new')->whereNull('claimed_by');
        }
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $inquiryId = preg_match('/^(?:INQ[-\s]*)?0*(\d+)$/i', $search, $matches)
                ? (int) $matches[1]
                : null;
            $query->where(function ($searchQuery) use ($search, $inquiryId) {
                $searchQuery->where('ticket_number', 'like', "%{$search}%")
                    ->orWhereHas('inquiry', function ($inquiryQuery) use ($search, $inquiryId) {
                        $inquiryQuery->where(function ($detailQuery) use ($search, $inquiryId) {
                            $detailQuery->where('client_name', 'like', "%{$search}%")
                                ->orWhere('client_email', 'like', "%{$search}%")
                                ->orWhere('product_name', 'like', "%{$search}%");
                            if ($inquiryId) {
                                $detailQuery->orWhere('id', $inquiryId);
                            }
                        });
                    });
            });
        }
        $tickets = $query
            ->orderBy('updated_at', 'desc')
            ->paginate(20)->appends(request()->all());

        $completedThisMonth = \App\DesignRequirementTicket::where('claimed_by', $user->id)
            ->whereIn('status', ['completed', 'forwarded'])->where('completed_at', '>=', now()->startOfMonth())->count();

        return view('crm.design.index', compact('tickets', 'completedThisMonth', 'tab'));
    }

    public function claimRequirement($id)
    {
        $user = \Auth::guard('crm')->user();
        if (!$user->isDesigner()) abort(403, 'Only a Designer can open and claim a design ticket.');
        \Illuminate\Support\Facades\DB::transaction(function () use ($id, $user) {
            $ticket = \App\DesignRequirementTicket::where('id', $id)->lockForUpdate()->firstOrFail();
            if ($ticket->status !== 'new' && (int)$ticket->claimed_by !== (int)$user->id) {
                abort(409, 'This ticket has already been opened by another designer.');
            }
            $ticket->update(['status' => 'open', 'claimed_by' => $user->id, 'opened_at' => $ticket->opened_at ?: now()]);
        });
        return redirect()->route('crm.design_tickets.index', [
            'tab' => 'mine',
            'open_ticket' => $id,
        ])->with('success', 'Ticket opened and assigned to you.');
    }

    public function releaseRequirement($id)
    {
        $user = \Auth::guard('crm')->user();
        $ticket = \App\DesignRequirementTicket::findOrFail($id);
        if (!$user->isAdmin() && !$user->isSalesManager() && (int)$ticket->claimed_by !== (int)$user->id) abort(403);
        $ticket->update(['status' => 'new', 'claimed_by' => null, 'opened_at' => null]);
        return back()->with('success', 'Ticket released to the design queue.');
    }

    public function returnToSales(Request $request, $id)
    {
        $user = \Auth::guard('crm')->user();
        $data = $request->validate(['return_note' => 'required|string|max:3000']);
        $ticket = \App\DesignRequirementTicket::with('inquiry')->findOrFail($id);
        if (!$user->isAdmin() && !$user->isSalesManager()
            && (!$user->isDesigner() || (int)$ticket->claimed_by !== (int)$user->id)) {
            abort(403);
        }
        if (!in_array($ticket->status, ['open', 'on_hold'])) {
            return back()->with('error', 'Only an open design ticket can be returned to Sales.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($ticket, $user, $data) {
            $ticket->update([
                'status' => 'returned_to_sales',
                'return_note' => $data['return_note'],
                'returned_by' => $user->id,
                'returned_at' => now(),
            ]);
            $ticket->inquiry->update(['estimate_status' => 'returned_to_sales']);
        });

        return redirect()->route('crm.design_tickets.index', ['tab' => 'history'])
            ->with('success', 'Inquiry returned to the Sales Agent with your note.');
    }

    public function completeRequirement(Request $request, $id)
    {
        $user = \Auth::guard('crm')->user();
        $ticket = \App\DesignRequirementTicket::with(['inquiry.inquiryAttachments', 'inquiry.salesOrder'])->findOrFail($id);
        if (!$user->isAdmin() && !$user->isSalesManager() && (int)$ticket->claimed_by !== (int)$user->id) abort(403);

        if (strpos($ticket->ticket_number, 'ART-') === 0) {
            return $this->completeOrderArtwork($request, $ticket, $user);
        }

        $data = $request->validate([
            'open_length' => 'required|numeric|min:0.01',
            'open_width' => 'required|numeric|min:0.01',
            'unit' => 'required|string|in:mm,cm,inches',
            'designer_notes' => 'nullable|string|max:3000',
            'designer_files' => 'required|array|min:1|max:10', 'designer_files.*' => 'file|max:51200',
        ]);
        $data['open_size'] = $this->formatDimension($data['open_length']).' x '.$this->formatDimension($data['open_width']);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $data, $ticket, $user) {
            $inquiry = $ticket->inquiry;
            foreach ($request->file('designer_files', []) as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                if (in_array($extension, ['php','phtml','phar','exe','sh','bat','cmd','js'])) abort(422, 'Executable attachments are not allowed.');
                $size = $file->getSize(); $mime = $file->getClientMimeType(); $original = $file->getClientOriginalName();
                $directory = public_path('uploads/design-requirements'); if (!is_dir($directory)) mkdir($directory, 0755, true);
                $filename = uniqid('dsn_', true).'.'.$extension; $file->move($directory, $filename);
                \App\InquiryAttachment::create([
                    'crm_email_id' => $inquiry->id, 'design_ticket_id' => $ticket->id,
                    'uploaded_by' => $user->id, 'stage' => 'designer', 'original_name' => $original,
                    'file_path' => 'uploads/design-requirements/'.$filename, 'mime_type' => $mime, 'file_size' => $size,
                ]);
            }
            $inquiry->update(['open_size' => $data['open_size'], 'unit' => $data['unit'], 'estimate_status' => 'pending']);
            $estimator = \App\CrmUser::inWorkspace(null, ['estimator'])->orderBy('id')->first();
            if (!$estimator) abort(422, 'No estimator is configured for this project.');
            $paths = $inquiry->inquiryAttachments()->pluck('file_path')->all();
            $estimateData = [
                'ticket_number' => $inquiry->workflow_number,
                'crm_email_id' => $inquiry->id, 'client_name' => $inquiry->client_name,
                'client_email' => $inquiry->client_email, 'product_style' => $inquiry->product_name,
                'length' => $inquiry->length, 'width' => $inquiry->width, 'height' => $inquiry->height,
                'unit' => $data['unit'], 'stock' => $inquiry->stock, 'printing' => $inquiry->printing,
                'finish_size' => $inquiry->finish_size, 'flat_size' => $data['open_size'],
                'colors' => $inquiry->color, 'coating' => $inquiry->coating,
                'lamination' => $inquiry->lamination, 'die_cutting' => $inquiry->die,
                'gluing' => $inquiry->glue, 'shipping_region' => $inquiry->shipping_region,
                'currency' => $inquiry->invoice_currency ?: 'USD',
                'requirements' => trim(
                    ($inquiry->message ?: '').
                    (!empty($inquiry->custom_specs['Finishing Options']) ? "\nFinishing Options: ".implode(', ', $inquiry->custom_specs['Finishing Options']) : '').
                    "\nDesigner: ".($data['designer_notes'] ?? '')
                ),
                'attachments' => $paths, 'estimator_id' => null,
                'requested_by' => $ticket->requested_by, 'status' => 'pending',
                'returned_to' => null, 'return_note' => null, 'returned_by' => null, 'returned_at' => null,
            ];
            $estimate = $ticket->estimate_ticket_id
                ? \App\EstimateTicket::find($ticket->estimate_ticket_id)
                : null;
            if ($estimate) {
                unset($estimateData['ticket_number']);
                $estimate->update($estimateData);
            } else {
                $estimate = \App\EstimateTicket::create($estimateData);
            }
            if (!$estimate->options()->exists()) {
                foreach (($ticket->quantities ?: [$inquiry->quantity]) as $quantity) {
                    $estimate->options()->create(['quantity' => (int)$quantity]);
                }
            }
            $inquiry->update(['estimator_id' => null]);
            $ticket->update([
                'open_size' => $data['open_size'], 'unit' => $data['unit'],
                'designer_notes' => $data['designer_notes'] ?? null, 'status' => 'forwarded',
                'return_note' => null, 'returned_by' => null, 'returned_at' => null,
                'estimate_ticket_id' => $estimate->id, 'completed_at' => now(), 'forwarded_at' => now(),
            ]);
        });
        return redirect()->route('crm.design_tickets.index', ['tab' => 'history'])
            ->with('success', 'Design completed and estimate ticket sent automatically.');
    }

    private function formatDimension($value)
    {
        return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    }

    private function completeOrderArtwork(Request $request, $ticket, $user)
    {
        $data = $request->validate([
            'designer_notes' => 'nullable|string|max:3000',
            'designer_files' => 'required|array|min:1|max:10',
            'designer_files.*' => 'file|max:51200',
        ]);

        $order = optional($ticket->inquiry)->salesOrder;
        if (!$order || $order->status !== 'in_design') {
            return back()->with('error', 'This order is no longer waiting for final artwork.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $data, $ticket, $user, $order) {
            $version = (int) \App\ProofRevision::where('crm_email_id', $ticket->crm_email_id)
                ->max('version_number');

            foreach ($request->file('designer_files', []) as $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                if (in_array($extension, ['php','phtml','phar','exe','sh','bat','cmd','js'])) {
                    abort(422, 'Executable attachments are not allowed.');
                }

                $directory = public_path('uploads/proofs');
                if (!is_dir($directory)) mkdir($directory, 0755, true);
                $filename = uniqid('proof_', true) . '.' . $extension;
                $file->move($directory, $filename);

                \App\ProofRevision::create([
                    'crm_email_id' => $ticket->crm_email_id,
                    'version_number' => ++$version,
                    'file_path' => 'uploads/proofs/' . $filename,
                    'feedback_notes' => $data['designer_notes'] ?? null,
                    'status' => 'pending',
                    'uploaded_by' => $user->id,
                ]);
            }

            $order->update(['status' => 'design_approved']);
            $ticket->update([
                'status' => 'completed',
                'designer_notes' => $data['designer_notes'] ?? null,
                'completed_at' => now(),
            ]);

            \App\Services\WorkflowService::logApproval(
                $ticket->inquiry,
                'proof_uploaded',
                'approved',
                'Designer uploaded final artwork for Sales Agent review.'
            );
        });

        return redirect()->route('crm.design_tickets.index', ['tab' => 'history'])
            ->with('success', 'Final artwork sent to the Sales Agent for approval.');
    }

    public function uploadProof(Request $request, $id)
    {
        $request->validate([
            'proof_file' => 'required|file|mimes:pdf,jpg,jpeg,png,ai|max:50000',
            'designer_notes' => 'nullable|string'
        ]);

        $ticket = \App\SalesOrder::findOrFail($id);

        if ($request->hasFile('proof_file')) {
            $file = $request->file('proof_file');
            $filename = time() . '_proof_' . $file->getClientOriginalName();
            $file->move('uploads/proofs', $filename);

            // Create a proof revision
            $proof = \App\ProofRevision::create([
                'crm_email_id' => $ticket->crm_email_id,
                'version_number' => \App\ProofRevision::where('crm_email_id', $ticket->crm_email_id)->max('version_number') + 1,
                'file_path' => 'uploads/proofs/' . $filename,
                'feedback_notes' => 'Designer Notes: ' . $request->designer_notes,
                'status' => 'pending',
                'uploaded_by' => \Auth::guard('crm')->id()
            ]);

            // Delete original artwork file from server
            if ($ticket->artwork_file_path && file_exists(public_path($ticket->artwork_file_path))) {
                unlink(public_path($ticket->artwork_file_path));
            }

            $ticket->update([
                'status' => 'design_approved', // Mark design as done and returned to sales
                'artwork_file_path' => null // Clear original artwork
            ]);

            \App\Services\WorkflowService::logApproval(
                $ticket->lead,
                'proof_uploaded',
                'approved',
                'Designer uploaded a new proof for review.'
            );
        }

        return back()->with('success', 'Proof uploaded successfully! The Sales Agent has been notified.');
    }
}
