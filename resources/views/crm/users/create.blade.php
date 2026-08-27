@extends('crm.layout')


@section('content')

    <style>
        :root {
            --primary: var(--primary-purple);
            --primary-dark: var(--primary-hover);
            --secondary: #0f172a;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
            --bg: #f8fafc;
            --white: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        .user-page {
            padding: 20px;
        }

        .user-wrapper {
            max-width: 1200px;
            margin: auto;
        }

        .top-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .header-left h2 {
            margin: 0;
            font-size: 32px;
            font-weight: 800;
            color: var(--text);
            letter-spacing: -1px;
        }

        .header-left p {
            margin: 8px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .header-badge {
            background: linear-gradient(135deg, var(--primary), var(--primary-purple));
            color: #fff;
            padding: 14px 18px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 30px rgba(var(--primary-rgb), .2);
        }

        .header-badge i {
            font-size: 20px;
        }

        .main-card {
            background: var(--white);
            border-radius: 28px;
            overflow: hidden;
            border: 1px solid #edf2f7;
            box-shadow: 0 25px 60px rgba(15, 23, 42, .06);
            position: relative;
        }

        .main-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, var(--primary), var(--primary-purple), #06b6d4);
        }

        .card-body {
            padding: 35px;
        }

        .form-layout {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 30px;
        }

        .section-card {
            background: #fff;
            border: 1px solid #edf2f7;
            border-radius: 24px;
            padding: 28px;
            height: 100%;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 28px;
        }

        .section-icon {
            width: 50px;
            height: 50px;
            border-radius: 16px;
            background: linear-gradient(135deg, #eef2ff, var(--primary-soft));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 18px;
        }

        .section-title h4 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            color: var(--text);
        }

        .section-title p {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: 13px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-size: 13px;
            font-weight: 700;
            color: #334155;
        }

        .required {
            color: var(--danger);
        }

        .input-group-modern {
            position: relative;
        }

        .input-group-modern .icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            transition: .3s;
            font-size: 15px;
        }

        .modern-input,
        .modern-select {
            width: 100%;
            height: 58px;
            border-radius: 18px;
            border: 1.8px solid var(--border);
            background: #f8fafc;
            padding: 0 18px 0 52px;
            font-size: 15px;
            font-weight: 600;
            color: var(--text);
            transition: .3s;
            outline: none;
        }

        .modern-select {
            appearance: none;
        }

        .modern-input:focus,
        .modern-select:focus {
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 5px rgba(var(--primary-rgb), .08);
        }

        .modern-input:focus~.icon,
        .modern-select:focus~.icon {
            color: var(--primary);
        }

        .select-arrow {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .hostinger-card {
            background: linear-gradient(180deg, #ffffff, #faf5ff);
            border: 1px solid var(--primary-soft);
        }

        .security-box {
            margin-top: 18px;
            padding: 18px;
            border-radius: 18px;
            background: #fff;
            border: 1px solid var(--primary-soft);
        }

        .security-box h6 {
            margin: 0 0 14px;
            font-size: 13px;
            color: var(--primary-purple);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .security-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #94a3b8;
            transition: .3s;
        }

        .requirement i {
            font-size: 14px;
        }

        .requirement.valid {
            color: var(--success);
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 16px;
        }

        .info-alert {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e40af;
            border-radius: 18px;
            padding: 18px;
            display: flex;
            gap: 14px;
            margin-top: 20px;
        }

        .info-alert i {
            font-size: 20px;
            margin-top: 2px;
        }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 30px;
            margin-top: 30px;
            border-top: 1px solid #edf2f7;
            flex-wrap: wrap;
            gap: 16px;
        }

        .btn-group {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
        }

        .btn-modern {
            height: 56px;
            padding: 0 28px;
            border: none;
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: .3s;
            text-decoration: none;
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .btn-primary-modern {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: #fff;
            box-shadow: 0 15px 35px rgba(var(--primary-rgb), .25);
        }

        .btn-primary-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(var(--primary-rgb), .35);
        }

        .error-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            padding: 20px;
            border-radius: 20px;
            margin-bottom: 25px;
        }

        .error-box ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .error-box li {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #b91c1c;
            font-size: 14px;
            font-weight: 600;
        }

        @media(max-width:992px) {
            .form-layout {
                grid-template-columns: 1fr;
            }

            .card-body {
                padding: 20px;
            }

            .section-card {
                padding: 22px;
            }
        }

        @media(max-width:576px) {
            .top-header {
                align-items: flex-start;
            }

            .header-left h2 {
                font-size: 26px;
            }

            .security-grid {
                grid-template-columns: 1fr;
            }

            .action-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-group {
                width: 100%;
            }

            .btn-modern {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <div class="user-page">
        <div class="user-wrapper">

            <div class="top-header">
                <div class="header-left">
                    <h2>Create New User</h2>
                    <p>Create CRM users with secure Hostinger email integration and role permissions.</p>
                </div>


            </div>

            <div class="main-card">
                <div class="card-body">

                    @if ($errors->any())
                        <div class="error-box">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ $error }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('crm.users.store') }}" method="POST" id="userForm" autocomplete="off">
                        @csrf

                        <input type="hidden" name="email" id="sync_email">
                        <input type="hidden" name="password" id="sync_password">

                        <div class="form-layout">

                            <!-- LEFT SIDE -->
                            <div class="section-card">

                                <div class="section-title">
                                    <div class="section-icon">
                                        <i class="fas fa-user"></i>
                                    </div>

                                    <div>
                                        <h4>User Information</h4>
                                        <p>Basic account and access details</p>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        Full Name <span class="required">*</span>
                                    </label>

                                    <div class="input-group-modern">
                                        <input type="text" name="name" class="modern-input" placeholder="Enter full name"
                                            required>

                                        <i class="fas fa-user icon"></i>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        User Role <span class="required">*</span>
                                    </label>

                                    <div class="input-group-modern">
                                        <select name="role" id="roleSelect" class="modern-select">
                                            @if(Auth::guard('crm')->user()->isAdmin())
                                                <option value="sales">Sales Agent</option>
                                                <option value="sales_manager">Sales Manager</option>
                                                <option value="designer">Designer</option>
                                                <option value="prepress">Prepress</option>
                                                <option value="retention">Customer Retention Team</option>
                                                <option value="qc">QC Inspector</option>
                                                <option value="shipping">Shipping Agent</option>
                                                <option value="estimator">Estimator</option>
                                                <option value="team_lead">Team Lead</option>
                                                <option value="production_manager">Production Manager</option>
                                                <option value="press_operator">Press Operator</option>
                                                <option value="finishing_operator">Finishing Operator</option>
                                                <option value="warehouse">Warehouse Officer</option>
                                                <option value="accounts">Accounts Officer</option>
                                                <option value="admin">Administrator</option>
                                                @if(Auth::guard('crm')->user()->isSuperAdmin())
                                                    <option value="super_admin">Owner</option>
                                                @endif
                                            @else
                                                <option value="sales">Sales Agent</option>
                                            @endif
                                        </select>

                                        <i class="fas fa-user-shield icon"></i>
                                        <i class="fas fa-chevron-down select-arrow"></i>
                                    </div>
                                </div>

                                @if(Auth::guard('crm')->user()->isAdmin())
                                <div class="form-group" id="facilityField" style="display:none;">
                                    <label class="form-label">
                                        Production Facility <span class="required">*</span>
                                    </label>
                                    <div class="input-group-modern">
                                        <select name="production_facility_id" id="facilitySelect" class="modern-select">
                                            <option value="">Select production facility</option>
                                            @foreach($facilities as $facility)
                                                <option value="{{ $facility->id }}" {{ old('production_facility_id') == $facility->id ? 'selected' : '' }}>
                                                    {{ $facility->city }}, {{ $facility->country }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <i class="fas fa-industry icon"></i>
                                        <i class="fas fa-chevron-down select-arrow"></i>
                                    </div>
                                    <small style="display:block;margin-top:7px;color:#64748b;">This user will only be assignable to jobs from this facility.</small>
                                </div>
                                @endif

                                <div class="form-group">
                                    <label class="form-label">
                                        Allowed IP Address
                                    </label>

                                    <div class="input-group-modern">
                                        <input type="text" name="allowed_ip" class="modern-input" placeholder="127.0.0.1">

                                        <i class="fas fa-network-wired icon"></i>
                                    </div>
                                </div>

                                <div class="info-alert">
                                    <i class="fas fa-shield-check"></i>

                                    <div>
                                        <strong>Security Notice</strong>
                                        <div style="margin-top:4px;font-size:13px;line-height:1.6;">
                                            Hostinger email credentials are verified securely before creating the CRM user
                                            account.
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- RIGHT SIDE -->
                            <div class="section-card hostinger-card">

                                <div class="section-title">
                                    <div class="section-icon">
                                        <i class="fas fa-envelope-open-text"></i>
                                    </div>

                                    <div>
                                        <h4>Hostinger Integration</h4>
                                        <p>Connect mailbox and IMAP credentials</p>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        Hostinger Email <span class="required" id="email_req_star" style="display:none;">*</span>
                                    </label>

                                    <div class="input-group-modern">
                                        <input type="email" name="email_user" id="email_user" class="modern-input"
                                            placeholder="agent@yourdomain.com"
                                            oninput="document.getElementById('sync_email').value = this.value">

                                        <i class="fas fa-at icon"></i>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">
                                        Hostinger Password <span class="required" id="pass_req_star" style="display:none;">*</span>
                                    </label>

                                    <div class="input-group-modern">
                                        <input type="password" name="email_pass" id="email_pass" class="modern-input"
                                            placeholder="Enter email password" autocomplete="new-password"
                                            oninput="syncPassword(this.value)">

                                        <i class="fas fa-lock icon"></i>

                                        <button type="button" class="toggle-password"
                                            onclick="togglePassword('email_pass', this)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>

                                    <div class="security-box">
                                        <h6>Password Requirements</h6>

                                        <div class="security-grid">
                                            <div class="requirement" id="req-length">
                                                <i class="fas fa-circle"></i>
                                                Minimum 8 Characters
                                            </div>

                                            <div class="requirement" id="req-upper">
                                                <i class="fas fa-circle"></i>
                                                Uppercase Letter
                                            </div>

                                            <div class="requirement" id="req-lower">
                                                <i class="fas fa-circle"></i>
                                                Lowercase Letter
                                            </div>

                                            <div class="requirement" id="req-number">
                                                <i class="fas fa-circle"></i>
                                                Numeric Value
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Hidden Fields -->
                                <input type="hidden" name="imap_host" value="imap.hostinger.com">
                                <input type="hidden" name="imap_port" value="993">
                                <input type="hidden" name="smtp_host" value="smtp.hostinger.com">
                                <input type="hidden" name="smtp_port" value="587">

                            </div>

                        </div>

                        <div class="action-bar">

                            <div style="color:#64748b;font-size:13px;font-weight:600;">
                                All credentials are verified before account creation.
                            </div>

                            <div class="btn-group">

                                <a href="{{ route('crm.users.index') }}" class="btn-modern btn-cancel">
                                    <i class="fas fa-arrow-left"></i>
                                    Cancel
                                </a>

                                <button type="submit" class="btn-modern btn-primary-modern" id="submitBtn">
                                    <i class="fas fa-user-plus"></i>
                                    Create User Account
                                </button>

                            </div>

                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>

@endsection

@section('scripts')

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
            background: '#ffffff',
            color: '#0f172a',
            customClass: {
                popup: 'animated-toast'
            }
        });

        function togglePassword(id, btn) {

            const input = document.getElementById(id);
            const icon = btn.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        function syncPassword(value) {

            document.getElementById('sync_password').value = value;

            updateRequirement('req-length', value.length >= 8);
            updateRequirement('req-upper', /[A-Z]/.test(value));
            updateRequirement('req-lower', /[a-z]/.test(value));
            updateRequirement('req-number', /[0-9]/.test(value));
        }

        function updateRequirement(id, valid) {

            const el = document.getElementById(id);

            if (valid) {
                el.classList.add('valid');
                el.querySelector('i').className = 'fas fa-check-circle';
            } else {
                el.classList.remove('valid');
                el.querySelector('i').className = 'fas fa-circle';
            }
        }

        let isVerified = false;

        document.getElementById('userForm').addEventListener('submit', async function (e) {

            const roleSelect = document.querySelector('select[name="role"]');
            const selectedRole = roleSelect ? roleSelect.value : document.querySelector('input[name="role"]').value;
            
            const isSalesRole = selectedRole === 'sales' || selectedRole === 'sales_manager';
            const emailUser = document.getElementById('email_user').value;
            const emailPass = document.getElementById('email_pass').value;

            if (isSalesRole) {
                if (!emailUser || !emailPass) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Credentials Required',
                        text: 'Hostinger Email and Password are required for Sales Agents and Sales Managers.',
                        confirmButtonColor: 'var(--primary-purple)'
                    });
                    return;
                }
            } else {
                // If not sales role, we don't need to verify hostinger
                return; // Let it submit normally
            }

            if (isVerified) {
                return;
            }

            e.preventDefault();

            const form = this;
            const btn = document.getElementById('submitBtn');

            const originalHtml = btn.innerHTML;

            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying Credentials...';

            const payload = {
                email_user: emailUser,
                email_pass: emailPass,
                imap_host: document.querySelector('input[name="imap_host"]').value,
                imap_port: document.querySelector('input[name="imap_port"]').value
            };

            try {

                const response = await fetch('{{ route("crm.users.test_connection") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.success) {

                    Toast.fire({
                        icon: 'success',
                        title: 'Mailbox authenticated successfully'
                    });

                    isVerified = true;

                    setTimeout(() => {
                        form.submit();
                    }, 600);

                } else {

                    Swal.fire({
                        icon: 'error',
                        title: 'Authentication Failed',
                        html: `
                                                <div style="font-size:14px;color:#64748b;line-height:1.7;">
                                                  Authentication failed. Please check your credentials
                                                </div>
                                            `,
                        confirmButtonText: 'Try Again',
                        confirmButtonColor: 'var(--primary-purple)',
                        borderRadius: '20px'
                    });

                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                }

            } catch (error) {

                Toast.fire({
                    icon: 'error',
                    title: 'Connection error. Please try again.'
                });

                btn.disabled = false;
                btn.innerHTML = originalHtml;
            }

        });

        document.addEventListener('DOMContentLoaded', function () {

            const email = document.getElementById('email_user');
            const password = document.getElementById('email_pass');
            const roleSelect = document.querySelector('select[name="role"]');

            if (email.value) {
                document.getElementById('sync_email').value = email.value;
            }

            if (password.value) {
                document.getElementById('sync_password').value = password.value;
            }

            function updateStars() {
                const role = roleSelect ? roleSelect.value : document.querySelector('input[name="role"]').value;
                const showStar = (role === 'sales' || role === 'sales_manager');
                document.getElementById('email_req_star').style.display = showStar ? 'inline' : 'none';
                document.getElementById('pass_req_star').style.display = showStar ? 'inline' : 'none';
            }

            function updateFacilityField() {
                const facilityField = document.getElementById('facilityField');
                const facilitySelect = document.getElementById('facilitySelect');
                if (!facilityField || !facilitySelect || !roleSelect) return;

                const facilityRoles = ['production_manager', 'press_operator', 'finishing_operator', 'qc', 'warehouse'];
                const requiresFacility = facilityRoles.includes(roleSelect.value);
                facilityField.style.display = requiresFacility ? 'block' : 'none';
                facilitySelect.required = requiresFacility;
                if (!requiresFacility) facilitySelect.value = '';
            }

            if (roleSelect) {
                roleSelect.addEventListener('change', updateStars);
                roleSelect.addEventListener('change', updateFacilityField);
            }
            updateStars();
            updateFacilityField();

        });

    </script>

@endsection
