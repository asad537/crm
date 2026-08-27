<?php

namespace App\Http\Controllers\Crm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CrmEmail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = \Auth::guard('crm')->user();
        $isRestricted = $currentUser && !$currentUser->isAdmin() && !$currentUser->isSalesManager();
        $range = $request->get('range', 'today');
        $now = Carbon::now();
        $workspaceId = \App\Support\CrmWorkspaceContext::id();
        $dashboardCount = function ($key, $callback) use ($workspaceId, $currentUser, $range) {
            return Cache::remember(
                'crm:dashboard:'.($workspaceId ?: 0).':'.($currentUser ? $currentUser->id : 0).':'.$range.':'.$key,
                30,
                $callback
            );
        };

        // Initialize defaults
        $statsStartDate = $now->copy()->startOfDay();
        $chartStartDate = $now->copy()->subDays(6)->startOfDay();
        $labels = [];

        // 1. Determine Date Range & Labels
        if ($range === 'weekly') {
            // Stats: This Week
            $statsStartDate = $now->copy()->startOfWeek();
            // Chart: Last 4 Weeks
            $chartStartDate = $now->copy()->subWeeks(3)->startOfWeek(); 
            for ($i = 0; $i < 4; $i++) {
                $labels[] = 'Week ' . ($i + 1);
            }
        } elseif ($range === 'monthly') {
            // Stats: This Month
            $statsStartDate = $now->copy()->startOfMonth();
            // Chart: This Year (Jan-Dec)
            $chartStartDate = $now->copy()->startOfYear();
            $labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        } elseif ($range === 'yearly') {
            // Stats: This Year
            $statsStartDate = $now->copy()->startOfYear();
            // Chart: Last 5 Years
            $chartStartDate = $now->copy()->subYears(4)->startOfYear();
            for ($i = 0; $i < 5; $i++) {
                $labels[] = $chartStartDate->copy()->addYears($i)->format('Y');
            }
        } else { // Today (Default)
            // Stats: Today Only
            $statsStartDate = $now->copy()->startOfDay();
            // Chart: Last 10 Days Trend
            $chartStartDate = $now->copy()->subDays(9)->startOfDay();
            for ($i = 0; $i < 10; $i++) {
                $labels[] = $chartStartDate->copy()->addDays($i)->format('d M');
            }
        }

        // 2. Fetch Stats & Calculate Trends
        // Define Current vs Previous Ranges
        $prevStartDate = null;
        $prevEndDate = null;
        $currentEndDate = $now->copy();

        if ($range === 'today') {
            $prevStartDate = $now->copy()->subDay()->startOfDay();
            $prevEndDate = $now->copy()->subDay()->endOfDay();
        } elseif ($range === 'weekly') {
            $prevStartDate = $now->copy()->subWeek()->startOfWeek();
            $prevEndDate = $now->copy()->subWeek()->endOfWeek();
        } elseif ($range === 'monthly') {
            $prevStartDate = $now->copy()->subMonth()->startOfMonth();
            $prevEndDate = $now->copy()->subMonth()->endOfMonth();
        } elseif ($range === 'yearly') {
            $prevStartDate = $now->copy()->subYear()->startOfYear();
            $prevEndDate = $now->copy()->subYear()->endOfYear();
        }

        // Helper to get count
        $getCount = function($isSpam, $status = null) use ($statsStartDate, $currentEndDate, $currentUser, $isRestricted, $dashboardCount) {
            return $dashboardCount('current:'.(int)$isSpam.':'.($status ?: 'all'), function () use ($isSpam, $status, $statsStartDate, $currentEndDate, $currentUser, $isRestricted) {
                $q = CrmEmail::where('is_spam', $isSpam)->whereBetween('created_at', [$statsStartDate, $currentEndDate]);
                if ($status) $q->where('status', $status);
                if ($isRestricted) {
                    $q->where('assigned_to', $currentUser->id);
                }
                return $q->count();
            });
        };
        
        $getPrevCount = function($isSpam, $status = null) use ($prevStartDate, $prevEndDate, $currentUser, $isRestricted, $dashboardCount) {
            return $dashboardCount('previous:'.(int)$isSpam.':'.($status ?: 'all'), function () use ($isSpam, $status, $prevStartDate, $prevEndDate, $currentUser, $isRestricted) {
                $q = CrmEmail::where('is_spam', $isSpam)->whereBetween('created_at', [$prevStartDate, $prevEndDate]);
                if ($status) $q->where('status', $status);
                if ($isRestricted) {
                    $q->where('assigned_to', $currentUser->id);
                }
                return $q->count();
            });
        };

        // Current Counts
        $totalEmails = $getCount(false);
        
        $spamEmailsQuery = CrmEmail::where('is_spam', true)->whereBetween('created_at', [$statsStartDate, $currentEndDate]);
        if ($isRestricted) {
            $spamEmailsQuery->where('assigned_to', $currentUser->id);
        }
        $spamEmails = $spamEmailsQuery->count();
        
        $repliedEmails = $getCount(false, 'Responded');
        $ordersDone = $getCount(false, 'Order Done');

        // Previous Counts
        $prevTotal = $getPrevCount(false);
        
        $prevSpamQuery = CrmEmail::where('is_spam', true)->whereBetween('created_at', [$prevStartDate, $prevEndDate]);
        if ($isRestricted) {
            $prevSpamQuery->where('assigned_to', $currentUser->id);
        }
        $prevSpam = $prevSpamQuery->count();
        
        $prevReplied = $getPrevCount(false, 'Responded');
        $prevOrders = $getPrevCount(false, 'Order Done');

        // Calculate Percentage Change
        $calcTrend = function($current, $prev) {
            if ($prev == 0) return $current > 0 ? 100 : 0;
            return round((($current - $prev) / $prev) * 100, 1);
        };

        $trends = [
            'total' => $calcTrend($totalEmails, $prevTotal),
            'spam' => $calcTrend($spamEmails, $prevSpam),
            'replied' => $calcTrend($repliedEmails, $prevReplied),
            'orders' => $calcTrend($ordersDone, $prevOrders)
        ];

        // 3. Compute Chart Trends (using Chart Range)
        $chartReplied = array_fill(0, count($labels), 0);
        $chartPending = array_fill(0, count($labels), 0);
        $chartTotal = array_fill(0, count($labels), 0);
        $chartSpam = array_fill(0, count($labels), 0);
        $chartOrders = array_fill(0, count($labels), 0);

        if ($currentUser && $currentUser->isDesigner()) {
            $pendingOrders = \App\SalesOrder::where('created_at', '>=', $chartStartDate)
                                            ->where('status', 'in_design')
                                            ->get(['created_at']);

            $completedRevisions = \App\ProofRevision::where('created_at', '>=', $chartStartDate)
                                                    ->where('uploaded_by', $currentUser->id)
                                                    ->get(['created_at', 'crm_email_id']);

            foreach ($pendingOrders as $order) {
                $idx = -1;
                $ts = Carbon::parse($order->created_at);

                if ($range === 'today' || $range === 'daily') {
                    $idx = $ts->diffInDays($chartStartDate->copy()->startOfDay()); 
                } elseif ($range === 'weekly') {
                    $idx = floor($ts->diffInDays($chartStartDate) / 7);
                } elseif ($range === 'monthly') {
                    $idx = $ts->month - 1;
                } elseif ($range === 'yearly') {
                    $idx = $ts->year - $chartStartDate->year;
                }

                if ($idx < 0) $idx = 0; 
                if ($idx >= count($labels)) $idx = count($labels) - 1;

                if ($idx >= 0 && $idx < count($labels)) {
                    $chartPending[$idx]++;
                    $chartTotal[$idx]++;
                }
            }

            $seenCompletions = [];
            foreach ($completedRevisions as $rev) {
                $idx = -1;
                $ts = Carbon::parse($rev->created_at);

                if ($range === 'today' || $range === 'daily') {
                    $idx = $ts->diffInDays($chartStartDate->copy()->startOfDay()); 
                } elseif ($range === 'weekly') {
                    $idx = floor($ts->diffInDays($chartStartDate) / 7);
                } elseif ($range === 'monthly') {
                    $idx = $ts->month - 1;
                } elseif ($range === 'yearly') {
                    $idx = $ts->year - $chartStartDate->year;
                }

                if ($idx < 0) $idx = 0; 
                if ($idx >= count($labels)) $idx = count($labels) - 1;

                if ($idx >= 0 && $idx < count($labels)) {
                    $key = $idx . '_' . $rev->crm_email_id;
                    if (!in_array($key, $seenCompletions)) {
                        $seenCompletions[] = $key;
                        $chartReplied[$idx]++; // Use replied array for completed
                        $chartTotal[$idx]++;
                    }
                }
            }
        } elseif ($currentUser && $currentUser->isPrepress()) {
            $chartQuery = \App\SalesOrder::where('created_at', '>=', $chartStartDate);
            $rawChartData = $chartQuery->get(['created_at', 'status', 'prepress_checks']);

            foreach ($rawChartData as $order) {
                $idx = -1;
                $ts = Carbon::parse($order->created_at);

                if ($range === 'today' || $range === 'daily') {
                    $idx = $ts->diffInDays($chartStartDate->copy()->startOfDay()); 
                } elseif ($range === 'weekly') {
                    $idx = floor($ts->diffInDays($chartStartDate) / 7);
                } elseif ($range === 'monthly') {
                    $idx = $ts->month - 1;
                } elseif ($range === 'yearly') {
                    $idx = $ts->year - $chartStartDate->year;
                }

                if ($idx < 0) $idx = 0; 
                if ($idx >= count($labels)) $idx = count($labels) - 1;

                if ($idx >= 0 && $idx < count($labels)) {
                    if ($order->status === 'prepress' || !is_null($order->prepress_checks)) {
                        $chartTotal[$idx]++; 
                        
                        if ($order->status === 'prepress') {
                            $chartPending[$idx]++;
                        } else {
                            $chartReplied[$idx]++; // Use replied array for completed
                        }
                    }
                }
            }
        } elseif ($currentUser && $currentUser->isProductionManager()) {
            $chartQuery = \App\ProductionJob::where('created_at', '>=', $chartStartDate);
            $rawChartData = $chartQuery->get(['created_at', 'status']);

            foreach ($rawChartData as $job) {
                $idx = -1;
                $ts = Carbon::parse($job->created_at);

                if ($range === 'today' || $range === 'daily') {
                    $idx = $ts->diffInDays($chartStartDate->copy()->startOfDay()); 
                } elseif ($range === 'weekly') {
                    $idx = floor($ts->diffInDays($chartStartDate) / 7);
                } elseif ($range === 'monthly') {
                    $idx = $ts->month - 1;
                } elseif ($range === 'yearly') {
                    $idx = $ts->year - $chartStartDate->year;
                }

                if ($idx < 0) $idx = 0; 
                if ($idx >= count($labels)) $idx = count($labels) - 1;

                if ($idx >= 0 && $idx < count($labels)) {
                    $chartTotal[$idx]++; 
                    
                    if (in_array($job->status, ['production_completed', 'warehouse_ready'])) {
                        $chartReplied[$idx]++; // Use replied array for completed
                    } else {
                        $chartPending[$idx]++;
                    }
                }
            }
        } elseif ($currentUser && $currentUser->isQC()) {
            $chartQuery = \App\ProductionJob::where('created_at', '>=', $chartStartDate)
                                            ->where('qc_inspector_id', $currentUser->id);
            $rawChartData = $chartQuery->get(['created_at', 'status']);

            foreach ($rawChartData as $job) {
                $idx = -1;
                $ts = Carbon::parse($job->created_at);

                if ($range === 'today' || $range === 'daily') {
                    $idx = $ts->diffInDays($chartStartDate->copy()->startOfDay()); 
                } elseif ($range === 'weekly') {
                    $idx = floor($ts->diffInDays($chartStartDate) / 7);
                } elseif ($range === 'monthly') {
                    $idx = $ts->month - 1;
                } elseif ($range === 'yearly') {
                    $idx = $ts->year - $chartStartDate->year;
                }

                if ($idx < 0) $idx = 0; 
                if ($idx >= count($labels)) $idx = count($labels) - 1;

                if ($idx >= 0 && $idx < count($labels)) {
                    $chartTotal[$idx]++;

                    if (in_array($job->status, ['first_sheet_review', 'final_quality_control'])) {
                        $chartPending[$idx]++;
                    } else {
                        $chartReplied[$idx]++;
                    }
                }
            }
        } elseif (false) {
            $chartQuery = \App\ProductionJob::where('created_at', '>=', $chartStartDate)
                                            ->where('production_supervisor_id', $currentUser->id);
            $rawChartData = $chartQuery->get(['created_at', 'status']);

            foreach ($rawChartData as $job) {
                $idx = -1;
                $ts = Carbon::parse($job->created_at);

                if ($range === 'today' || $range === 'daily') {
                    $idx = $ts->diffInDays($chartStartDate->copy()->startOfDay()); 
                } elseif ($range === 'weekly') {
                    $idx = floor($ts->diffInDays($chartStartDate) / 7);
                } elseif ($range === 'monthly') {
                    $idx = $ts->month - 1;
                } elseif ($range === 'yearly') {
                    $idx = $ts->year - $chartStartDate->year;
                }

                if ($idx < 0) $idx = 0; 
                if ($idx >= count($labels)) $idx = count($labels) - 1;

                if ($idx >= 0 && $idx < count($labels)) {
                    $chartTotal[$idx]++;

                    if (in_array($job->status, ['warehouse_ready', 'production_completed'])) {
                        $chartReplied[$idx]++;
                    } else {
                        $chartPending[$idx]++;
                    }
                }
            }
        } elseif ($currentUser && $currentUser->isPressOperator()) {
            $chartQuery = \App\ProductionJob::where('created_at', '>=', $chartStartDate)
                                            ->where('press_operator_id', $currentUser->id);
            $rawChartData = $chartQuery->get(['created_at', 'status']);

            foreach ($rawChartData as $job) {
                $idx = -1;
                $ts = Carbon::parse($job->created_at);

                if ($range === 'today' || $range === 'daily') {
                    $idx = $ts->diffInDays($chartStartDate->copy()->startOfDay()); 
                } elseif ($range === 'weekly') {
                    $idx = floor($ts->diffInDays($chartStartDate) / 7);
                } elseif ($range === 'monthly') {
                    $idx = $ts->month - 1;
                } elseif ($range === 'yearly') {
                    $idx = $ts->year - $chartStartDate->year;
                }

                if ($idx < 0) $idx = 0; 
                if ($idx >= count($labels)) $idx = count($labels) - 1;

                if ($idx >= 0 && $idx < count($labels)) {
                    $chartTotal[$idx]++; 
                    
                    if (!in_array($job->status, ['pending_planning', 'scheduled', 'press_setup', 'adjustments_required', 'production_ready', 'full_production'])) {
                        $chartReplied[$idx]++; // Runs completed by press operator
                    } else {
                        $chartPending[$idx]++;
                    }
                }
            }
        } else {
            $chartQuery = CrmEmail::where('created_at', '>=', $chartStartDate);
            if ($isRestricted) {
                $chartQuery->where('assigned_to', $currentUser->id);
            }
            $rawChartData = $chartQuery->get(['created_at', 'status', 'is_spam']);
            
            foreach ($rawChartData as $email) {
                $idx = -1;
                $ts = Carbon::parse($email->created_at);

                if ($range === 'today' || $range === 'daily') {
                    $idx = $ts->diffInDays($chartStartDate->copy()->startOfDay()); 
                } elseif ($range === 'weekly') {
                    $idx = floor($ts->diffInDays($chartStartDate) / 7);
                } elseif ($range === 'monthly') {
                    $idx = $ts->month - 1;
                } elseif ($range === 'yearly') {
                    $idx = $ts->year - $chartStartDate->year;
                }

                if ($idx < 0) $idx = 0; 
                if ($idx >= count($labels)) $idx = count($labels) - 1;

                if ($idx >= 0 && $idx < count($labels)) {
                    if ($email->is_spam) {
                        $chartSpam[$idx]++;
                    } else {
                        $chartTotal[$idx]++; 
                        
                        if (in_array($email->status, ['Responded', 'Order Done', 'Closed'])) {
                            $chartReplied[$idx]++;
                        } else { 
                            $chartPending[$idx]++;
                        }

                        if ($email->status === 'Order Done') {
                            $chartOrders[$idx]++;
                        }
                    }
                }
            }
        }
        
        $chartTrends = [
            'labels' => $labels,
            'total' => $chartTotal, 
            'replied' => $chartReplied,
            'pending' => $chartPending,
            'orders' => $chartOrders,
            'spam' => $chartSpam
        ];

        // 4. AJAX Response
        if ($request->ajax()) {
            // Estimator stats filtered by selected date range
            $ajaxEstimatorStats = ['total' => 0, 'estimated' => 0, 'pending' => 0];
            if ($currentUser && $currentUser->isEstimator()) {
                $ajaxEstimatorStats['total']     = CrmEmail::where('estimator_id', $currentUser->id)
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
                $ajaxEstimatorStats['estimated'] = CrmEmail::where('estimator_id', $currentUser->id)
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->whereIn('estimate_status', ['estimated', 'approved'])
                                                    ->count();
                $ajaxEstimatorStats['pending']   = CrmEmail::where('estimator_id', $currentUser->id)
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->whereIn('estimate_status', ['pending', 'change_requested'])
                                                    ->count();
            }

            // Designer stats filtered by selected date range
            $ajaxDesignerStats = ['total' => 0, 'completed' => 0, 'pending' => 0];
            if ($currentUser && $currentUser->isDesigner()) {
                $ajaxDesignerStats['pending']   = \App\SalesOrder::where('status', 'in_design')
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
                $ajaxDesignerStats['completed'] = \App\ProofRevision::where('uploaded_by', $currentUser->id)
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->distinct('crm_email_id')
                                                    ->count('crm_email_id');
                $ajaxDesignerStats['total']     = $ajaxDesignerStats['pending'] + $ajaxDesignerStats['completed'];
            }

            // Prepress stats filtered by selected date range
            $ajaxPrepressStats = ['total' => 0, 'completed' => 0, 'pending' => 0];
            if ($currentUser && $currentUser->isPrepress()) {
                $ajaxPrepressStats['pending']   = \App\SalesOrder::where('status', 'prepress')
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
                $ajaxPrepressStats['completed'] = \App\SalesOrder::whereNotNull('prepress_checks')
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
                $ajaxPrepressStats['total']     = $ajaxPrepressStats['pending'] + $ajaxPrepressStats['completed'];
            }

            // Production stats filtered by selected date range
            $ajaxProductionStats = ['in_queue' => 0, 'in_press' => 0, 'pending_qc' => 0, 'completed' => 0];
            if ($currentUser && $currentUser->isProductionManager()) {
                $ajaxProductionStats['in_queue']   = \App\ProductionJob::whereIn('status', ['pending_planning', 'scheduled'])
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
                $ajaxProductionStats['in_press']   = \App\ProductionJob::whereIn('status', ['press_setup', 'adjustments_required', 'production_ready', 'full_production'])
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
                $ajaxProductionStats['pending_qc'] = \App\ProductionJob::whereIn('status', ['first_sheet_review', 'final_quality_control'])
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
                $ajaxProductionStats['completed']  = \App\ProductionJob::whereIn('status', ['production_completed', 'warehouse_ready'])
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
            }

            // QC stats filtered by selected date range
            $ajaxQcStats = ['assigned' => 0, 'first_sheet' => 0, 'final_qc' => 0, 'passed' => 0];
            if ($currentUser && $currentUser->isQC()) {
                $ajaxQcStats['assigned']    = \App\ProductionJob::where('qc_inspector_id', $currentUser->id)
                                            ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                            ->count();
                $ajaxQcStats['first_sheet'] = \App\ProductionJob::where('qc_inspector_id', $currentUser->id)
                                            ->where('status', 'first_sheet_review')
                                            ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                            ->count();
                $ajaxQcStats['final_qc']    = \App\ProductionJob::where('qc_inspector_id', $currentUser->id)
                                            ->where('status', 'final_quality_control')
                                            ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                            ->count();
                $ajaxQcStats['passed']      = \App\ProductionFirstSheetCheck::where('qc_inspector_id', $currentUser->id)
                                            ->where('status', 'qc_passed')
                                            ->whereBetween('updated_at', [$statsStartDate, $currentEndDate])
                                            ->count();
            }

            // Supervisor stats filtered by selected date range
            $ajaxSupervisorStats = ['assigned' => 0, 'needs_review' => 0, 'active' => 0, 'completed' => 0];
            if (false) {
                $ajaxSupervisorStats['assigned']     = \App\ProductionJob::where('production_supervisor_id', $currentUser->id)
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
                $ajaxSupervisorStats['needs_review'] = \App\ProductionJob::where('production_supervisor_id', $currentUser->id)
                                                    ->whereIn('status', ['in_process_checks', 'coating_options', 'lamination_options'])
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
                $ajaxSupervisorStats['active']       = \App\ProductionJob::where('production_supervisor_id', $currentUser->id)
                                                    ->whereIn('status', ['die_cutting', 'stripping', 'blank_separation', 'gluing', 'packing', 'palletization'])
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
                $ajaxSupervisorStats['completed']    = \App\ProductionJob::where('production_supervisor_id', $currentUser->id)
                                                    ->whereIn('status', ['warehouse_ready', 'production_completed'])
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
            }

            // Press Operator stats filtered by selected date range
            $ajaxPressStats = ['assigned' => 0, 'in_setup' => 0, 'running' => 0, 'completed' => 0];
            if ($currentUser && $currentUser->isPressOperator()) {
                $ajaxPressStats['assigned']  = \App\ProductionJob::where('press_operator_id', $currentUser->id)
                                            ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                            ->count();
                $ajaxPressStats['in_setup']  = \App\ProductionJob::where('press_operator_id', $currentUser->id)
                                            ->whereIn('status', ['scheduled', 'press_setup', 'adjustments_required', 'production_ready'])
                                            ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                            ->count();
                $ajaxPressStats['running']   = \App\ProductionJob::where('press_operator_id', $currentUser->id)
                                            ->whereIn('status', ['full_production'])
                                            ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                            ->count();
                $ajaxPressStats['completed'] = \App\ProductionJob::where('press_operator_id', $currentUser->id)
                                            ->whereNotIn('status', ['pending_planning', 'scheduled', 'press_setup', 'adjustments_required', 'production_ready', 'full_production', 'first_sheet_review'])
                                            ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                            ->count();
            }

            // Accounts stats filtered by selected date range
            $ajaxAccountsStats = ['pending_check' => 0, 'pending_payment' => 0, 'invoiced' => 0, 'completed' => 0];
            if ($currentUser && $currentUser->isAccounts()) {
                $ajaxAccountsStats['pending_check']   = \App\SalesOrder::whereIn('shipping_stage', ['balance_payment_check'])
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
                $ajaxAccountsStats['pending_payment'] = \App\SalesOrder::whereIn('shipping_stage', ['final_payment_pending'])
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
                $ajaxAccountsStats['invoiced']        = \App\SalesOrder::whereIn('shipping_stage', ['delivered', 'final_invoice'])
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
                $ajaxAccountsStats['completed']       = \App\SalesOrder::whereIn('shipping_stage', ['payment_posted'])
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
            }

            // Shipping stats filtered by selected date range
            $ajaxShippingStats = ['pending' => 0, 'label_generated' => 0, 'in_transit' => 0, 'delivered' => 0];
            if ($currentUser && $currentUser->isShipping()) {
                $ajaxShippingStats['pending']         = \App\SalesOrder::whereIn('shipping_stage', ['ready_to_ship', 'shipping_department'])
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
                $ajaxShippingStats['label_generated'] = \App\SalesOrder::whereIn('shipping_stage', ['shipping_label_generated'])
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
                $ajaxShippingStats['in_transit']      = \App\SalesOrder::whereIn('shipping_stage', ['in_transit'])
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
                $ajaxShippingStats['delivered']       = \App\SalesOrder::whereIn('shipping_stage', ['delivered'])
                                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                    ->count();
            }

            return response()->json([
                'stats' => [
                    'total' => number_format($totalEmails),
                    'spam' => number_format($spamEmails),
                    'replied' => number_format($repliedEmails),
                    'orders' => number_format($ordersDone),
                ],
                'trends' => $trends, // Pass Trends
                'chart' => $chartTrends,
                'estimator' => $ajaxEstimatorStats,
                'designer' => $ajaxDesignerStats,
                'prepress' => $ajaxPrepressStats,
                'production' => $ajaxProductionStats,
                'qc' => $ajaxQcStats,
                'supervisor' => $ajaxSupervisorStats,
                'press' => $ajaxPressStats,
                'accounts' => $ajaxAccountsStats,
                'shipping' => $ajaxShippingStats,
            ]);
        }

        // 5. Initial Page Load (Full Data)
        // We need existing variables: sources, followups, recent, location
        // We'll fetch these as "Recent/All Time" to keep the dashboard populated
        
        $recentQuery = CrmEmail::where('is_spam', false);
        if ($isRestricted) {
            $recentQuery->where('assigned_to', $currentUser->id);
        }
        $recentEmails = $recentQuery->orderBy('created_at', 'desc')->take(8)->get();
        
        $followQuery = CrmEmail::where('is_spam', false)
            ->where('status', 'Viewed')
            ->where('created_at', '<', Carbon::now()->subHours(24));
        if ($isRestricted) {
            $followQuery->where('assigned_to', $currentUser->id);
        }
        $followUps = $followQuery->orderBy('created_at', 'asc')->take(5)->get();

        // Source Chart (All Time for better distribution data)
        $sourcesQuery = CrmEmail::where('is_spam', false);
        if ($isRestricted) {
            $sourcesQuery->where('assigned_to', $currentUser->id);
        }
        $sourcesRaw = $sourcesQuery->take(100)->get(); // Limit for perf
        
        $sourceCounts = ['Contact Us'=>0,'Product Page'=>0,'Quote Request'=>0,'Other'=>0];
        foreach ($sourcesRaw as $email) {
             $src = $email->product_name ?? $email->subject;
             if (stripos($src, 'Quote')!==false) $sourceCounts['Quote Request']++;
             elseif (stripos($src, 'Contact')!==false) $sourceCounts['Contact Us']++;
             else $sourceCounts['Other']++;
        }
        $sourceChartData = ['labels'=>array_keys($sourceCounts), 'data'=>array_values($sourceCounts)];
        
        // Location Data (Simplified for brevity, keeps existing logic mostly)
        $locQuery = CrmEmail::where('is_spam', false);
        if ($isRestricted) {
            $locQuery->where('assigned_to', $currentUser->id);
        }
        $emailsForLoc = $locQuery->get();
        
        $locationCounts = [];
        foreach ($emailsForLoc as $r) {
             $c = $r->country ?: 'USA'; 
             if (!isset($locationCounts[$c])) $locationCounts[$c]=0; 
             $locationCounts[$c]++; 
        }
        arsort($locationCounts);
        $locationData = [];
        $totalLoc = $emailsForLoc->count();
        foreach(array_slice($locationCounts,0,3) as $k=>$v) {
            $locationData[] = ['name'=>$k, 'percent'=> $totalLoc?round($v/$totalLoc*100):0];
        }

        // Dummy Variables for View to prevent errors (Month comparison)
        $totalMonth = 0; $newMonth = 0; $pendingMonth = 0; $ordersMonth = 0;
        $spamMonth = 0; $repliedMonth = 0;
        $statusChartData = ['labels'=>[], 'data'=>[]]; // Not used if we update chartTrends

        // ── ESTIMATOR STATS ──────────────────────────────────────────────────────
        // Apply the same date range as the active filter (default = Today)
        // so page load matches what "Today" button shows
        $estimatorStats = ['total' => 0, 'estimated' => 0, 'pending' => 0];
        if ($currentUser && $currentUser->isEstimator()) {
            $estimatorStats['total']     = CrmEmail::where('estimator_id', $currentUser->id)
                                            ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                            ->count();
            $estimatorStats['estimated'] = CrmEmail::where('estimator_id', $currentUser->id)
                                            ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                            ->whereIn('estimate_status', ['estimated', 'approved'])
                                            ->count();
            $estimatorStats['pending']   = CrmEmail::where('estimator_id', $currentUser->id)
                                            ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                            ->whereIn('estimate_status', ['pending', 'change_requested'])
                                            ->count();
        }

        // ── DESIGNER STATS ──────────────────────────────────────────────────────
        $designerStats = ['total' => 0, 'completed' => 0, 'pending' => 0];
        if ($currentUser && $currentUser->isDesigner()) {
            $designerStats['pending']   = \App\SalesOrder::where('status', 'in_design')
                                            ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                            ->count();
            $designerStats['completed'] = \App\ProofRevision::where('uploaded_by', $currentUser->id)
                                            ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                            ->distinct('crm_email_id')
                                            ->count('crm_email_id');
            $designerStats['total']     = $designerStats['pending'] + $designerStats['completed'];
        }

        // ── PREPRESS STATS ──────────────────────────────────────────────────────
        $prepressStats = ['total' => 0, 'completed' => 0, 'pending' => 0];
        if ($currentUser && $currentUser->isPrepress()) {
            $prepressStats['pending']   = \App\SalesOrder::where('status', 'prepress')
                                            ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                            ->count();
            $prepressStats['completed'] = \App\SalesOrder::whereNotNull('prepress_checks')
                                            ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                            ->count();
            $prepressStats['total']     = $prepressStats['pending'] + $prepressStats['completed'];
        }

        // ── PRODUCTION STATS ────────────────────────────────────────────────────
        $productionStats = ['in_queue' => 0, 'in_press' => 0, 'pending_qc' => 0, 'completed' => 0];
        if ($currentUser && $currentUser->isProductionManager()) {
            $productionStats['in_queue']   = \App\ProductionJob::whereIn('status', ['pending_planning', 'scheduled'])
                                                ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                ->count();
            $productionStats['in_press']   = \App\ProductionJob::whereIn('status', ['press_setup', 'adjustments_required', 'production_ready', 'full_production'])
                                                ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                ->count();
            $productionStats['pending_qc'] = \App\ProductionJob::whereIn('status', ['first_sheet_review', 'final_quality_control'])
                                                ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                ->count();
            $productionStats['completed']  = \App\ProductionJob::whereIn('status', ['production_completed', 'warehouse_ready'])
                                                ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                                ->count();
        }

        // ── QC STATS ────────────────────────────────────────────────────────────
        $qcStats = ['assigned' => 0, 'first_sheet' => 0, 'final_qc' => 0, 'passed' => 0];
        if ($currentUser && $currentUser->isQC()) {
            $qcStats['assigned']    = \App\ProductionJob::where('qc_inspector_id', $currentUser->id)
                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                    ->count();
            $qcStats['first_sheet'] = \App\ProductionJob::where('qc_inspector_id', $currentUser->id)
                                    ->where('status', 'first_sheet_review')
                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                    ->count();
            $qcStats['final_qc']    = \App\ProductionJob::where('qc_inspector_id', $currentUser->id)
                                    ->where('status', 'final_quality_control')
                                    ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                    ->count();
            $qcStats['passed']      = \App\ProductionFirstSheetCheck::where('qc_inspector_id', $currentUser->id)
                                    ->where('status', 'qc_passed')
                                    ->whereBetween('updated_at', [$statsStartDate, $currentEndDate])
                                    ->count();
        }

        // ── PRODUCTION SUPERVISOR STATS ─────────────────────────────────────────
        $supervisorStats = ['assigned' => 0, 'needs_review' => 0, 'active' => 0, 'completed' => 0];
        if (false) {
            $supervisorStats['assigned']     = \App\ProductionJob::where('production_supervisor_id', $currentUser->id)
                                            ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                            ->count();
            $supervisorStats['needs_review'] = \App\ProductionJob::where('production_supervisor_id', $currentUser->id)
                                            ->whereIn('status', ['in_process_checks', 'coating_options', 'lamination_options'])
                                            ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                            ->count();
            $supervisorStats['active']       = \App\ProductionJob::where('production_supervisor_id', $currentUser->id)
                                            ->whereIn('status', ['die_cutting', 'stripping', 'blank_separation', 'gluing', 'packing', 'palletization'])
                                            ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                            ->count();
            $supervisorStats['completed']    = \App\ProductionJob::where('production_supervisor_id', $currentUser->id)
                                            ->whereIn('status', ['warehouse_ready', 'production_completed'])
                                            ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                            ->count();
        }

        // ── PRESS OPERATOR STATS ────────────────────────────────────────────────
        $pressStats = ['assigned' => 0, 'in_setup' => 0, 'running' => 0, 'completed' => 0];
        if ($currentUser && $currentUser->isPressOperator()) {
            $pressStats['assigned']  = \App\ProductionJob::where('press_operator_id', $currentUser->id)
                                        ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                        ->count();
            $pressStats['in_setup']  = \App\ProductionJob::where('press_operator_id', $currentUser->id)
                                        ->whereIn('status', ['scheduled', 'press_setup', 'adjustments_required', 'production_ready'])
                                        ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                        ->count();
            $pressStats['running']   = \App\ProductionJob::where('press_operator_id', $currentUser->id)
                                        ->whereIn('status', ['full_production'])
                                        ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                        ->count();
            $pressStats['completed'] = \App\ProductionJob::where('press_operator_id', $currentUser->id)
                                        ->whereNotIn('status', ['pending_planning', 'scheduled', 'press_setup', 'adjustments_required', 'production_ready', 'full_production', 'first_sheet_review'])
                                        ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                        ->count();
        }
        // ── ACCOUNTS STATS ──────────────────────────────────────────────────────
        $accountsStats = ['pending_check' => 0, 'pending_payment' => 0, 'invoiced' => 0, 'completed' => 0];
        if ($currentUser && $currentUser->isAccounts()) {
            $accountsStats['pending_check']   = \App\SalesOrder::whereIn('shipping_stage', ['balance_payment_check'])
                                        ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                        ->count();
            $accountsStats['pending_payment'] = \App\SalesOrder::whereIn('shipping_stage', ['final_payment_pending'])
                                        ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                        ->count();
            $accountsStats['invoiced']        = \App\SalesOrder::whereIn('shipping_stage', ['delivered', 'final_invoice'])
                                        ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                        ->count();
            $accountsStats['completed']       = \App\SalesOrder::whereIn('shipping_stage', ['payment_posted'])
                                        ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                        ->count();
        }
        // ── SHIPPING STATS ──────────────────────────────────────────────────────
        $shippingStats = ['pending' => 0, 'label_generated' => 0, 'in_transit' => 0, 'delivered' => 0];
        if ($currentUser && $currentUser->isShipping()) {
            $shippingStats['pending']         = \App\SalesOrder::whereIn('shipping_stage', ['ready_to_ship', 'shipping_department'])
                                        ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                        ->count();
            $shippingStats['label_generated'] = \App\SalesOrder::whereIn('shipping_stage', ['shipping_label_generated'])
                                        ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                        ->count();
            $shippingStats['in_transit']      = \App\SalesOrder::whereIn('shipping_stage', ['in_transit'])
                                        ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                        ->count();
            $shippingStats['delivered']       = \App\SalesOrder::whereIn('shipping_stage', ['delivered'])
                                        ->whereBetween('created_at', [$statsStartDate, $currentEndDate])
                                        ->count();
        }
        // ─────────────────────────────────────────────────────────────────────────

        return view('crm.dashboard', compact(
            'totalEmails', 'spamEmails', 'repliedEmails', 'ordersDone', 'trends',
            'chartTrends', 'recentEmails', 'followUps', 
            'sourceChartData', 'locationData',
            'totalMonth', 'newMonth', 'pendingMonth', 'ordersMonth', 'spamMonth', 'repliedMonth', 'statusChartData',
            'estimatorStats', 'designerStats', 'prepressStats', 'productionStats', 'qcStats', 'supervisorStats', 'pressStats', 'accountsStats', 'shippingStats'
        ));
    }
}
