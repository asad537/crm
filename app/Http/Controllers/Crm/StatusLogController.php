<?php

namespace App\Http\Controllers\Crm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CrmStatusLog;

class StatusLogController extends Controller
{
    public function index(Request $request)
    {
        $query = CrmStatusLog::with('email')->orderBy('created_at', 'desc');

        // Default to 'today' if no filter is provided
        if (!$request->has('date_filter')) {
            $request->merge(['date_filter' => 'today']);
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

        if ($request->has('user') && $request->user != '') {
            $query->where('user_name', $request->user);
        }

        $logs = $query->paginate(9);
        
        $users = \App\CrmUser::inWorkspace()->get();
                            
        return view('crm.logs.index', compact('logs', 'users'));
    }
}
