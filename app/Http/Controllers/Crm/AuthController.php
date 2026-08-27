<?php

namespace App\Http\Controllers\Crm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('crm.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $email = strtolower(trim($request->email));
        $password = $request->password;

        // 1. Find the user in our system first
        $user = \App\CrmUser::where('email', $email)->first();

        if (!$user) {
            return back()->with('error', 'User not found in CRM system.');
        }

        // 2. MANDATORY Hostinger/IMAP Check
        // This ensures the CRM password MUST be the same as the Email password
        if ($this->verifyImap($email, $password, $user)) {

            // Success! Update local database khudi to stay in sync
            $user->password = Hash::make($password);
            $user->email_pass = $password;
            $user->save();

            // Log them in
            Auth::guard('crm')->login($user);
            $request->session()->regenerate();

            $workspaces = $user->workspaces()->where('is_active', true)->orderBy('crm_workspaces.id')->get();
            if ($workspaces->isEmpty()) {
                Auth::guard('crm')->logout();
                return back()->with('error', 'No active CRM workspace is assigned to this account.');
            }

            $preferredWorkspace = $workspaces->firstWhere('id', (int) session('crm_workspace_id')) ?: $workspaces->first();
            session(['crm_workspace_id' => $preferredWorkspace->id]);
            \App\Support\CrmWorkspaceContext::set($preferredWorkspace->id);

            if ($user->isProductionManager()) {
                return redirect()->route('crm.production_jobs.index');
            }
            if ($user->isPressOperator()) {
                return redirect()->route('crm.press_tickets.index');
            }
            if ($user->isQC()) {
                return redirect()->route('crm.qc_tickets.index');
            }

            if ($user->isWarehouse()) {
                return redirect()->route('crm.warehouse_tickets.index');
            }
            if ($user->isAccounts()) {
                return redirect()->route('crm.accounts_tickets.index');
            }
            if ($user->isShipping()) {
                return redirect()->route('crm.shipping_tickets.index');
            }
            if ($user->isRetention()) {
                return redirect()->route('crm.retention_tickets.index');
            }

            return redirect()->route('crm.dashboard');
        }

        // 3. If IMAP fails, they cannot login at all
        return back()->with('error', 'Incorrect password. Your CRM password must match your Hostinger email password.');
    }

    /**
     * Helper to verify password against Hostinger/IMAP
     */
    private function verifyImap($email, $password, $user)
    {
        // Bypass IMAP check for local test/seed accounts ending in @example.com
        if (strpos($email, '@example.com') !== false) {
            return \Hash::check($password, $user->password);
        }

        // Bypass IMAP check for non-sales roles
        // Login happens before a workspace is selected, so consult every pivot.
        // Any privileged/non-sales workspace uses the locally managed CRM password.
        $workspaceRoles = $user->workspaces()->pluck('crm_user_workspace.role')->all();
        $requiresMailboxPassword = !empty($workspaceRoles)
            && empty(array_diff($workspaceRoles, ['sales', 'sales_manager']));
        if (!$requiresMailboxPassword) {
            return \Hash::check($password, $user->password);
        }

        $host = $user->imap_host ?: 'imap.hostinger.com';
        $port = $user->imap_port ?: 993;
        $enc = $user->imap_encryption ?: 'ssl';

        // Build IMAP connection string
        // novalidate-cert is often needed for shared hosting
        $mailbox = "{" . $host . ":" . $port . "/imap/" . $enc . "/novalidate-cert}INBOX";

        // Attempt connection with 1 attempt limit to avoid hanging
        // OP_HALFOPEN is faster as it just checks auth
        $conn = @imap_open($mailbox, $email, $password, OP_HALFOPEN, 1);

        if ($conn) {
            imap_close($conn);
            return true;
        }

        return false;
    }

    public function logout()
    {
        $user = Auth::guard('crm')->user();
        if ($user) {
            \App\CrmUser::where('id', $user->id)->update([
                'last_seen_at' => now()->subSeconds(31),
            ]);
        }

        Auth::guard('crm')->logout();
        return redirect()->route('crm.login');
    }

    public function showChangePassword()
    {
        return view('crm.auth.change_password');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::guard('crm')->user();

        if ($request->filled('password') || $request->filled('current_password')) {
            $request->validate([
                'current_password' => 'required',
                'password' => [
                    'required',
                    'confirmed',
                    'min:8',
                    'regex:/[a-z]/',      // lowercase
                    'regex:/[A-Z]/',      // uppercase
                    'regex:/[0-9]/',      // number
                    'regex:/[@$!%*#?&^()_\-+=\[\]{}|;:,.<>?\/\\\\~`]/', // special char
                ],
            ], [
                'password.min' => 'Password must be at least 8 characters.',
                'password.regex' => 'Password must include uppercase, lowercase, number, and special character.',
                'password.confirmed' => 'Confirm password does not match.',
            ]);

            if (!Hash::check($request->current_password, $user->password)) {
                return back()->with('error', 'Current password is incorrect.');
            }

            $user->password = Hash::make($request->password);
        }

        $user->signature = $request->input('signature');
        $user->save();

        return back()->with('success', 'Profile updated successfully!');
    }
}
