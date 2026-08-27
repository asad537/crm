<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select CRM Workspace</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:grid;place-items:center;background:linear-gradient(135deg,#f7f4ef 0%,#f3f6f2 100%);font-family:'DM Sans',sans-serif;color:#172033;padding:32px 20px}
        body:before{content:'';position:fixed;width:380px;height:380px;border-radius:50%;background:rgba(244,90,36,.08);filter:blur(15px);top:-190px;left:-120px;z-index:-1}body:after{content:'';position:fixed;width:360px;height:360px;border-radius:50%;background:rgba(117,198,0,.07);filter:blur(15px);right:-150px;bottom:-190px;z-index:-1}
        .wrap{width:min(920px,100%);text-align:left;background:#fff;border-radius:24px;box-shadow:0 24px 70px rgba(15,23,42,.16);overflow:hidden}
        .portal-head{position:relative;overflow:hidden;background:linear-gradient(110deg,#fff8f3 0%,#fff 52%,#f7fced 100%);color:#172033;padding:34px 42px 31px;border-bottom:1px solid #e8ebef}.portal-head:after{content:'CRM';position:absolute;right:34px;top:3px;font-size:90px;font-weight:800;letter-spacing:-.08em;color:rgba(23,32,51,.035);pointer-events:none}.eyebrow{display:flex;align-items:center;gap:8px;color:#f45a24;font-size:10px;font-weight:800;letter-spacing:.15em;text-transform:uppercase;margin-bottom:10px}.eyebrow:before{content:'';width:22px;height:3px;border-radius:99px;background:linear-gradient(90deg,#f45a24 0 50%,#75c600 50%)}h1{font-size:32px;letter-spacing:-.035em;margin:0 0 7px}.sub{color:#748095;margin:0;font-size:14px}
        .portal-body{padding:32px 42px 25px}.grid{display:grid;gap:14px}.card{--brand:#75c600;position:relative;display:block;background:#fff;border:1px solid #e3e7ed;border-radius:16px;padding:0;text-align:left;overflow:hidden;cursor:pointer;transition:border-color .2s,box-shadow .2s,transform .2s}.card.al-massa{--brand:#f45a24}.card:hover,.card:focus{outline:none;border-color:var(--brand);box-shadow:0 12px 28px rgba(15,23,42,.09);transform:translateY(-2px)}
        .brand-strip{position:absolute;inset:0 auto 0 0;width:5px;background:var(--brand)}.card-body{display:grid;grid-template-columns:175px 1fr auto;align-items:center;gap:24px;padding:19px 22px 19px 26px}.logo-box{height:100px;border-right:1px solid #edf0f4;display:flex;align-items:center;justify-content:center;padding-right:22px}.logo{display:block;max-width:150px;max-height:82px;object-fit:contain}.al-massa .logo{width:100px;height:100px}.workspace-copy{min-width:0}.name{font-weight:750;font-size:19px;margin:0 0 7px;letter-spacing:-.015em}.role{display:inline-flex;align-items:center;gap:7px;color:#748095;font-size:12px}.role:before{content:'';width:7px;height:7px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 3px #dcfce7}.btn{border:0;border-radius:10px;padding:12px 17px;background:var(--brand);color:#fff;font:700 13px 'DM Sans',sans-serif;cursor:pointer;white-space:nowrap;transition:filter .2s}.btn:after{content:'  →';font-size:16px}.btn:hover{filter:brightness(.9)}.empty{background:#f8fafc;padding:30px;border-radius:16px;color:#64748b;text-align:center}
        .logout{margin:20px 42px 25px;text-align:right}.logout button{background:none;border:0;color:#7a8598;cursor:pointer;padding:5px;font:600 12px 'DM Sans',sans-serif}.logout button:hover{color:#dc2626}
        @media(max-width:700px){body{padding:16px}.portal-head{padding:27px 24px}.portal-head:after{font-size:64px;right:17px;top:10px}.portal-body{padding:22px 18px}.card-body{grid-template-columns:92px 1fr;gap:16px;padding:16px 16px 16px 20px}.logo-box{height:72px;padding-right:15px}.logo{max-width:78px}.al-massa .logo{width:66px;height:66px}.btn{grid-column:1/-1;width:100%}.logout{margin:15px 22px 22px}h1{font-size:27px}}
    </style>
</head>
<body>
<main class="wrap">
    <header class="portal-head">
        <div class="eyebrow">Business CRM Portal</div>
        <h1>Choose your workspace</h1>
        <p class="sub">Continue with the business account you want to manage.</p>
    </header>
    <section class="portal-body">
    @if($workspaces->isEmpty())
        <div class="empty">No CRM workspace has been assigned to your account. Contact an administrator.</div>
    @else
        <div class="grid">
            @foreach($workspaces as $workspace)
                <form class="card {{ $workspace->slug === 'mybox-packaging-app' ? 'al-massa' : 'my-box' }}" method="POST" action="{{ route('crm.workspaces.activate', $workspace) }}"
                    tabindex="0" role="button" aria-label="Open {{ $workspace->name }} CRM"
                    onclick="if(event.target.tagName !== 'BUTTON') this.submit()"
                    onkeydown="if(event.key === 'Enter' || event.key === ' '){ event.preventDefault(); this.submit(); }">
                    @csrf
                    <div class="brand-strip"></div>
                    <div class="card-body">
                        <div class="logo-box">
                            @if($workspace->slug === 'mybox-packaging-app')
                                <img class="logo" src="{{ asset('al-massa-packaging-logo.png') }}" alt="Al Massa Packaging">
                            @else
                                <img class="logo" src="{{ asset('my-box-printing-logo.svg') }}" alt="My Box Printing">
                            @endif
                        </div>
                        <div class="workspace-copy">
                            <div class="name">{{ $workspace->name }}</div>
                            <div class="role">{{ $workspace->pivot->role === 'super_admin' ? 'Owner' : ucwords(str_replace('_', ' ', $workspace->pivot->role)) }} access</div>
                        </div>
                        <button class="btn" type="submit">Open workspace</button>
                    </div>
                </form>
            @endforeach
        </div>
    @endif
    </section>
    <form class="logout" method="POST" action="{{ route('crm.logout') }}">@csrf<button type="submit">Sign out</button></form>
</main>
</body>
</html>
