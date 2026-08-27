<?php

namespace App\Http\Controllers\Crm;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\CrmUser;
use App\ProductionFacility;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserManagementController extends Controller
{
    public function index()
    {
        $currentUser = Auth::guard('crm')->user();
        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager()) {
            return redirect()->route('crm.dashboard')->with('error', 'Unauthorized');
        }

        $workspaceId = session('crm_workspace_id');
        $query = CrmUser::with(['workspaces' => function ($q) use ($workspaceId) {
            $q->where('crm_workspaces.id', $workspaceId);
        }])->whereHas('workspaces', function ($q) use ($workspaceId) {
            $q->where('crm_workspaces.id', $workspaceId);
        });

        // Owner accounts are private and must not be exposed in a normal
        // Admin/Sales Manager directory for the active workspace.
        if (!$currentUser->isSuperAdmin()) {
            $query->whereDoesntHave('workspaces', function ($q) use ($workspaceId) {
                $q->where('crm_workspaces.id', $workspaceId)
                    ->where('crm_user_workspace.role', 'super_admin');
            });
        }

        if ($currentUser->isSalesManager()) {
            $query->whereHas('workspaces', function ($q) use ($workspaceId) {
                $q->where('crm_workspaces.id', $workspaceId)
                    ->where('crm_user_workspace.role', 'sales');
            });
        }

        $users = $query->orderByRaw("
            CASE role
                WHEN 'super_admin' THEN 1
                WHEN 'admin' THEN 2
                WHEN 'sales_manager' THEN 3
                WHEN 'sales' THEN 4
                WHEN 'designer' THEN 4
                WHEN 'prepress' THEN 5
                WHEN 'retention' THEN 6
                WHEN 'qc' THEN 7
                WHEN 'shipping' THEN 8
                WHEN 'production_manager' THEN 9
                WHEN 'press_operator' THEN 10
                WHEN 'production_supervisor' THEN 11
                WHEN 'finishing_operator' THEN 12
                WHEN 'warehouse' THEN 13
                WHEN 'accounts' THEN 14
                ELSE 15
            END
        ")->paginate(9);
        return view('crm.users.index', compact('users'));
    }

    public function create()
    {
        $currentUser = Auth::guard('crm')->user();
        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager()) {
            return redirect()->route('crm.dashboard')->with('error', 'Unauthorized');
        }
        $facilities = ProductionFacility::where('is_active', true)->orderBy('city')->get();
        return view('crm.users.create', compact('facilities'));
    }

    public function store(Request $request)
    {
        $currentUser = Auth::guard('crm')->user();
        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager()) {
            return redirect()->route('crm.dashboard')->with('error', 'Unauthorized');
        }
        
        $rules = [
            'name' => 'required',
            'email' => 'required|email|unique:crm_users',
            'password' => 'required|min:6',
            'allowed_ip' => 'nullable',
            'email_user' => 'nullable|email',
            'email_pass' => 'nullable',
        ];

        if ($currentUser->isAdmin()) {
            $allowedRoles = 'admin,sales_manager,sales,designer,prepress,retention,qc,shipping,estimator,team_lead,production_manager,press_operator,finishing_operator,warehouse,accounts';
            if ($currentUser->isSuperAdmin()) $allowedRoles = 'super_admin,' . $allowedRoles;
            $rules['role'] = 'required|in:' . $allowedRoles;
            $rules['production_facility_id'] = 'required_if:role,production_manager,press_operator,finishing_operator,qc,warehouse|nullable|exists:production_facilities,id';
        } else {
            $request->merge(['role' => 'sales']);
            $rules['role'] = 'required|in:sales';
        }
        
        $request->validate($rules);

        $user = CrmUser::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'production_facility_id' => $this->facilityForRole($request),
            'allowed_ip' => $request->allowed_ip,
            'email_user' => $request->email_user,
            'email_pass' => $request->email_pass,
            'imap_host' => $request->imap_host ?: 'imap.hostinger.com',
            'imap_port' => $request->imap_port ?: '993',
            'imap_encryption' => $request->imap_encryption ?: 'ssl',
            'smtp_host' => $request->smtp_host ?: 'smtp.hostinger.com',
            'smtp_port' => $request->smtp_port ?: '587',
            'smtp_encryption' => $request->smtp_encryption ?: 'tls',
        ]);
        if ($request->role === 'super_admin') {
            $workspaceRoles = \App\CrmWorkspace::where('is_active', true)
                ->pluck('id')
                ->mapWithKeys(function ($workspaceId) {
                    return [$workspaceId => ['role' => 'super_admin']];
                })->all();
            $user->workspaces()->syncWithoutDetaching($workspaceRoles);
        } else {
            $user->workspaces()->attach(session('crm_workspace_id'), ['role' => $request->role]);
        }

        return redirect()->route('crm.users.index')->with('success', 'User created successfully.');
    }

    public function edit($id)
    {
        $currentUser = Auth::guard('crm')->user();
        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager()) {
            return redirect()->route('crm.dashboard')->with('error', 'Unauthorized');
        }
        $user = CrmUser::findOrFail($id);
        $workspaceRole = $user->roleForWorkspace(session('crm_workspace_id')) ?: $user->role;

        if ($user->isSuperAdmin() && !$currentUser->isSuperAdmin()) {
            return redirect()->route('crm.users.index')->with('error', 'Only an Owner can manage this account.');
        }
        
        if ($currentUser->isSalesManager() && $workspaceRole !== 'sales') {
            return redirect()->route('crm.users.index')->with('error', 'Unauthorized to edit this user.');
        }
        
        $facilities = ProductionFacility::where('is_active', true)->orderBy('city')->get();
        return view('crm.users.edit', compact('user', 'facilities', 'workspaceRole'));
    }

    public function update(Request $request, $id)
    {
        $currentUser = Auth::guard('crm')->user();
        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager()) {
            return redirect()->route('crm.dashboard')->with('error', 'Unauthorized');
        }
        
        $user = CrmUser::findOrFail($id);
        $workspaceRole = $user->roleForWorkspace(session('crm_workspace_id')) ?: $user->role;

        if ($user->isSuperAdmin() && !$currentUser->isSuperAdmin()) {
            return redirect()->route('crm.users.index')->with('error', 'Only an Owner can manage this account.');
        }
        
        if ($currentUser->isSalesManager() && $workspaceRole !== 'sales') {
            return redirect()->route('crm.users.index')->with('error', 'Unauthorized to update this user.');
        }
        
        $rules = [
            'name' => 'required',
            'email' => 'required|email|unique:crm_users,email,'.$id,
            'email_user' => 'nullable|email',
        ];

        if ($currentUser->isAdmin()) {
            $allowedRoles = 'admin,sales_manager,sales,designer,prepress,retention,qc,shipping,estimator,team_lead,production_manager,press_operator,finishing_operator,warehouse,accounts';
            if ($currentUser->isSuperAdmin()) $allowedRoles = 'super_admin,' . $allowedRoles;
            $rules['role'] = 'required|in:' . $allowedRoles;
            $rules['production_facility_id'] = 'required_if:role,production_manager,press_operator,finishing_operator,qc,warehouse|nullable|exists:production_facilities,id';
        } else {
            $request->merge(['role' => 'sales']);
            $rules['role'] = 'required|in:sales';
        }

        $request->validate($rules);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'production_facility_id' => $this->facilityForRole($request),
            'allowed_ip' => $request->allowed_ip,
            'email_user' => $request->email_user,
            'email_pass' => $request->email_pass ?: $user->email_pass,
            'imap_host' => $request->imap_host ?: 'imap.hostinger.com',
            'imap_port' => $request->imap_port ?: '993',
            'imap_encryption' => $request->imap_encryption ?: 'ssl',
            'smtp_host' => $request->smtp_host ?: 'smtp.hostinger.com',
            'smtp_port' => $request->smtp_port ?: '587',
            'smtp_encryption' => $request->smtp_encryption ?: 'tls',
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->workspaces()->updateExistingPivot(session('crm_workspace_id'), ['role' => $request->role]);
        if ($request->role === 'super_admin') {
            $workspaceRoles = \App\CrmWorkspace::where('is_active', true)
                ->pluck('id')
                ->mapWithKeys(function ($workspaceId) {
                    return [$workspaceId => ['role' => 'super_admin']];
                })->all();
            $user->workspaces()->syncWithoutDetaching($workspaceRoles);
        }

        return redirect()->route('crm.users.index')->with('success', 'User updated successfully.');
    }

    private function facilityForRole(Request $request)
    {
        $facilityRoles = ['production_manager', 'press_operator', 'finishing_operator', 'qc', 'warehouse'];
        return in_array($request->role, $facilityRoles, true) ? $request->production_facility_id : null;
    }

    public function testConnection(Request $request)
    {
        $host = $request->imap_host ?: 'imap.hostinger.com';
        $port = $request->imap_port ?: '993';
        $user = $request->email_user;
        $pass = $request->email_pass;

        if (!$user || !$pass) {
            return response()->json(['success' => false, 'message' => 'Email and Password are required for verification.']);
        }

        // Hostinger usually uses /ssl
        // Add /novalidate-cert to bypass self-signed certificate errors common with Hostinger
        $mailbox = "{" . $host . ":" . $port . "/imap/ssl/novalidate-cert}INBOX";
        
        // Timeout set to 10 seconds
        imap_timeout(IMAP_OPENTIMEOUT, 10);
        
        // Suppress errors with @ and check connection
        $connection = @imap_open($mailbox, $user, $pass, OP_HALFOPEN);
        
        if ($connection) {
            imap_close($connection);
            return response()->json(['success' => true, 'message' => 'Hostinger connection verified successfully!']);
        } else {
            $error = imap_last_error();
            return response()->json(['success' => false, 'message' => 'Verification failed: ' . ($error ?: 'Unknown error')]);
        }
    }

    public function destroy($id)
    {
        $currentUser = Auth::guard('crm')->user();
        if (!$currentUser->isAdmin() && !$currentUser->isSalesManager()) {
            return redirect()->route('crm.dashboard')->with('error', 'Unauthorized');
        }
        
        $user = CrmUser::findOrFail($id);

        if ($user->isSuperAdmin() && !$currentUser->isSuperAdmin()) {
            return redirect()->route('crm.users.index')->with('error', 'Only an Owner can remove this account.');
        }
        
        if ($currentUser->isSalesManager() && $user->role !== 'sales') {
            return redirect()->route('crm.users.index')->with('error', 'Unauthorized to delete this user.');
        }
        
        $user->delete();
        return redirect()->back()->with('success', 'User deleted.');
    }
}
