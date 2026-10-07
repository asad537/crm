<?php

namespace App\Http\Middleware;

use App\CrmPortalAccount;
use Closure;
use Illuminate\Http\Request;

/** Guards the customer invoice portal: requires a logged-in portal account in the session. */
class InvoicePortalAuth
{
    public function handle(Request $request, Closure $next)
    {
        $id = $request->session()->get('invoice_portal_account_id');
        $account = $id ? CrmPortalAccount::find($id) : null;
        if (!$account) {
            $request->session()->forget('invoice_portal_account_id');
            return redirect()->route('invoice_portal.login')->with('error', 'Please log in to view your invoices.');
        }
        $request->attributes->set('portal_account', $account);
        return $next($request);
    }
}
