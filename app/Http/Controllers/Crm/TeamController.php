<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CrmUser;
use App\CrmEmail;
use App\CrmStatusLog;
use App\DesignRequirementTicket;
use App\EstimateTicket;
use App\ProductionJob;
use App\QualityControl;
use App\SalesOrder;
use Carbon\Carbon;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->get('range', 'today'); // Default Today
        $search = $request->get('search');
        $start_date = $request->get('start_date');
        $end_date = $request->get('end_date');

        if ($start_date && $end_date) {
            $startDate = Carbon::parse($start_date)->startOfDay();
            $endDate = Carbon::parse($end_date)->endOfDay();
            $range = 'custom';
        } else {
            $startDate = Carbon::today();
            $endDate = Carbon::today()->endOfDay();
            if ($range === 'this_week') {
                $startDate = Carbon::now()->startOfWeek();
                $endDate = Carbon::now()->endOfWeek();
            } elseif ($range === 'this_month') {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
            } elseif ($range === 'this_year') {
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
            }
        }

        $query = CrmUser::inWorkspace();
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($request->filled('user_id')) {
            $query->where('id', (int) $request->user_id);
        }
        if ($request->filled('ids') && is_array($request->ids)) {
            $query->whereIn('id', array_map('intval', $request->ids));
        }
        $query->where('role', '!=', 'admin'); // Exclude Admins
        $users = $query->get();

        // Batch-fetch all viewed/responded/orders counts for these users in ONE query
        // (was N queries per user × 3 statuses — classic N+1).
        $userNames = $users->pluck('name')->all();
        $statusCounts = CrmStatusLog::whereIn('user_name', $userNames)
            ->whereIn('new_status', ['Viewed', 'Responded', 'Order Done'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('user_name, new_status, COUNT(*) as c')
            ->groupBy('user_name', 'new_status')
            ->get()
            ->groupBy('user_name');

        $performanceData = [];
        foreach ($users as $user) {
            $userCounts = $statusCounts->get($user->name, collect());
            $viewed    = (int) ($userCounts->firstWhere('new_status', 'Viewed')->c    ?? 0);
            $responded = (int) ($userCounts->firstWhere('new_status', 'Responded')->c ?? 0);
            $orders    = (int) ($userCounts->firstWhere('new_status', 'Order Done')->c ?? 0);

            $role = $user->activeWorkspaceRole();
            $metrics = $this->roleMetrics($user, $role, $startDate, $endDate, $viewed, $responded, $orders);
            $performanceData[] = [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $role,
                'role_label' => $user->getRoleLabel(),
                'metrics' => $metrics,
                'score' => collect($metrics)->sum('value'),
            ];
        }

        $teamSummary = [
            'members' => count($performanceData),
            'activity' => collect($performanceData)->sum('score'),
            'active_members' => collect($performanceData)->where('score', '>', 0)->count(),
        ];

        return view('crm.team.index', compact('performanceData', 'teamSummary', 'range', 'search', 'start_date', 'end_date'));
    }

    public function export(Request $request)
    {
        $request->validate(['format' => 'required|in:excel,pdf']);
        $reportView = $this->index($request);
        $data = $reportView->getData();
        $data['generatedAt'] = now()->format('d M Y, h:i A');
        $data['dateRangeLabel'] = $this->dateRangeLabel($request, $data['range']);
        $data['brand'] = $this->reportBranding();
        $fileName = 'team-performance-' . now()->format('Y-m-d-His');

        if ($request->format === 'pdf') {
            return app('dompdf.wrapper')
                ->loadView('crm.team.export_pdf', $data)
                ->setPaper('a4', 'landscape')
                ->download($fileName . '.pdf');
        }

        return response()->view('crm.team.export_excel', $data)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '.xls"');
    }

    private function dateRangeLabel(Request $request, $range)
    {
        if ($request->filled('start_date') && $request->filled('end_date')) {
            return Carbon::parse($request->start_date)->format('d M Y') . ' – ' . Carbon::parse($request->end_date)->format('d M Y');
        }
        return ucwords(str_replace('_', ' ', $range));
    }

    private function reportBranding()
    {
        $workspace = view()->shared('activeCrmWorkspace');
        $isAlMassa = $workspace && $workspace->slug === 'mybox-packaging-app';
        $logoFile = $isAlMassa ? 'al-massa-invoice-email-logo.png' : 'my-box-printing-logo-pdf.jpg';
        $logoPath = collect([
            base_path($logoFile),
            rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') . '/' . $logoFile,
            public_path($logoFile),
            base_path('public/' . $logoFile),
        ])->first(function ($path) {
            return $path && is_file($path);
        });

        return [
            'name' => $isAlMassa ? 'Al Massa Packaging' : 'My Box Printing',
            'company' => $isAlMassa ? 'AL MASSA AL MALAKIYA BOXES AND PACKING IND. LLC' : 'MY BOX PRINTING',
            'color' => $isAlMassa ? '#0d2f68' : '#80c500',
            'accent' => $isAlMassa ? '#e4a000' : '#80c500',
            'logo' => $logoPath ? 'data:image/' . pathinfo($logoPath, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($logoPath)) : '',
        ];
    }

    private function roleMetrics($user, $role, $startDate, $endDate, $viewed, $responded, $orders)
    {
        if ($role === 'designer') {
            return [
                $this->metric('Tickets Claimed', DesignRequirementTicket::where('claimed_by', $user->id)->whereBetween('opened_at', [$startDate, $endDate])->count(), 'folder-open', 'blue'),
                $this->metric('Designs Completed', DesignRequirementTicket::where('claimed_by', $user->id)->whereBetween('completed_at', [$startDate, $endDate])->count(), 'check-circle', 'green'),
                $this->metric('Sent to Estimator', DesignRequirementTicket::where('claimed_by', $user->id)->whereBetween('forwarded_at', [$startDate, $endDate])->count(), 'paper-plane', 'purple'),
            ];
        }

        if ($role === 'estimator') {
            return [
                $this->metric('Tickets Opened', EstimateTicket::where('estimator_id', $user->id)->whereBetween('created_at', [$startDate, $endDate])->count(), 'calculator', 'blue'),
                $this->metric('Estimates Submitted', EstimateTicket::where('estimator_id', $user->id)->whereBetween('submitted_at', [$startDate, $endDate])->count(), 'paper-plane', 'purple'),
                $this->metric('Estimates Completed', EstimateTicket::where('estimator_id', $user->id)->whereBetween('completed_at', [$startDate, $endDate])->count(), 'check-circle', 'green'),
            ];
        }

        if ($role === 'team_lead') {
            return [
                $this->metric('Reviews Assigned', EstimateTicket::where('team_lead_id', $user->id)->whereBetween('created_at', [$startDate, $endDate])->count(), 'clipboard-list', 'blue'),
                $this->metric('Reviews Completed', EstimateTicket::where('team_lead_id', $user->id)->whereBetween('team_lead_reviewed_at', [$startDate, $endDate])->count(), 'check-double', 'green'),
                $this->metric('Workflow Activity', $viewed + $responded + $orders, 'chart-line', 'purple'),
            ];
        }

        if (in_array($role, ['production_manager', 'press_operator'], true)) {
            $column = $role === 'production_manager' ? 'production_manager_id' : 'press_operator_id';
            return [
                $this->metric('Jobs Assigned', ProductionJob::where($column, $user->id)->whereBetween('created_at', [$startDate, $endDate])->count(), 'industry', 'blue'),
                $this->metric('Jobs Started', ProductionJob::where($column, $user->id)->whereBetween('actual_start_at', [$startDate, $endDate])->count(), 'play-circle', 'purple'),
                $this->metric('Jobs Completed', ProductionJob::where($column, $user->id)->whereBetween('completed_at', [$startDate, $endDate])->count(), 'check-circle', 'green'),
            ];
        }

        if ($role === 'qc') {
            return [
                $this->metric('QC Checks', QualityControl::where('qc_agent_id', $user->id)->whereBetween('created_at', [$startDate, $endDate])->count(), 'search', 'blue'),
                $this->metric('Jobs Inspected', ProductionJob::where('qc_inspector_id', $user->id)->whereBetween('created_at', [$startDate, $endDate])->count(), 'clipboard-check', 'purple'),
                $this->metric('Workflow Activity', $viewed + $responded + $orders, 'chart-line', 'green'),
            ];
        }

        if ($role === 'sales') {
            $inquiryBase = CrmEmail::whereNotNull('inquiry_quantities');
            $inquiriesAdded = (clone $inquiryBase)
                ->where('created_by', $user->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count();
            $inquiriesAssigned = (clone $inquiryBase)
                ->where('assigned_to', $user->id)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('assigned_at', [$startDate, $endDate])
                          ->orWhere(function ($legacy) use ($startDate, $endDate) {
                              $legacy->whereNull('assigned_at')->whereBetween('created_at', [$startDate, $endDate]);
                          });
                })
                ->count();

            return [
                $this->metric('Inquiries Added', $inquiriesAdded, 'plus-circle', 'blue'),
                $this->metric('Inquiries Assigned', $inquiriesAssigned, 'clipboard-list', 'purple'),
                $this->metric('Orders Completed', SalesOrder::where('sales_agent_id', $user->id)->whereBetween('order_completed_at', [$startDate, $endDate])->count() + $orders, 'shopping-bag', 'green'),
            ];
        }

        if ($role === 'sales_manager') {
            return [
                $this->metric('Emails Read', $viewed, 'envelope-open', 'blue'),
                $this->metric('Replies Sent', $responded, 'reply', 'purple'),
                $this->metric('Orders Completed', SalesOrder::where('sales_agent_id', $user->id)->whereBetween('order_completed_at', [$startDate, $endDate])->count() + $orders, 'shopping-bag', 'green'),
            ];
        }

        return [
            $this->metric('Items Viewed', $viewed, 'eye', 'blue'),
            $this->metric('Actions Completed', $responded, 'tasks', 'purple'),
            $this->metric('Orders Completed', $orders, 'check-circle', 'green'),
        ];
    }

    private function metric($label, $value, $icon, $tone)
    {
        return ['label' => $label, 'value' => (int) $value, 'icon' => $icon, 'tone' => $tone];
    }
}
