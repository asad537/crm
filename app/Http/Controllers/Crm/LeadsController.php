<?php

namespace App\Http\Controllers\Crm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CrmEmail;

class LeadsController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('export')) {
            return $this->exportLeads($request);
        }

        $query = CrmEmail::with(['estimator', 'rejectionLog'])
                        ->select('id', 'client_name', 'client_email', 'client_phone', 'created_at', 'status', 'product_name', 'quantity', 'estimator_id', 'estimate_status')
                        ->where('is_spam', false)
                        ->where('is_rejected', false)
                        ->orderBy('created_at', 'desc');

        if (\Auth::guard('crm')->user()->isEstimator()) {
             // Estimators see all their assigned estimations regardless of the main CRM status 
             // (e.g., even if it changed to 'Responded' and a revision was requested)
             $query->where('estimator_id', \Auth::guard('crm')->id());
        } else {
             // Keep this page dedicated to qualified leads. Estimate work has its
             // own Get Estimate queue and should not be mixed into this listing.
             $query->where('status', 'Qualified Lead');

             if (!\Auth::guard('crm')->user()->isAdmin() && !\Auth::guard('crm')->user()->isSalesManager()) {
                 $query->where('assigned_to', \Auth::guard('crm')->id());
             }
        }



        // Date Range Filter
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->has('date_filter') && $request->date_filter != '') {
            if ($request->date_filter == 'today') {
                $query->whereDate('created_at', \Carbon\Carbon::today());
            } elseif ($request->date_filter == 'yesterday') {
                $query->whereDate('created_at', \Carbon\Carbon::yesterday());
            } elseif ($request->date_filter == 'this_week') {
                $query->whereBetween('created_at', [\Carbon\Carbon::now()->startOfWeek(), \Carbon\Carbon::now()->endOfWeek()]);
            }
        }

        // Product Filter
        if ($request->filled('product')) {
            $query->where('product_name', $request->product);
        }

        // Global Search Filter (All Fields)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%")
                  ->orWhere('client_email', 'like', "%{$search}%")
                  ->orWhere('client_phone', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%");
            });
        }

        // Fetch Unique Products for Dropdown (Exclude Spam)
        $products = CrmEmail::select('product_name')
                            ->where('is_spam', false)
                            ->where('status', 'Qualified Lead')
                            ->distinct()
                            ->whereNotNull('product_name')
                            ->pluck('product_name');

        $leads = $query->paginate(9);

        if ($request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return view('crm.leads.table', compact('leads'))->render();
        }

        return view('crm.leads.index', compact('leads', 'products'));
    }

    protected function exportLeads(Request $request)
    {
        $startDate = $request->start_date ? \Carbon\Carbon::parse($request->start_date) : \Carbon\Carbon::today();
        $endDate = $request->end_date ? \Carbon\Carbon::parse($request->end_date) : \Carbon\Carbon::today();
        $endDate->setTime(23, 59, 59);

        // Fetch only Qualified Leads
        $emails = CrmEmail::with(['estimator', 'rejectionLog'])
                          ->select('id', 'client_email', 'status', 'is_spam', 'created_at', 'product_name', 'estimator_id', 'estimate_status')
                          ->where('status', 'Qualified Lead')
                          ->whereBetween('created_at', [$startDate, $endDate])
                          ->orderBy('created_at', 'asc')
                          ->get();

        $dailyData = [];
        $totals = [
            'Qualified Leads' => 0
        ];

        foreach ($emails as $email) {
            $date = $email->created_at->format('Y-m-d');
            if (!isset($dailyData[$date])) {
                $dailyData[$date] = [
                    'Qualified Leads' => 0
                ];
            }

            $dailyData[$date]['Qualified Leads']++;
            $totals['Qualified Leads']++;
        }

        $filename = "quotes_export_" . now()->format('Ymd_His') . ".xls";
        
        $startDateStr = $startDate->format('Y-m-d');
        $endDateStr = $endDate->format('Y-m-d');

        return response(view('crm.leads.export', [
            'dailyData' => $dailyData, 
            'totals' => $totals, 
            'startDate' => $startDateStr, 
            'endDate' => $endDateStr
        ]))
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
            ->header('Pragma', 'no-cache')
            ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            ->header('Expires', '0');
    }
}
