<?php

namespace App\Http\Controllers\Crm;

use App\CrmUser;
use App\Http\Controllers\Controller;
use App\ProductionFacility;
use App\ProductionFirstSheetCheck;
use App\ProductionJob;
use App\ProductionJobLog;
use App\ProductionMachine;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use App\Mail\FirstSheetRejectedMail;

class ProductionJobController extends Controller
{
    private $printingMethods = [
        'offset' => 'Offset Printing',
        'digital' => 'Digital Printing',
        'large_format' => 'Large Format Printing',
        'uv' => 'UV Printing',
        'screen' => 'Screen Printing',
    ];

    private $gluingTypes = [
        'straight_line_glue' => 'Straight Line Glue',
        'auto_lock_bottom' => 'Auto Lock Bottom',
        '4_corner_glue' => '4 Corner Glue',
        '5_corner_glue' => '5 Corner Glue',
        '6_corner_glue' => '6 Corner Glue',
        'burger_box_glue' => 'Burger Box Glue',
        'gift_box_assembly' => 'Gift Box Assembly',
    ];

    public function index()
    {
        $user = $this->authorizedUser();
        $query = $this->baseJobQuery();

        if ($user->isPressOperator()) {
            $query->where('press_operator_id', $user->id);
        } elseif ($user->isQC()) {
            $query->whereIn('status', [
                'first_sheet_review',
            ]);
        } elseif ($user->isProductionManager() && $user->production_facility_id) {
            $query->where('production_facility_id', $user->production_facility_id);
        }

        return $this->renderJobBoard($query, [
            'pageTitle' => 'Production Jobs',
            'pageEyebrow' => 'Operations Center',
            'pageSubtitle' => 'Plan, assign and monitor every active print job from one workspace.',
            'queueTitle' => 'Job Queue',
        ]);
    }

    public function pressTickets()
    {
        $user = $this->authorizedUser();
        if (!$user->isAdmin() && !$user->isProductionManager() && !$user->isPressOperator()) {
            abort(403, 'Only Press Operator, Production Manager or Admin can view press tickets.');
        }

        $query = $this->baseJobQuery()
            ->whereIn('status', ['scheduled', 'press_setup', 'adjustments_required', 'production_ready', 'full_production', 'in_process_checks']);

        if ($user->isPressOperator()) {
            $query->where('press_operator_id', $user->id);
        }

        return $this->renderJobBoard($query, [
            'pageTitle' => 'Press Tickets',
            'pageEyebrow' => 'Press Operation',
            'pageSubtitle' => 'Assigned press tickets with order specs, setup notes, first-sheet pulls and production run actions.',
            'queueTitle' => 'Assigned Press Queue',
        ]);
    }

    public function qcTickets()
    {
        $user = $this->authorizedUser();
        if (!$user->isAdmin() && !$user->isProductionManager() && !$user->isQC()) {
            abort(403, 'Only QC, Production Manager or Admin can view QC tickets.');
        }

        $query = $this->baseJobQuery()
            ->where(function($q) {
                $q->where('status', 'final_quality_control')
                  ->orWhere(function($sq) {
                      $sq->where('status', 'first_sheet_review')
                         ->whereHas('firstSheetChecks', function($cq) {
                             $cq->whereIn('status', ['pending', 'pending_qc']);
                         });
                  });
            });

        if ($user->isQC() && $this->hasQcAssignmentColumn()) {
            $query->where('qc_inspector_id', $user->id);
        }

        return $this->renderJobBoard($query, [
            'pageTitle' => 'QC Tickets',
            'pageEyebrow' => 'Quality Control',
            'pageSubtitle' => 'First-sheet QC and final quality-control tickets with full job packet and inspection checklists.',
            'queueTitle' => 'QC Inspection Queue',
        ]);
    }

    public function supervisorTickets()
    {
        $user = $this->authorizedUser();
        if (!$user->isAdmin() && !$user->isProductionManager()) {
            abort(403, 'Only Production Manager or Admin can view supervisor tickets.');
        }

        $query = $this->baseJobQuery()
            ->whereIn('status', [
                'supervisor_review',
                'production_ready',
                'full_production',
                'in_process_checks',
                'final_quality_control',
            ]);

        if ($user->isProductionManager() && $user->production_facility_id) {
            $query->where('production_facility_id', $user->production_facility_id);
        }

        return $this->renderJobBoard($query, [
            'pageTitle' => 'Supervisor Tickets',
            'pageEyebrow' => 'Production Supervision',
            'pageSubtitle' => 'Review production readiness, active runs and jobs awaiting final quality control.',
            'queueTitle' => 'Supervisor Queue',
        ]);
    }


    public function show($id)
    {
        $user = $this->authorizedUser();
        $job = $this->baseJobQuery()->findOrFail($id);

        if ($user->isPressOperator() && (int) $job->press_operator_id !== (int) $user->id) {
            abort(403, 'This press ticket is not assigned to you.');
        }
        if ($user->isQC() && $this->hasQcAssignmentColumn() && (int) $job->qc_inspector_id !== (int) $user->id) {
            abort(403, 'This QC ticket is not assigned to you.');
        }

        return view('crm.production.show', [
            'job' => $job,
            'facilities' => ProductionFacility::where('is_active', true)->with('machines')->get(),
            'machines' => ProductionMachine::with('facility')->where('status', '!=', 'inactive')->get(),
            'operators' => CrmUser::inWorkspace(null, ['press_operator'])->whereNotNull('production_facility_id')->orderBy('name')->get(),
            'managers' => CrmUser::inWorkspace(null, ['production_manager'])->whereNotNull('production_facility_id')->orderBy('name')->get(),
            'qcs' => CrmUser::inWorkspace(null, ['qc'])->orderBy('name')->get(),
            'hasQcAssignment' => $this->hasQcAssignmentColumn(),
            'printingMethods' => $this->printingMethods,
            'gluingTypes' => $this->gluingTypes,
        ]);
    }

    private function baseJobQuery()
    {
        return ProductionJob::with([
            'salesOrder.lead', 'facility', 'machine', 'manager', 'operator',
            'qcInspector', 'firstSheetChecks.inspector', 'logs.user',
        ]);
    }

    private function hasQcAssignmentColumn()
    {
        return Schema::hasColumn('production_jobs', 'qc_inspector_id');
    }


    private function supervisorStatuses()
    {
        return [
            'scheduled',
            'press_setup',
            'first_sheet_review',
            'adjustments_required',
            'production_ready',
            'full_production',
            'in_process_checks',
            'coating_options',
            'lamination_options',
            'die_cutting',
            'stripping',
            'blank_separation',
            'gluing',
            'warehouse_ready',
            'production_completed'
        ];
    }

    private function postProductionStatuses()
    {
        return array_merge(['full_production'], $this->supervisorStatuses());
    }

    private function renderJobBoard($query, array $viewData = [])
    {
        return view('crm.production.index', $viewData + [
            'jobs' => $query->orderBy('updated_at', 'desc')->get(),
            'facilities' => ProductionFacility::where('is_active', true)->with('machines')->get(),
            'machines' => ProductionMachine::with('facility')->where('status', '!=', 'inactive')->get(),
            'operators' => CrmUser::inWorkspace(null, ['press_operator'])->whereNotNull('production_facility_id')->orderBy('name')->get(),
            'managers' => CrmUser::inWorkspace(null, ['production_manager'])->whereNotNull('production_facility_id')->orderBy('name')->get(),
            'qcs' => CrmUser::inWorkspace(null, ['qc'])->orderBy('name')->get(),
            'hasQcAssignment' => $this->hasQcAssignmentColumn(),
            'printingMethods' => $this->printingMethods,
            'gluingTypes' => $this->gluingTypes,
        ]);
    }

    public function storeMachine(Request $request)
    {
        $user = $this->authorizedUser();
        $this->requireManager($user);

        $request->validate([
            'production_facility_id' => 'required|exists:production_facilities,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:production_machines,code',
            'printing_method' => 'required|in:' . implode(',', array_keys($this->printingMethods)),
        ]);

        ProductionMachine::create($request->only([
            'production_facility_id', 'name', 'code', 'printing_method',
        ]) + ['status' => 'available']);

        return back()->with('success', 'Production machine added successfully.');
    }

    public function plan(Request $request, $id)
    {
        $user = $this->authorizedUser();
        $this->requireManager($user);

        $request->validate([
            'production_facility_id' => 'required|exists:production_facilities,id',
            'production_machine_id' => 'required|exists:production_machines,id',
            'press_operator_id' => 'required|exists:crm_users,id',
            'qc_inspector_id' => ($this->hasQcAssignmentColumn() ? 'required|' : 'nullable|') . 'exists:crm_users,id',
            'printing_method' => 'required|in:' . implode(',', array_keys($this->printingMethods)),
            'gluing_type' => 'nullable|in:' . implode(',', array_keys($this->gluingTypes)),
            'priority' => 'required|in:low,normal,high,urgent',
            'planned_quantity' => 'required|integer|min:1',
            'scheduled_start_at' => 'required|date',
            'scheduled_due_at' => 'required|date|after_or_equal:scheduled_start_at',
            'planning_notes' => 'nullable|string|max:2000',
        ]);

        $job = ProductionJob::findOrFail($id);
        $this->requireStatus($job, ['pending_planning', 'scheduled']);

        $machine = ProductionMachine::findOrFail($request->production_machine_id);
        if ((int) $machine->production_facility_id !== (int) $request->production_facility_id ||
            $machine->printing_method !== $request->printing_method) {
            return back()->with('error', 'Selected machine does not match the facility or printing method.');
        }

        $manager = CrmUser::inWorkspace(null, ['production_manager'])->where('production_facility_id', $request->production_facility_id)->first();
        if (!$manager) {
            $manager = CrmUser::inWorkspace(null, ['production_manager'])->first();
        }
        if (!$manager) {
            return back()->with('error', 'No Production Manager found in the system.');
        }

        $supervisor = null;

        $operator = CrmUser::findOrFail($request->press_operator_id);
        $qcInspector = $this->hasQcAssignmentColumn() ? CrmUser::findOrFail($request->qc_inspector_id) : null;
        
        if (!$operator->isPressOperator() || ($qcInspector && !$qcInspector->isQC())) {
            return back()->with('error', 'Please select valid press operator and QC users.');
        }
        if ((int) $operator->production_facility_id !== (int) $request->production_facility_id) {
            return back()->with('error', 'Press Operator must belong to the selected facility.');
        }
        if ($qcInspector && $qcInspector->production_facility_id &&
            (int) $qcInspector->production_facility_id !== (int) $request->production_facility_id) {
            return back()->with('error', 'QC Inspector must belong to the selected facility.');
        }

        $fromStatus = $job->status;
        DB::transaction(function () use ($job, $request, $user, $fromStatus, $manager, $supervisor) {
            $planningData = $request->only([
                'production_facility_id', 'production_machine_id',
                'press_operator_id', 'printing_method', 'gluing_type', 'priority', 'planned_quantity',
                'scheduled_start_at', 'scheduled_due_at', 'planning_notes',
            ]);
            $planningData['production_manager_id'] = $manager->id;
            if ($this->hasQcAssignmentColumn()) {
                $planningData['qc_inspector_id'] = $request->qc_inspector_id;
            }

            $planningData['scheduled_start_at'] = Carbon::parse($planningData['scheduled_start_at'])->format('Y-m-d H:i:s');
            $planningData['scheduled_due_at'] = Carbon::parse($planningData['scheduled_due_at'])->format('Y-m-d H:i:s');

            $job->update($planningData + ['status' => 'scheduled']);

            $this->logTransition($job, $fromStatus, 'scheduled', 'Production job planned and assigned.', $user->id);
        });

        return back()->with('success', 'Production job planned and assigned successfully.');
    }

    public function startSetup(Request $request, $id)
    {
        $user = $this->authorizedUser();
        $job = ProductionJob::findOrFail($id);
        $this->requireOperator($job, $user);
        $this->requireStatus($job, ['scheduled']);

        $request->validate(['press_setup_notes' => 'required|string|max:2000']);

        $fromStatus = $job->status;
        $job->update([
            'status' => 'press_setup',
            'actual_start_at' => now(),
            'press_setup_notes' => $request->press_setup_notes,
        ]);
        $this->logTransition($job, $fromStatus, 'press_setup', $request->press_setup_notes, $user->id);

        return back()->with('success', 'Press setup started.');
    }

    public function submitFirstSheet(Request $request, $id)
    {
        $user = $this->authorizedUser();
        $job = ProductionJob::findOrFail($id);
        $this->requireOperator($job, $user);
        $this->requireStatus($job, ['press_setup', 'adjustments_required']);

        $request->validate([
            'notes' => 'required|string|max:2000',
            'first_sheet_file' => 'nullable|file|max:51200'
        ]);
        $attempt = ((int) $job->firstSheetChecks()->max('attempt_number')) + 1;

        $fromStatus = $job->status;
        DB::transaction(function () use ($job, $request, $user, $attempt, $fromStatus) {
            ProductionFirstSheetCheck::create([
                'production_job_id' => $job->id,
                'attempt_number' => $attempt,
                'status' => 'pending_qc',
                'notes' => $request->notes,
            ]);

            $updateData = [
                'status' => 'first_sheet_review',
                'adjustment_notes' => $job->status === 'adjustments_required' ? $request->notes : $job->adjustment_notes,
            ];

            if ($request->hasFile('first_sheet_file')) {
                $file = $request->file('first_sheet_file');
                $filename = time() . '_first_sheet_' . $job->id . '.' . $file->getClientOriginalExtension();
                $file->move('uploads/qc_sheets', $filename);
                $updateData['first_sheet_file_path'] = 'uploads/qc_sheets/' . $filename;
            }

            $job->update($updateData);
            $this->logTransition($job, $fromStatus, 'first_sheet_review', 'First sheet attempt #' . $attempt . ' submitted for QC.', $user->id);
        });

        return back()->with('success', 'First sheet submitted to QC.');
    }

    public function reviewFirstSheet(Request $request, $id)
    {
        $user = $this->authorizedUser();
        if (!$user->isAdmin() && !$user->isQC() && !$user->isProductionManager()) {
            abort(403, 'Only QC, Production Manager, or Admin can review the first sheet.');
        }

        $request->validate([
            'review_stage' => 'required|in:qc',
            'notes' => 'nullable|string|max:2000',
            'proof_match_passed' => 'nullable|boolean',
            'cmyk_density_passed' => 'nullable|boolean',
            'spot_color_passed' => 'nullable|boolean',
            'registration_passed' => 'nullable|boolean',
            'print_defect_passed' => 'nullable|boolean',
            'supervisor_approved' => 'nullable|boolean',
        ]);

        $job = ProductionJob::findOrFail($id);
        $this->requireStatus($job, ['first_sheet_review']);
        $check = $job->firstSheetChecks()
            ->whereIn('status', ['pending', 'pending_qc', 'qc_passed'])
            ->orderBy('attempt_number', 'desc')
            ->firstOrFail();

        if ($request->review_stage === 'qc') {
            if (!$user->isAdmin() && !$user->isQC()) {
                abort(403, 'Only QC or Admin can complete the technical first-sheet checks.');
            }
            if ($user->isQC() && $this->hasQcAssignmentColumn() && (int) $job->qc_inspector_id !== (int) $user->id) {
                abort(403, 'This QC ticket is not assigned to you.');
            }
            if (!in_array($check->status, ['pending', 'pending_qc'], true)) {
                return back()->with('error', 'This first sheet is not waiting for QC checks.');
            }

            $results = [
                'proof_match_passed' => $request->has('proof_match_passed'),
                'cmyk_density_passed' => $request->has('cmyk_density_passed'),
                'spot_color_passed' => $request->has('spot_color_passed'),
                'registration_passed' => $request->has('registration_passed'),
                'print_defect_passed' => $request->has('print_defect_passed'),
            ];
            $passed = !in_array(false, $results, true);

            if ($request->qc_action === 'approve' && !$passed) {
                return back()->with('error', 'All checks must be marked as passed to approve. If you wish to reject, click Reject.');
            }
            if ($request->qc_action === 'reject') {
                $passed = false;
            }

            if (!$passed && !$request->filled('notes')) {
                return back()->with('error', 'QC rejection reason is required when rejecting.');
            }

            $fromStatus = $job->status;
            DB::transaction(function () use ($job, $check, $results, $request, $user, $passed, $fromStatus) {
                $check->update($results + [
                    'qc_inspector_id' => $user->id,
                    'status' => $passed ? 'qc_passed' : 'rejected',
                    'notes' => $request->notes ?: $check->notes,
                ]);

                if ($passed) {
                    $job->update(['status' => 'sales_agent_review']);
                    $this->logTransition($job, $fromStatus, 'sales_agent_review', 'QC checks passed. Waiting for Sales Agent approval.', $user->id);
                    return;
                }

                $job->update([
                    'status' => 'adjustments_required',
                    'adjustment_notes' => $request->notes,
                ]);
                $this->logTransition($job, $fromStatus, 'adjustments_required', 'QC rejected first sheet: ' . $request->notes, $user->id);

                // Send Email to Admins and Sales Agent
                $adminEmails = \App\CrmUser::inWorkspace(null, ['admin'])->pluck('email')->toArray();
                $agentEmail = $job->salesOrder && $job->salesOrder->agent ? $job->salesOrder->agent->email : null;
                $emails = $adminEmails;
                if ($agentEmail && !in_array($agentEmail, $emails)) {
                    $emails[] = $agentEmail;
                }
                if (!empty($emails)) {
                    Mail::to($emails)->send(new FirstSheetRejectedMail($job, $request->notes));
                }
            });

            return back()->with($passed ? 'success' : 'error', $passed
                ? 'QC checks passed. Sales Agent approval is required next.'
                : 'First sheet rejected by QC. Adjustments are required and emails sent.');
        }

        abort(400, 'Invalid review stage');
    }

    public function salesAgentReview(Request $request, $id)
    {
        $user = Auth::guard('crm')->user();
        
        $request->validate([
            'action' => 'required|in:approve,request_change',
            'notes' => 'nullable|string|max:2000',
        ]);

        $job = ProductionJob::findOrFail($id);
        $this->requireStatus($job, ['sales_agent_review']);

        $isAgent = $job->salesOrder && (int)$job->salesOrder->sales_agent_id === (int)$user->id;
        if (!$user->isAdmin() && !$isAgent) {
            abort(403, 'Only Admin or the assigned Sales Agent can review the first sheet.');
        }

        $approved = $request->action === 'approve';
        if (!$approved && !$request->filled('notes')) {
            return back()->with('error', 'Rejection reason is required when requesting changes.');
        }
        $newStatus = $approved ? 'production_ready' : 'adjustments_required';

        $fromStatus = $job->status;
        DB::transaction(function () use ($job, $request, $user, $approved, $newStatus, $fromStatus) {
            $job->update([
                'status' => $newStatus,
                'adjustment_notes' => $approved ? null : $request->notes,
            ]);
            $this->logTransition($job, $fromStatus, $newStatus, $approved ? 'Sales Agent approved first sheet.' : 'Sales Agent requested changes: ' . $request->notes, $user->id);
        });

        return back()->with($approved ? 'success' : 'error', $approved
            ? 'First sheet approved by Sales Agent. Job is now ready for full production.'
            : 'Changes requested. Job sent back to press operator.');
    }

    public function startRun($id)
    {
        $user = $this->authorizedUser();
        $job = ProductionJob::findOrFail($id);
        $this->requireOperator($job, $user);
        $this->requireStatus($job, ['production_ready']);

        $fromStatus = $job->status;
        $job->update(['status' => 'full_production']);
        $this->logTransition($job, $fromStatus, 'full_production', 'Full production run started.', $user->id);

        return back()->with('success', 'Full production run started.');
    }

    public function completeRun(Request $request, $id)
    {
        $user = $this->authorizedUser();
        $job = ProductionJob::findOrFail($id);
        $this->requireOperator($job, $user);
        $this->requireStatus($job, ['full_production']);

        $request->validate([
            'good_quantity' => 'required|integer|min:0',
            'waste_quantity' => 'required|integer|min:0',
            'every_x_sheets_check' => 'nullable|string|max:500',
            'density_reading' => 'nullable|string|max:500',
            'color_variation_check' => 'nullable|string|max:500',
            'registration_check' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
        ]);


        $fromStatus = $job->status;
        $readings = collect([
            'Every X sheets check' => $request->every_x_sheets_check,
            'Density reading' => $request->density_reading,
            'Color variation check' => $request->color_variation_check,
            'Registration check' => $request->registration_check,
            'Run notes' => $request->notes,
        ])->filter()->map(function ($value, $label) {
            return $label . ': ' . $value;
        })->implode("\n");

        DB::transaction(function () use ($job, $request, $user, $fromStatus, $readings) {
            $job->update([
                'status' => 'in_process_checks',
                'good_quantity' => $request->good_quantity,
                'waste_quantity' => $request->waste_quantity,
                'completed_at' => null,
            ]);
            $this->logTransition($job, $fromStatus, 'in_process_checks', $readings ?: 'Full production run completed. Sent to supervisor for in-process checks.', $user->id);
        });

        return back()->with('success', 'Run quantities recorded. Job moved to supervisor in-process checks.');
    }

    public function updateSupervisorStage(Request $request, $id)
    {
        $user = $this->authorizedUser();
        $job = ProductionJob::findOrFail($id);
        $this->requireSupervisor($job, $user);
        $this->requireStatus($job, $this->supervisorStatuses());

        $request->validate([
            'stages' => 'required|array',
            'stages.*' => 'in:in_process_checks,coating_options,lamination_options,die_cutting,stripping,blank_separation,gluing,final_quality_control,packing,palletization,warehouse_ready,production_completed',
            'notes' => 'nullable|string|max:2000',
        ]);

        $fromStatus = $job->status;
        
        $stageOrder = [
            'in_process_checks' => 1,
            'coating_options' => 2,
            'lamination_options' => 3,
            'die_cutting' => 4,
            'stripping' => 5,
            'blank_separation' => 6,
            'gluing' => 7,
            'final_quality_control' => 8,
            'packing' => 9,
            'palletization' => 10,
            'warehouse_ready' => 11,
            'production_completed' => 12,
        ];

        $selectedStages = $request->stages;
        $furthestStage = $selectedStages[0];
        foreach ($selectedStages as $stage) {
            if ($stageOrder[$stage] > $stageOrder[$furthestStage]) {
                $furthestStage = $stage;
            }
        }
        $toStatus = $furthestStage;

        DB::transaction(function () use ($job, $request, $user, $fromStatus, $toStatus, $selectedStages) {
            $data = [
                'status' => $toStatus,
                'completed_finishing_stages' => $selectedStages
            ];
            if ($toStatus === 'production_completed') {
                $data['completed_at'] = now();
            }

            $job->update($data);

            if ($toStatus === 'production_completed' && $job->salesOrder) {
                $job->salesOrder->update([
                    'status' => 'warehouse_ready',
                    'shipping_stage' => 'warehouse_ready',
                ]);
            }

            $this->logTransition(
                $job,
                $fromStatus,
                $toStatus,
                $request->notes ?: 'Post-production stages marked as completed: ' . implode(', ', array_map(function($s) { return ucwords(str_replace('_', ' ', $s)); }, $selectedStages)) . '.',
                $user->id
            );
        });

        return back()->with('success', 'Supervisor stage updated successfully.');
    }

    public function finalQualityControl(Request $request, $id)
    {
        $user = $this->authorizedUser();
        if (!$user->isAdmin() && !$user->isQC() && !$user->isProductionManager()) {
            abort(403, 'Only QC, Production Manager, or Admin can complete final quality control.');
        }

        $job = ProductionJob::findOrFail($id);
        $this->requireStatus($job, ['final_quality_control']);

        $request->validate([
            'action' => 'required|in:approve,reject',
            'dimension_check_passed' => 'nullable|boolean',
            'fold_color_check_passed' => 'nullable|boolean',
            'quantity_check_passed' => 'nullable|boolean',
            'glue_strength_check_passed' => 'nullable|boolean',
            'barcode_scan_passed' => 'nullable|boolean',
            'packaging_inspection_passed' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
        ]);

        $fromStatus = $job->status;

        if ($request->action === 'reject') {
            if (!$request->filled('notes')) {
                return back()->with('error', 'Rejection reason is required when cancelling the order.');
            }

            DB::transaction(function () use ($job, $request, $user, $fromStatus) {
                $job->update(['status' => 'cancelled', 'completed_at' => now()]);
                
                if ($job->salesOrder) {
                    $job->salesOrder->update([
                        'status' => 'cancelled',
                    ]);
                }

                $this->logTransition($job, $fromStatus, 'cancelled', 'Final QC Rejected / Order Cancelled: ' . $request->notes, $user->id);
            });

            return back()->with('success', 'Order has been rejected and cancelled at Final QC.');
        }

        $results = [
            'Dimension check' => $request->has('dimension_check_passed'),
            'Fold color check' => $request->has('fold_color_check_passed'),
            'Quantity check' => $request->has('quantity_check_passed'),
            'Glue strength check' => $request->has('glue_strength_check_passed'),
            'Barcode scan' => $request->has('barcode_scan_passed'),
            'Packaging inspection' => $request->has('packaging_inspection_passed'),
        ];
        $passed = !in_array(false, $results, true);

        if (!$passed && !$request->filled('notes')) {
            return back()->with('error', 'QC rejection reason is required when any final QC check fails.');
        }

        $toStatus = $passed ? 'warehouse_ready' : 'gluing';
        $failedChecks = collect($results)->filter(function ($passed) {
            return !$passed;
        })->keys()->implode(', ');

        DB::transaction(function () use ($job, $request, $user, $fromStatus, $toStatus, $passed, $failedChecks) {
            $notes = $passed
                ? ($request->notes ?: 'Final QC passed. Production completed and sent to warehouse.')
                : 'Final QC failed: ' . $failedChecks . '. ' . $request->notes;

            $job->update(['status' => $toStatus, 'completed_at' => $passed ? now() : null]);
            
            if ($passed && $job->salesOrder) {
                $job->salesOrder->update([
                    'status' => 'warehouse_ready',
                    'shipping_stage' => 'warehouse_ready',
                ]);
            }

            $this->logTransition($job, $fromStatus, $toStatus, $notes, $user->id);
        });

        return back()->with($passed ? 'success' : 'error', $passed
            ? 'Final QC passed. Job successfully sent to warehouse.'
            : 'Final QC failed. Job moved back to gluing/correction.');
    }

    private function authorizedUser()
    {
        $user = Auth::guard('crm')->user();
        if (!$user || (!$user->isAdmin() && !$user->isProductionManager() && !$user->isPressOperator() && !$user->isQC())) {
            abort(403, 'Unauthorized production access.');
        }
        return $user;
    }

    private function requireManager($user)
    {
        if (!$user->isAdmin() && !$user->isProductionManager()) {
            abort(403, 'Only Production Manager or Admin can perform this action.');
        }
    }

    private function requireOperator(ProductionJob $job, $user)
    {
        if ($user->isAdmin()) {
            return;
        }

        if (!$user->isPressOperator() || (int) $job->press_operator_id !== (int) $user->id) {
            abort(403, 'This production job is not assigned to you.');
        }
    }

    private function requireSupervisor(ProductionJob $job, $user)
    {
        if ($user->isAdmin() || $user->isProductionManager()) {
            return;
        }
        abort(403, 'This action requires Production Manager or Admin privileges.');
    }

    private function requireStatus(ProductionJob $job, array $allowed)
    {
        if (!in_array($job->status, $allowed, true)) {
            abort(422, 'This action is not allowed while the job is ' . str_replace('_', ' ', $job->status) . '.');
        }
    }

    private function logTransition(ProductionJob $job, $fromStatus, $toStatus, $notes, $userId)
    {
        ProductionJobLog::create([
            'production_job_id' => $job->id,
            'crm_user_id' => $userId,
            'from_status' => $fromStatus === $toStatus ? null : $fromStatus,
            'to_status' => $toStatus,
            'notes' => $notes,
        ]);
    }
}
