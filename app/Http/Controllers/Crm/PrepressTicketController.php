<?php

namespace App\Http\Controllers\Crm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PrepressTicketController extends Controller
{
    private $checks = [
        'file_check' => 'File Check',
        'font_check' => 'Font Check',
        'bleed_check' => 'Bleed Check',
        'color_check' => 'CMYK / Pantone Color Check',
        'resolution_check' => 'Resolution Check',
        'barcode_check' => 'Barcode Check',
        'inspection' => 'Inspection',
        'ctp_plate_making' => 'CTP Plate Making',
    ];

    public function index(\Illuminate\Http\Request $request)
    {
        $this->authorizePrepress();

        // Eager-load lead (was fetched via ->with already). Added pagination (was ->get() — could return hundreds of rows).
        $tickets = \App\SalesOrder::with('lead')
            ->where('status', 'prepress')
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        // Facilities list is tiny and rarely changes — cache 10 min.
        $facilities = \Illuminate\Support\Facades\Cache::remember(
            'crm:prepress:facilities',
            600,
            function () {
                return \App\ProductionFacility::where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name']);
            }
        );

        return view('crm.prepress.index', [
            'tickets' => $tickets,
            'checks' => $this->checks,
            'facilities' => $facilities,
        ]);
    }

    public function createPlate(Request $request, $id)
    {
        $this->authorizePrepress();

        $request->validate([
            'production_facility_id' => 'required|exists:production_facilities,id',
            'color_match_check' => 'required|accepted',
        ], [
            'color_match_check.accepted' => 'You must confirm the color match before creating a plate.',
        ]);

        $ticket = \App\SalesOrder::findOrFail($id);
        if ($ticket->status !== 'prepress') {
            abort(422, 'Only prepress tickets can have plates created.');
        }

        $ticket->update([
            'is_plate_created' => true,
            'production_facility_id' => $request->production_facility_id,
        ]);

        return back()->with('success', 'Plate created and Facility assigned! Please complete the prepress checklist now.')->with('open_ticket', $id);
    }

    public function complete(Request $request, $id)
    {
        $this->authorizePrepress();

        $request->validate([
            'checks' => 'required|array',
            'checks.*' => 'in:' . implode(',', array_keys($this->checks)),
            'prepress_notes' => 'nullable|string',
            'qc_sheet' => 'nullable|file|max:51200',
        ]);

        $missingChecks = array_diff(array_keys($this->checks), $request->checks);
        if (!empty($missingChecks)) {
            return back()->with('error', 'Please complete all prepress checks before sending to production.');
        }

        $ticket = \App\SalesOrder::findOrFail($id);
        if ($ticket->status !== 'prepress') {
            abort(422, 'Only prepress tickets can be sent to production.');
        }

        if (!$ticket->is_plate_created || !$ticket->production_facility_id) {
            return back()->with('error', 'Please create a plate and select a facility first before completing the checklist.');
        }

        DB::transaction(function () use ($ticket, $request) {
            $updateData = [
                'status' => 'in_production',
                'prepress_checks' => $request->checks,
                'prepress_notes' => $request->prepress_notes,
            ];

            if ($request->hasFile('qc_sheet')) {
                $file = $request->file('qc_sheet');
                $filename = time() . '_qc_sheet_' . $ticket->id . '.' . $file->getClientOriginalExtension();
                $file->move('uploads/qc_sheets', $filename);
                $updateData['qc_sheet_file_path'] = 'uploads/qc_sheets/' . $filename;
            }

            $ticket->update($updateData);

            $job = \App\ProductionJob::firstOrCreate(
                ['sales_order_id' => $ticket->id],
                [
                    'status' => 'pending_planning',
                    'planned_quantity' => (int) ($ticket->lead->quantity ?: $ticket->lead->order_quantity ?: 0),
                    'production_facility_id' => $ticket->production_facility_id,
                ]
            );

            if ($job->wasRecentlyCreated) {
                \App\ProductionJobLog::create([
                    'production_job_id' => $job->id,
                    'crm_user_id' => \Auth::guard('crm')->id(),
                    'from_status' => null,
                    'to_status' => 'pending_planning',
                    'notes' => 'Prepress approved. Plate created and sent to Production Manager for planning.',
                ]);
            } else {
                $job->update([
                    'status' => 'pending_planning',
                    'production_facility_id' => $ticket->production_facility_id,
                    'press_operator_id' => null, // Clear press operator if any
                ]);
                \App\ProductionJobLog::create([
                    'production_job_id' => $job->id,
                    'crm_user_id' => \Auth::guard('crm')->id(),
                    'from_status' => $job->status,
                    'to_status' => 'pending_planning',
                    'notes' => 'Prepress approved. Plate created and sent to Production Manager for planning.',
                ]);
            }

            \App\Services\WorkflowService::logApproval(
                $ticket->lead,
                'prepress_completed',
                'approved',
                'Prepress checks completed. Order sent to production.'
            );
        });

        return back()->with('success', 'Prepress completed. Production job ticket is ready.');
    }

    public function sendBack(Request $request, $id)
    {
        $this->authorizePrepress();

        $request->validate([
            'revision_notes'       => 'required|string',
            'revision_attachment'  => 'nullable|file|max:20480', // max 20MB
        ]);

        $ticket = \App\SalesOrder::findOrFail($id);

        $updateData = [
            'status'       => 'in_design',
            'design_notes' => "Prepress Revision Required: " . $request->revision_notes . "\n\n" . $ticket->design_notes,
        ];

        // Handle optional attachment
        if ($request->hasFile('revision_attachment')) {
            $file     = $request->file('revision_attachment');
            $filename = time() . '_prepress_revision_' . $id . '.' . $file->getClientOriginalExtension();
            $file->move('uploads/prepress_revisions', $filename);
            $updateData['prepress_revision_attachment'] = 'uploads/prepress_revisions/' . $filename;
        } else {
            // Clear previous attachment when sending back again without a new one
            $updateData['prepress_revision_attachment'] = null;
        }

        $ticket->update($updateData);

        \App\Services\WorkflowService::logApproval(
            $ticket->lead,
            'prepress_revision_requested',
            'revision_requested',
            'Prepress sent the order back to design: ' . $request->revision_notes
        );

        return back()->with('success', 'Ticket sent back to Design for revision.');
    }

    private function authorizePrepress()
    {
        $user = \Auth::guard('crm')->user();
        if (!$user || (!$user->isAdmin() && !$user->isPrepress())) {
            abort(403, 'Unauthorized access.');
        }
    }
}
