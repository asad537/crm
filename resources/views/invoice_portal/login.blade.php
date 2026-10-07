@extends('invoice_portal.layout')
@section('title', 'Customer Login')
@section('styles')
.auth{min-height:calc(100vh - 6rem);display:flex;align-items:center;justify-content:center}
.auth-card{width:100%;max-width:420px;padding:2rem 2rem 1.6rem}
.auth-card img{height:52px;display:block;margin:0 auto 1.2rem}
.auth-card h1{margin:0 0 .25rem;font-size:1.25rem;text-align:center}
.auth-card p.sub{margin:0 0 1.4rem;text-align:center;color:var(--mut);font-size:.86rem}
.f{display:flex;flex-direction:column;gap:.3rem;margin-bottom:.9rem}
.f label{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#7c8aa0}
.f input{height:44px;padding:0 .85rem;border:1px solid #d6dde6;border-radius:9px;font:inherit;font-size:.95rem;outline:0}
.f input:focus{border-color:var(--p);box-shadow:0 0 0 3px rgba(55,96,148,.15)}
.auth .btn.p{width:100%;justify-content:center;height:46px;font-size:.95rem;margin-top:.3rem}
.forgot{margin-top:1.1rem;text-align:center;font-size:.8rem;color:var(--mut)}
.forgot button{border:none;background:none;color:var(--p);font-weight:600;cursor:pointer;font:inherit;font-size:.8rem}
#forgotForm{display:none;margin-top:.9rem;padding-top:.9rem;border-top:1px dashed var(--b)}
#forgotForm.show{display:block}
@endsection
@section('content')
<div class="auth">
    <div class="card auth-card">
        <img src="{{ asset($brand['logo']) }}" alt="{{ $brand['name'] }}">
        <h1>Customer Invoice Portal</h1>
        <p class="sub">Log in with the details we emailed you to view and pay your invoices.</p>
        <form method="POST" action="{{ route('invoice_portal.do_login') }}">
            {{ csrf_field() }}
            <div class="f"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus></div>
            <div class="f"><label>Password</label><input type="password" name="password" autocomplete="current-password" required></div>
            <button class="btn p" type="submit"><i class="fas fa-sign-in-alt"></i> Log in</button>
        </form>
        <div class="forgot">
            Forgot your password? <button type="button" onclick="document.getElementById('forgotForm').classList.toggle('show')">Email me new login details</button>
            <form method="POST" action="{{ route('invoice_portal.forgot') }}" id="forgotForm">
                {{ csrf_field() }}
                <div class="f"><label>Your email</label><input type="email" name="email" value="{{ old('email') }}" required></div>
                <button class="btn" type="submit" style="width:100%;justify-content:center"><i class="fas fa-paper-plane"></i> Send new password</button>
            </form>
        </div>
    </div>
</div>
@endsection
