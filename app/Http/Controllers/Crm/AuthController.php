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

        $user = \App\CrmUser::where('email', $email)->first();
        if (!$user) {
            return back()->with('error', 'User not found in CRM system.');
        }

        // Login step B: LOCAL password first for everyone. The legacy mailbox (IMAP) check is
        // only a guarded fallback for sales-only accounts (see config/crm.php) so nobody is
        // locked out while their local hash catches up; a successful fallback re-hashes locally.
        $authenticated = $this->verifyLocal($password, $user);
        $usedFallback = false;
        if (!$authenticated && $this->mailboxFallbackApplies($email, $user)) {
            $authenticated = app(\App\Services\Auth\MailboxPasswordVerifier::class)->verify($user, $email, $password);
            $usedFallback = $authenticated;
        }

        if (!$authenticated) {
            \Log::info('CRM login failed', ['user_id' => $user->id]);
            return back()->with('error', 'Incorrect password. Use "Forgot password?" to reset it.');
        }

        $updates = ['last_login_at' => now()];
        if ($usedFallback) {
            // Self-heal: the mailbox password is now also the local password.
            $updates['password'] = Hash::make($password);
            $updates['imap_login_fallback_at'] = now();
            \Log::notice('CRM login used mailbox fallback', ['user_id' => $user->id]);
        }
        $user->forceFill($updates)->save();

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

        if ($user->isProductionManager()) return redirect()->route('crm.production_jobs.index');
        if ($user->isPressOperator())     return redirect()->route('crm.press_tickets.index');
        if ($user->isQC())                return redirect()->route('crm.qc_tickets.index');
        if ($user->isWarehouse())         return redirect()->route('crm.warehouse_tickets.index');
        if ($user->isAccounts())          return redirect()->route('crm.accounts_tickets.index');
        if ($user->isShipping())          return redirect()->route('crm.shipping_tickets.index');
        if ($user->isRetention())         return redirect()->route('crm.retention_tickets.index');

        return redirect()->route('crm.dashboard');
    }

    private function verifyLocal(string $password, \App\CrmUser $user): bool
    {
        return !empty($user->password) && Hash::check($password, $user->password);
    }

    /** Sales-only accounts (every workspace role is sales/sales_manager) may fall back to the mailbox check while enabled. */
    private function mailboxFallbackApplies(string $email, \App\CrmUser $user): bool
    {
        if (!config('crm.auth.imap_login_fallback', true)) return false;
        if (str_ends_with($email, '@example.com')) return false; // local/test accounts never hit IMAP
        $roles = $user->workspaces()->pluck('crm_user_workspace.role')->all();
        return !empty($roles) && empty(array_diff($roles, ['sales', 'sales_manager']));
    }

    // ---- self-service password reset (crm broker) ---------------------------------------

    public function showForgotForm()
    {
        return view('crm.auth.forgot_password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $status = \Illuminate\Support\Facades\Password::broker('crm')->sendResetLink(['email' => strtolower(trim($request->email))]);
        // Always the same message — do not reveal whether the address exists.
        \Log::info('CRM password reset requested', ['status' => $status]);
        return back()->with('status', 'If that email belongs to a CRM account, a reset link has been sent.');
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('crm.auth.reset_password', ['token' => $token, 'email' => (string) $request->query('email', '')]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => ['required', 'confirmed', 'min:8', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[@$!%*#?&^()_\-+=\[\]{}|;:,.<>?\/\\\\~`]/'],
        ], ['password.regex' => 'Password must include uppercase, lowercase, number, and special character.']);

        $status = \Illuminate\Support\Facades\Password::broker('crm')->reset(
            ['email' => strtolower(trim($request->email)), 'password' => $request->password, 'password_confirmation' => $request->password_confirmation, 'token' => $request->token],
            function (\App\CrmUser $user, string $password) {
                $user->forceFill(['password' => Hash::make($password), 'password_changed_at' => now(), 'remember_token' => \Illuminate\Support\Str::random(60)])->save();
            }
        );

        if ($status === \Illuminate\Support\Facades\Password::PASSWORD_RESET) {
            return redirect()->route('crm.login')->with('status', 'Your password has been reset. Please sign in.');
        }
        return back()->withInput($request->only('email'))->with('error', __($status));
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
            $user->password_changed_at = now();
        }

        $user->signature = $request->input('signature');
        $user->save();

        return back()->with('success', 'Profile updated successfully!');
    }
}
