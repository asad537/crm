@php $brand = $brand ?? ['name' => 'The Custom Boxes', 'site' => 'www.thecustomboxes.com', 'email' => 'support@thecustomboxes.com', 'phones' => '1800-396-1840', 'logo' => 'thecustomboxes-logo.png', 'color' => '#376094']; @endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Invoice Portal') · {{ $brand['name'] }}</title>
    <link rel="icon" href="{{ asset('Favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root{--p:{{ $brand['color'] }};--ink:#0f172a;--mut:#64748b;--b:#e2e8f0;--bg:#f3f5f9;--ok:#16a34a;--bad:#dc2626}
        *{box-sizing:border-box}
        body{margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:var(--bg);color:var(--ink);font-size:14px;line-height:1.5;-webkit-font-smoothing:antialiased}
        a{color:var(--p)}
        .top{background:#fff;border-bottom:1px solid var(--b);box-shadow:0 1px 0 rgba(15,23,42,.02)}
        .top::before{content:"";display:block;height:4px;background:linear-gradient(90deg,var(--p),#7fa3d1)}
        .top-in{max-width:1180px;margin:0 auto;padding:.8rem 1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem}
        .top img{height:40px;display:block}
        .top .who{display:flex;align-items:center;gap:.9rem;font-size:.82rem;color:var(--mut)}
        .top .who strong{color:var(--ink)}
        .btn{display:inline-flex;align-items:center;gap:.45rem;padding:.6rem 1rem;border-radius:8px;border:1px solid var(--b);background:#fff;color:var(--ink);font-weight:600;font-size:.85rem;cursor:pointer;text-decoration:none;white-space:nowrap}
        .btn:hover{border-color:#b9c3cf}
        .btn.p{background:var(--p);border-color:var(--p);color:#fff}
        .btn.p:hover{filter:brightness(.94)}
        .btn.g{background:#16a34a;border-color:#16a34a;color:#fff}
        .btn.sm{padding:.45rem .75rem;font-size:.78rem}
        .btn.pp{background:#ffc439;border-color:#ffc439;color:#111}
        .wrap{max-width:1180px;margin:0 auto;padding:1.5rem 1.25rem 3rem}
        .card{background:#fff;border:1px solid var(--b);border-radius:14px;box-shadow:0 6px 24px rgba(15,23,42,.05)}
        .flash{padding:.75rem 1rem;border-radius:10px;margin-bottom:1rem;font-weight:600;font-size:.86rem;display:flex;gap:.6rem;align-items:flex-start}
        .flash.ok{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
        .flash.err{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
        .foot{text-align:center;color:#94a3b8;font-size:.76rem;padding:1rem;line-height:1.7}
        .badge{display:inline-flex;align-items:center;padding:.28rem .6rem;border-radius:999px;font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.03em}
        .badge.paid{background:#dcfce7;color:#166534}.badge.unpaid{background:#fee2e2;color:#b91c1c}.badge.partial{background:#fef3c7;color:#92400e}
        @yield('styles')
    </style>
</head>
<body>
@if(!empty($account))
<header class="top"><div class="top-in">
    <a href="{{ route('invoice_portal.invoices') }}"><img src="{{ asset($brand['logo']) }}" alt="{{ $brand['name'] }}"></a>
    <div class="who">
        <span>Welcome, <strong>{{ $account->name ?: $account->email }}</strong></span>
        <form method="POST" action="{{ route('invoice_portal.logout') }}" style="margin:0">{{ csrf_field() }}<button class="btn sm" type="submit"><i class="fas fa-sign-out-alt"></i> Log out</button></form>
    </div>
</div></header>
@endif
<main class="wrap">
    @if(session('success'))<div class="flash ok"><i class="fas fa-check-circle" style="margin-top:.15rem"></i><span>{{ session('success') }}</span></div>@endif
    @if(session('error'))<div class="flash err"><i class="fas fa-exclamation-circle" style="margin-top:.15rem"></i><span>{{ session('error') }}</span></div>@endif
    @if($errors->any())<div class="flash err"><i class="fas fa-exclamation-circle" style="margin-top:.15rem"></i><span>{{ $errors->first() }}</span></div>@endif
    @yield('content')
</main>
<div class="foot">{{ $brand['name'] }} &middot; &#9742; {{ $brand['phones'] }} &middot; &#9993; {{ $brand['email'] }} &middot; {{ $brand['site'] }}</div>
@yield('scripts')
</body>
</html>
