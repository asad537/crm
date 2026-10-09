<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | CRM</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root { --p:#6c5ce7; --p2:#5848d8; }
        * { box-sizing:border-box; } body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#eef0ff,#f8fafc); font-family:'DM Sans',sans-serif; color:#1e293b; padding:16px; }
        .card { width:100%; max-width:440px; background:#fff; border-radius:20px; box-shadow:0 24px 60px rgba(15,23,42,.12); padding:34px 30px; }
        h1 { margin:0 0 6px; font-size:1.35rem; } p.sub { margin:0 0 20px; color:#64748b; font-size:.92rem; }
        label { display:block; font-size:.8rem; font-weight:700; color:#475569; margin:12px 0 6px; }
        input { width:100%; padding:12px 14px; border:1.5px solid #dbe3ec; border-radius:11px; font:inherit; font-size:.95rem; outline:0; }
        input:focus { border-color:var(--p); box-shadow:0 0 0 3px rgba(108,92,231,.18); }
        .hint { font-size:.76rem; color:#94a3b8; margin-top:6px; }
        .btn { width:100%; margin-top:18px; padding:12px; border:0; border-radius:11px; background:var(--p); color:#fff; font:inherit; font-weight:800; cursor:pointer; } .btn:hover { background:var(--p2); }
        .msg { padding:11px 13px; border-radius:11px; font-size:.88rem; margin-bottom:14px; } .err { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; }
        a.back { display:inline-block; margin-top:16px; font-size:.86rem; color:var(--p); font-weight:700; text-decoration:none; }
    </style>
</head>
<body>
    <div class="card">
        <h1><i class="fas fa-lock" style="color:var(--p)"></i> Set a new password</h1>
        <p class="sub">Choose a strong password for your CRM account.</p>
        @if(session('error'))<div class="msg err"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>@endif
        @if($errors->any())<div class="msg err"><i class="fas fa-exclamation-circle"></i> {{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('crm.password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" value="{{ old('email', $email) }}" required>
            <label for="password">New password</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">
            <div class="hint">At least 8 characters with uppercase, lowercase, a number and a special character.</div>
            <label for="password_confirmation">Confirm new password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
            <button class="btn" type="submit"><i class="fas fa-check"></i> Reset password</button>
        </form>
        <a class="back" href="{{ route('crm.login') }}"><i class="fas fa-arrow-left"></i> Back to sign in</a>
    </div>
</body>
</html>
