@extends('crm.layout')

@section('title', 'Change Password')

@section('content')
    <style>
        .cp-wrap {
            max-width: 500px;
            margin: 2rem auto;
        }

        .cp-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.07);
            overflow: hidden;
        }

        .cp-top-bar {
            height: 3px;
            background: linear-gradient(to right, var(--primary-purple), var(--primary-purple), #10b981);
        }

        .cp-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cp-icon {
            width: 44px;
            height: 44px;
            background: var(--primary-soft);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-purple);
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .cp-title {
            font-size: 1rem;
            font-weight: 800;
            color: #0f172a;
        }

        .cp-sub {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 2px;
        }

        .cp-body {
            padding: 1.75rem 2rem 2rem;
        }

        .flash-ok {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .flash-err {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.82rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-label {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 0.45rem;
        }

        .input-wrap {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 0.72rem 2.8rem 0.72rem 1rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.85rem;
            color: #0f172a;
            outline: none;
            transition: border 0.2s;
            box-sizing: border-box;
        }

        .form-input:focus {
            border-color: var(--primary-purple);
        }

        .form-input.is-error {
            border-color: #fca5a5;
        }

        .toggle-pw {
            position: absolute;
            right: 0.9rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            font-size: 0.85rem;
            padding: 0;
        }

        .toggle-pw:hover {
            color: #475569;
        }

        .field-error {
            font-size: 0.72rem;
            color: #dc2626;
            margin-top: 4px;
        }

        /* Password rules */
        .pw-rules {
            background: #fafbff;
            border: 1px solid var(--primary-soft);
            border-radius: 10px;
            padding: 0.85rem 1rem;
            margin-bottom: 1.2rem;
        }

        .pw-rules-title {
            font-size: 0.68rem;
            font-weight: 800;
            color: var(--primary-purple);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 0.6rem;
        }

        .rule {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.75rem;
            color: #94a3b8;
            margin-bottom: 4px;
            transition: color 0.2s;
        }

        .rule.ok {
            color: #059669;
        }

        .rule .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #e2e8f0;
            flex-shrink: 0;
            transition: background 0.2s;
        }

        .rule.ok .dot {
            background: #059669;
        }

        .divider {
            border: none;
            border-top: 1px solid #f1f5f9;
            margin: 1.25rem 0;
        }

        .btn-save {
            width: 100%;
            padding: 0.82rem;
            background: #1e293b;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-save:hover {
            background: #334155;
        }
    </style>

    <div class="cp-wrap">
        <div class="cp-card">
            <div class="cp-top-bar"></div>

            <div class="cp-header">
                <div class="cp-icon"><i class="fas fa-lock"></i></div>
                <div>
                    <div class="cp-title">Change Password</div>
                    <div class="cp-sub">{{ Auth::guard('crm')->user()->name }} &mdash;
                        {{ Auth::guard('crm')->user()->getRoleLabel() }}</div>
                </div>
            </div>

            <div class="cp-body">

                @if(session('success'))
                    <div class="flash-ok"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
                @endif

                @if(session('error'))
                    <div class="flash-err"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
                @endif

                <form method="POST" action="{{ route('crm.change_password.update') }}" id="cpForm">
                    @csrf

                    {{-- Current Password --}}
                    <div class="form-group">
                        <label class="form-label">Current Password</label>
                        <div class="input-wrap">
                            <input type="password" id="current_password" name="current_password"
                                class="form-input {{ $errors->has('current_password') ? 'is-error' : '' }}"
                                placeholder="Enter your current password" autocomplete="current-password">
                            <button type="button" class="toggle-pw" onclick="togglePw('current_password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @error('current_password')
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    <hr class="divider">

                    {{-- New Password --}}
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <div class="input-wrap">
                            <input type="password" id="password" name="password"
                                class="form-input {{ $errors->has('password') ? 'is-error' : '' }}"
                                placeholder="Enter new password" autocomplete="new-password"
                                oninput="checkRules(this.value)">
                            <button type="button" class="toggle-pw" onclick="togglePw('password', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="field-error"><i class="fas fa-exclamation-circle"></i> {{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Confirm Password --}}
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <div class="input-wrap">
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                class="form-input" placeholder="Re-enter new password" autocomplete="new-password">
                            <button type="button" class="toggle-pw" onclick="togglePw('password_confirmation', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Email Signature --}}
                    <div class="form-group" style="margin-top: 1.5rem; margin-bottom: 1.5rem;">
                        <label class="form-label">Email Signature</label>
                        <textarea name="signature" id="signature" rows="4">{{ Auth::guard('crm')->user()->signature }}</textarea>
                    </div>

                    {{-- Password Rules --}}
                    <div class="pw-rules" style="margin-top:0.25rem; margin-bottom:1.2rem;">
                        <div class="pw-rules-title">Password Requirements (Only if changing password)</div>
                        <div class="rule" id="rule-len"><span class="dot"></span> At least 8 characters</div>
                        <div class="rule" id="rule-upper"><span class="dot"></span> One uppercase letter (A-Z)</div>
                        <div class="rule" id="rule-lower"><span class="dot"></span> One lowercase letter (a-z)</div>
                        <div class="rule" id="rule-num"><span class="dot"></span> One number (0-9)</div>
                        <div class="rule" id="rule-special"><span class="dot"></span> One special character (!@#$...)</div>
                    </div>

                    <button type="submit" class="btn-save">
                        <i class="fas fa-save"></i> Save Profile Settings
                    </button>

                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePw(id, btn) {
            const inp = document.getElementById(id);
            const isText = inp.type === 'text';
            inp.type = isText ? 'password' : 'text';
            btn.querySelector('i').className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
        }

        function checkRules(val) {
            toggle('rule-len', val.length >= 8);
            toggle('rule-upper', /[A-Z]/.test(val));
            toggle('rule-lower', /[a-z]/.test(val));
            toggle('rule-num', /[0-9]/.test(val));
            toggle('rule-special', /[@$!%*#?&^()_\-+=\[\]{}|;:,.<>?\/\\~`]/.test(val));
        }

        function toggle(id, ok) {
            const el = document.getElementById(id);
            el.classList.toggle('ok', ok);
        }
    </script>
@endsection

@section('scripts')
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    <script>
        CKEDITOR.replace('signature', {
            height: 200,
            removeButtons: 'About'
        });
    </script>
@endsection