<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\FinancialReportService;
use Illuminate\Support\Facades\Auth;

/**
 * Al Massa "Chart of Accounts": every account the CRM keeps (bank & cash, receivables, fixed assets, payables,
 * owner's capital, income and expense categories) with its balance, each opening the entries behind it.
 * Admins and accounts only, because Equity shows the owners' capital.
 */
class ChartOfAccountsController extends Controller
{
    public function index()
    {
        $user = Auth::guard('crm')->user();
        abort_unless($user->isAdmin() || $user->isAccounts(), 403, 'Only admins and accounts can open the Chart of Accounts.');

        // Balance-sheet accounts are as of today; income and expense accounts are this year so far.
        return view('crm.chart_of_accounts.index', [
            'chart' => (new FinancialReportService(now()->startOfYear(), now()))->chartOfAccounts(),
        ]);
    }
}
