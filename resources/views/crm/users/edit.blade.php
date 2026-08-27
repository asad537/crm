@extends('crm.layout')

@section('title', 'Edit User')

@section('content')
    <style>
        .content-card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }


        .form-section {
            padding: 2.5rem;
        }

        .section-title {
            font-size: 1.1rem;
            color: #1e293b;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 1rem;
            font-weight: 700;
        }

        .section-title i {
            color: var(--primary-purple);
            background: #eef2ff;
            padding: 8px;
            border-radius: 8px;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: #475569;
        }

        .form-label span.optional {
            font-weight: 400;
            color: #94a3b8;
            font-size: 0.8rem;
        }

        .form-control {
            width: 100%;
            padding: 0.85rem 1.2rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.2s;
            box-sizing: border-box;
            background: #f8fafc;
            color: #1e293b;
        }

        .form-control:focus {
            border-color: var(--primary-purple);
            background: white;
            box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.1);
        }

        .input-icon-wrapper {
            position: relative;
        }

        .input-icon-wrapper i.fas {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .input-icon-wrapper .form-control {
            padding-left: 3rem;
        }

        .select-wrapper {
            position: relative;
        }

        .select-wrapper select {
            appearance: none;
            cursor: pointer;
        }

        .select-wrapper::after {
            content: '\f078';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.85rem 2rem;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-purple) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(var(--primary-rgb), 0.3);
        }

        .btn-light {
            background: white;
            border: 1px solid #cbd5e1;
            color: #475569;
        }

        .btn-light:hover {
            background: #f8fafc;
            color: #1e293b;
            border-color: #94a3b8;
        }

        .error-list {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            color: #991b1b;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin: 2rem 2.5rem 0;
            font-size: 0.95rem;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .form-section {
                padding: 1.5rem;
            }
        }
    </style>

    <div class="content-card">

        @if ($errors->any())
            <div class="error-list">
                <ul style="padding-left: 1.5rem; margin: 0;">
                    @foreach ($errors->all() as $error)
                        <li style="margin-bottom: 0.25rem;">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('crm.users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-section">
                <h3 class="section-title">
                    <i class="fas fa-user"></i> Account Details
                </h3>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-signature"></i>
                            <input type="text" name="name" required class="form-control" value="{{ $user->name }}"
                                placeholder="John Doe">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" name="email" required class="form-control" value="{{ $user->email }}"
                                placeholder="john@example.com">
                        </div>
                    </div>
                </div>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <div class="select-wrapper">
                            @if(Auth::guard('crm')->user()->isAdmin())
                                <select name="role" id="roleSelect" class="form-control">
                                    <option value="sales" {{ $workspaceRole == 'sales' ? 'selected' : '' }}>Sales Agent</option>
                                    <option value="sales_manager" {{ $workspaceRole == 'sales_manager' ? 'selected' : '' }}>Sales Manager</option>
                                    <option value="designer" {{ $workspaceRole == 'designer' ? 'selected' : '' }}>Designer</option>
                                    <option value="prepress" {{ $workspaceRole == 'prepress' ? 'selected' : '' }}>Prepress</option>
                                    <option value="retention" {{ $workspaceRole == 'retention' ? 'selected' : '' }}>Customer Retention Team</option>
                                    <option value="qc" {{ $workspaceRole == 'qc' ? 'selected' : '' }}>QC Inspector</option>
                                    <option value="shipping" {{ $workspaceRole == 'shipping' ? 'selected' : '' }}>Shipping Agent</option>
                                    <option value="estimator" {{ $workspaceRole == 'estimator' ? 'selected' : '' }}>Estimator</option>
                                    <option value="team_lead" {{ $workspaceRole == 'team_lead' ? 'selected' : '' }}>Team Lead</option>
                                    <option value="production_manager" {{ $workspaceRole == 'production_manager' ? 'selected' : '' }}>Production Manager</option>
                                    <option value="press_operator" {{ $workspaceRole == 'press_operator' ? 'selected' : '' }}>Press Operator</option>
                                    <option value="finishing_operator" {{ $workspaceRole == 'finishing_operator' ? 'selected' : '' }}>Finishing Operator</option>
                                    <option value="warehouse" {{ $workspaceRole == 'warehouse' ? 'selected' : '' }}>Warehouse Officer</option>
                                    <option value="accounts" {{ $workspaceRole == 'accounts' ? 'selected' : '' }}>Accounts Officer</option>
                                    <option value="admin" {{ $workspaceRole == 'admin' ? 'selected' : '' }}>Administrator</option>
                                    @if(Auth::guard('crm')->user()->isSuperAdmin())
                                        <option value="super_admin" {{ $workspaceRole == 'super_admin' ? 'selected' : '' }}>Owner</option>
                                    @endif
                                </select>
                            @else
                                <select name="role" class="form-control" disabled>
                                    <option value="sales" selected>Sales Agent</option>
                                </select>
                                <input type="hidden" name="role" value="sales">
                            @endif
                        </div>
                    </div>

                    @if(Auth::guard('crm')->user()->isAdmin())
                    <div class="form-group" id="facilityField" style="display:none;">
                        <label class="form-label">Production Facility</label>
                        <div class="select-wrapper">
                            <select name="production_facility_id" id="facilitySelect" class="form-control">
                                <option value="">Select production facility</option>
                                @foreach($facilities as $facility)
                                    <option value="{{ $facility->id }}" {{ old('production_facility_id', $user->production_facility_id) == $facility->id ? 'selected' : '' }}>
                                        {{ $facility->city }}, {{ $facility->country }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <small style="color:#64748b;font-size:.8rem;margin-top:.5rem;display:block;">Only jobs from this facility can be assigned to this user.</small>
                    </div>
                    @endif

                    <div class="form-group">
                        <label class="form-label">Allowed IP <span class="optional">(Optional)</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-network-wired"></i>
                            <input type="text" name="allowed_ip" class="form-control" value="{{ $user->allowed_ip }}"
                                placeholder="e.g. 192.168.1.1">
                        </div>
                        <small style="color: #64748b; font-size: 0.8rem; margin-top: 0.5rem; display: block;">Restrict login
                            to this specific IP address.</small>
                    </div>
                </div>

                <h3 class="section-title" style="margin-top: 1rem;">
                    <i class="fas fa-lock"></i> Security
                </h3>

                <div class="form-group">
                    <label class="form-label">Password <span class="optional">(Leave blank to keep current)</span></label>
                    <div class="input-icon-wrapper">
                        <i class="fas fa-key"></i>
                        <input type="password" name="password" class="form-control" placeholder="••••••••">
                    </div>
                </div>

                <h3 class="section-title" style="margin-top: 2rem;">
                    <i class="fas fa-envelope-open-text"></i> Hostinger Email Integration
                </h3>

                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Email ID <span class="optional">(Optional)</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-at"></i>
                            <input type="email" name="email_user" class="form-control" value="{{ $user->email_user }}"
                                placeholder="sales@myboxprinting.com">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">App Password <span class="optional">(Optional)</span></label>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-asterisk"></i>
                            <input type="password" name="email_pass" class="form-control" value="{{ $user->email_pass }}"
                                placeholder="••••••••">
                        </div>
                    </div>
                </div>

                <div class="grid-2"
                    style="background: #f8fafc; padding: 1.5rem; border-radius: 12px; border: 1px dashed #cbd5e1; margin-top: 0.5rem;">
                    <div>
                        <div
                            style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.05em;">
                            Incoming (IMAP)</div>
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label class="form-label" style="font-size: 0.85rem;">Host</label>
                            <input type="text" name="imap_host" class="form-control" style="padding: 0.6rem 1rem;"
                                value="{{ $user->imap_host ?: 'imap.hostinger.com' }}">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" style="font-size: 0.85rem;">Port</label>
                            <input type="text" name="imap_port" class="form-control" style="padding: 0.6rem 1rem;"
                                value="{{ $user->imap_port ?: '993' }}">
                        </div>
                    </div>

                    <div>
                        <div
                            style="font-size: 0.85rem; font-weight: 700; color: #475569; margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.05em;">
                            Outgoing (SMTP)</div>
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label class="form-label" style="font-size: 0.85rem;">Host</label>
                            <input type="text" name="smtp_host" class="form-control" style="padding: 0.6rem 1rem;"
                                value="{{ $user->smtp_host ?: 'smtp.hostinger.com' }}">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" style="font-size: 0.85rem;">Port</label>
                            <input type="text" name="smtp_port" class="form-control" style="padding: 0.6rem 1rem;"
                                value="{{ $user->smtp_port ?: '587' }}">
                        </div>
                    </div>
                </div>

                <div
                    style="display: flex; gap: 1rem; margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #f1f5f9; justify-content: flex-end;">
                    <a href="{{ route('crm.users.index') }}" class="btn btn-light">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var roleSelect = document.getElementById('roleSelect');
            var facilityField = document.getElementById('facilityField');
            var facilitySelect = document.getElementById('facilitySelect');
            if (!roleSelect || !facilityField || !facilitySelect) return;

            function updateFacilityField() {
                var facilityRoles = ['production_manager', 'press_operator', 'finishing_operator', 'qc', 'warehouse'];
                var requiresFacility = facilityRoles.indexOf(roleSelect.value) !== -1;
                facilityField.style.display = requiresFacility ? 'block' : 'none';
                facilitySelect.required = requiresFacility;
                if (!requiresFacility) facilitySelect.value = '';
            }

            roleSelect.addEventListener('change', updateFacilityField);
            updateFacilityField();
        });
    </script>
@endsection
