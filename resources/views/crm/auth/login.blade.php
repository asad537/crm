<!DOCTYPE html>
@php
    $__loginStyle = in_array(request('style'), ['violet', 'sunset'], true) ? request('style') : 'ocean';
    $__loginCopy = [
        'ocean' => [
            'kicker' => 'Unified business platform',
            'headline' => 'One secure place for every customer journey.',
            'description' => 'Move between business workspaces, manage leads and keep every team connected from one central CRM.',
            'welcome' => 'Welcome Back',
            'signin' => 'Sign in to manage your business workspace',
        ],
        'violet' => [
            'kicker' => 'Your sales command center',
            'headline' => 'Build stronger relationships. Close faster.',
            'description' => 'Bring customer conversations, team activity and pipeline decisions together in one intelligent workspace.',
            'welcome' => 'Welcome to your CRM',
            'signin' => 'Continue to your sales and customer workspace',
        ],
        'sunset' => [
            'kicker' => 'From enquiry to delivery',
            'headline' => 'Move every opportunity forward with confidence.',
            'description' => 'Connect sales, estimating, design and production so every order stays visible from first contact to completion.',
            'welcome' => 'Good to see you again',
            'signin' => 'Sign in to continue managing your workflow',
        ],
    ][$__loginStyle];
@endphp
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <title>Business CRM Portal | Secure Login</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon-square.svg') }}">

    <!-- Google Fonts + Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            /* improve text rendering & avoid flickering */
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --accent: #14b8a6;
            --text-main: #111827;
            --text-gray: #4b5563;
            --muted: #6b7280;
            --glass-border: #e7e9ee;
            --glass-bg: #ffffff;
        }
        body.theme-violet { --primary:#7c3aed; --primary-dark:#6d28d9; }
        body.theme-sunset { --primary:#f97316; --primary-dark:#ea580c; }
        body.theme-violet { background:radial-gradient(circle at 12% 12%,#f1eaff 0,transparent 27%),radial-gradient(circle at 90% 88%,#ffeaf5 0,transparent 30%),#f8f7fb; }
        body.theme-sunset { background:radial-gradient(circle at 12% 12%,#fff1e7 0,transparent 27%),radial-gradient(circle at 90% 88%,#fff7db 0,transparent 30%),#faf8f5; }
        body.theme-violet .brand-panel { background:linear-gradient(145deg,#171329 0%,#2c1b4e 58%,#4b1f55 100%); }
        body.theme-sunset .brand-panel { background:linear-gradient(145deg,#21140f 0%,#432318 58%,#64351e 100%); }
        body.theme-violet .brand-kicker,body.theme-violet .brand-feature i { color:#d8b4fe; }
        body.theme-violet .brand-kicker::before { background:#a855f7; }
        body.theme-sunset .brand-kicker,body.theme-sunset .brand-feature i { color:#fdba74; }
        body.theme-sunset .brand-kicker::before { background:#f97316; }
        body.theme-violet .btn-login { background:linear-gradient(105deg,#7c3aed,#ec4899); box-shadow:0 14px 26px -12px rgba(124,58,237,.52); }
        body.theme-sunset .btn-login { background:linear-gradient(105deg,#f97316,#f59e0b); box-shadow:0 14px 26px -12px rgba(249,115,22,.5); }
        body.theme-violet .login-kicker { background:#f5f3ff;color:#7c3aed; }
        body.theme-sunset .login-kicker { background:#fff7ed;color:#ea580c; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: radial-gradient(circle at 12% 12%, #e5efff 0, transparent 29%), radial-gradient(circle at 90% 88%, #dffbf7 0, transparent 31%), #f7f9fc;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;   /* allow scroll on tiny screens but smooth */
        }

        .auth-shell {
            width: min(1040px, calc(100% - 40px));
            min-height: 630px;
            display: grid;
            grid-template-columns: .92fr 1.08fr;
            border: 1px solid #e4e8ef;
            border-radius: 28px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 30px 90px rgba(15,23,42,.16);
            position: relative;
            z-index: 10;
        }

        .brand-panel {
            position: relative;
            overflow: hidden;
            padding: 52px 46px;
            color: #fff;
            background: linear-gradient(145deg,#101a31 0%,#172f53 59%,#123f58 100%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .brand-panel::before { content:''; position:absolute; width:310px; height:310px; border-radius:50%; background:rgba(20,184,166,.17); right:-135px; top:-110px; }
        .brand-panel::after { content:''; position:absolute; width:220px; height:220px; border:1px solid rgba(255,255,255,.11); border-radius:52px; transform:rotate(34deg); left:-100px; bottom:-125px; }
        .industry-flow { position:absolute; inset:0; pointer-events:none; opacity:.12; z-index:0; }
        .flow-line { position:absolute; height:1px; transform-origin:left center; background:linear-gradient(90deg,transparent,#2dd4bf,transparent); }
        .flow-line.l1 { width:180px; right:12px; top:46%; transform:rotate(-24deg); }
        .flow-line.l2 { width:210px; left:45px; bottom:29%; transform:rotate(17deg); }
        .flow-line.l3 { width:155px; right:35px; bottom:17%; transform:rotate(-34deg); }
        .flow-node { position:absolute; width:46px; height:46px; border:1px solid rgba(45,212,191,.55); border-radius:14px; display:grid; place-items:center; color:#99f6e4; background:rgba(20,184,166,.14); font-size:1.05rem; box-shadow:0 0 0 7px rgba(20,184,166,.045); }
        .flow-node.customer { right:34px; top:38%; }
        .flow-node.box { right:135px; top:55%; }
        .flow-node.print { left:38px; bottom:25%; }
        .flow-node.growth { right:42px; bottom:11%; }
        .flow-node.workflow { left:44px; top:39%; }
        .brand-logo-card { position:relative; z-index:1; display:inline-flex; width:290px; padding:12px 16px; border-radius:15px; background:#fff; box-shadow:0 14px 30px rgba(0,0,0,.16); }
        .brand-logo-card img { width:100%; height:auto; display:block; }
        .brand-message { position:relative; z-index:1; margin-top:52px; }
        .brand-kicker { display:inline-flex; align-items:center; gap:8px; color:#5eead4; font-size:.7rem; font-weight:800; letter-spacing:.16em; text-transform:uppercase; }
        .brand-kicker::before { content:''; width:22px; height:2px; background:#14b8a6; }
        .brand-message h2 { margin:17px 0 14px; max-width:350px; font-size:2.15rem; line-height:1.15; letter-spacing:-.04em; }
        .brand-message p { max-width:350px; margin:0; color:#b9c8dc; font-size:.88rem; line-height:1.7; }
        .brand-features { position:relative; z-index:1; display:grid; gap:10px; margin-top:35px; }
        .brand-feature { display:flex; align-items:center; gap:11px; color:#dbe6f5; font-size:.78rem; font-weight:600; }
        .brand-feature i { width:29px; height:29px; border-radius:9px; display:grid; place-items:center; color:#5eead4; background:rgba(20,184,166,.13); border:1px solid rgba(94,234,212,.2); }
        .brand-foot { position:relative; z-index:1; display:flex; align-items:center; gap:7px; color:#8193ac; font-size:.66rem; font-weight:600; }

        /* ========= SMOOTH BACKGROUND BLOBS - GPU ACCELERATED ========= */
        .bg-blur {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            will-change: transform;
            pointer-events: none;
            z-index: 0;
            opacity: 0.5;
        }

        .bg1 {
            width: 380px;
            height: 380px;
            background: rgba(37, 99, 235, 0.16);
            top: -120px;
            left: -100px;
            animation: slowFloat 14s infinite alternate ease-in-out;
        }

        .bg2 {
            width: 340px;
            height: 340px;
            background: rgba(0, 0, 0, 0.04);
            bottom: -90px;
            right: -80px;
            animation: slowFloat 12s infinite alternate ease-in-out reverse;
            animation-delay: -2s;
        }

        .bg3 {
            width: 280px;
            height: 280px;
            background: rgba(24, 32, 51, 0.08);
            top: 40%;
            left: 70%;
            animation: slowFloat 18s infinite alternate;
            opacity: 0.35;
            filter: blur(95px);
        }

        @keyframes slowFloat {
            0% {
                transform: translate(0px, 0px) scale(1);
            }
            100% {
                transform: translate(25px, -25px) scale(1.07);
            }
        }

        /* main glass card - cleaner, lighter, hardware acceleration */
        .login-card {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 55px 64px 38px;
            border-radius: 0;
            background: #fff;
            backdrop-filter: none;
            border: 0;
            box-shadow: none;
            transition: box-shadow 0.2s ease, transform 0.2s ease;
            position: relative;
            z-index: 10;
        }
        .login-card > *:not(.login-visuals) { position:relative; z-index:2; }
        .login-visuals { position:absolute; inset:0; overflow:hidden; pointer-events:none; z-index:0; }
        .login-visuals::before { content:''; position:absolute; width:360px; height:360px; right:-185px; bottom:-175px; border-radius:50%; border:1px solid rgba(37,99,235,.13); box-shadow:0 0 0 38px rgba(20,184,166,.025),0 0 0 76px rgba(37,99,235,.02); }
        .login-flow-line { position:absolute; height:1px; background:linear-gradient(90deg,transparent,rgba(37,99,235,.2),rgba(20,184,166,.2),transparent); transform-origin:left center; }
        .login-flow-line.a { width:240px; right:-25px; top:27%; transform:rotate(28deg); }
        .login-flow-line.b { width:250px; right:10px; bottom:29%; transform:rotate(-22deg); }
        .login-flow-line.c { width:175px; left:18px; bottom:13%; transform:rotate(11deg); }
        .login-flow-icon { position:absolute; width:54px; height:54px; display:grid; place-items:center; border-radius:16px; color:#2563eb; background:linear-gradient(145deg,rgba(239,246,255,.9),rgba(236,253,250,.78)); border:1px solid rgba(37,99,235,.12); box-shadow:0 10px 25px rgba(20,184,166,.06); font-size:1.08rem; opacity:.28; }
        .login-flow-icon.sales { right:24px; top:14%; }
        .login-flow-icon.clients { right:52px; top:43%; }
        .login-flow-icon.analytics { right:25px; bottom:16%; }
        .login-flow-icon.target { left:24px; bottom:5%; }

        .login-card:hover {
            box-shadow: 0 26px 48px rgba(0, 0, 0, 0.08);
        }

        /* Logo wrapper - flexible with fallback text if SVG missing (smooth) */
        .portal-brand { display:none; }
        .portal-brand img { width:100%; max-width:330px; height:auto; display:block; }
        .portal-mark { width:48px; height:48px; border-radius:14px; display:grid; place-items:center; background:linear-gradient(145deg,#172033,#29354c); color:#fff; box-shadow:0 10px 22px rgba(15,23,42,.2); position:relative; }
        .portal-mark::after { content:''; position:absolute; right:-3px; bottom:-3px; width:14px; height:14px; border-radius:5px; background:var(--primary); border:3px solid #fff; }
        .portal-mark i { font-size:1.05rem; }
        .portal-brand-copy { text-align:left; line-height:1.08; }
        .portal-brand-copy strong { display:block; color:#172033; font-size:1rem; font-weight:850; letter-spacing:.01em; }
        .portal-brand-copy span { display:block; color:#8a96a8; margin-top:5px; font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.14em; }

        /* heading */
        .welcome {
            text-align: left;
            margin-bottom: 30px;
        }

        .welcome h1 {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-main);
            letter-spacing: -0.02em;
            margin-bottom: 8px;
        }

        .welcome p {
            color: var(--muted);
            font-size: 0.96rem;
            font-weight: 400;
        }
        .login-kicker { display:inline-flex; align-items:center; gap:7px; padding:6px 9px; margin-bottom:16px; border-radius:8px; background:#eef7fc; color:#167fc1; font-size:.65rem; font-weight:800; text-transform:uppercase; letter-spacing:.1em; }

        /* form groups */
        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            color: #344054;
            letter-spacing: -0.2px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            border-radius: 9px;
            display: grid;
            place-items: center;
            color: #2563eb;
            background: linear-gradient(145deg,#eff6ff,#ecfdf5);
            border: 1px solid rgba(37,99,235,.13);
            font-size: .9rem;
            transition: color .2s, border-color .2s, background .2s;
            pointer-events: none;
            z-index: 2;
        }

        .input-wrapper:focus-within .input-icon {
            color: #0f9f91;
            border-color: rgba(20,184,166,.3);
            background: #effcf9;
        }

        .form-control {
            width: 100%;
            height: 56px;
            border-radius: 12px;
            border: 1px solid #dfe3ea;
            background: #fbfcfd;
            backdrop-filter: blur(4px);
            padding: 0 18px 0 56px;
            font-size: 0.95rem;
            font-weight: 500;
            color: #172033;
            outline: none;
            transition: all 0.2s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            font-family: 'Inter', monospace;
        }

        .form-control:focus {
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.85);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--primary) 13%, transparent);
        }

        .form-control::placeholder {
            color: #a7b0bf;
            font-weight: 400;
        }

        /* password actions */
        .password-actions {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            gap: 12px;
            color: #98a2b3;
            z-index: 2;
        }

        .password-actions i {
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.2s, transform 0.1s;
        }

        .password-actions i:hover {
            color: var(--primary-dark);
        }

        /* remember me row */
        .remember {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 28px;
            color: #596579;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .remember input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        /* smooth button */
        .btn-login {
            width: 100%;
            height: 58px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(110deg, #2563eb 0%, #168ac5 52%, #14b8a6 100%);
            color: white;
            font-size: 1rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 14px 28px -12px rgba(37,99,235,.55), inset 0 1px 0 rgba(255,255,255,.22);
            letter-spacing: 0.3px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            background: linear-gradient(110deg, #1d4ed8 0%, #147eaf 52%, #0f9f91 100%);
            box-shadow: 0 18px 30px -12px rgba(37,99,235,.62), 0 0 0 3px rgba(20,184,166,.09);
        }

        .btn-login:active {
            transform: translateY(1px);
            transition: 0.05s;
        }

        /* error alert soft */
        .alert-error {
            margin-bottom: 24px;
            padding: 14px 20px;
            border-radius: 26px;
            background: rgba(220, 38, 38, 0.07);
            border-left: 4px solid #dc2626;
            backdrop-filter: blur(6px);
            color: #b91c1c;
            font-size: 0.88rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert-error i {
            font-size: 1.1rem;
        }

        /* footer */
        .footer {
            text-align: center;
            margin-top: 32px;
            color: #98a2b3;
            font-size: 0.78rem;
            font-weight: 500;
            letter-spacing: 0.2px;
        }

        /* small devices - buttery */
        @media (max-width: 540px) {
            .auth-shell { width:calc(100% - 24px); display:block; min-height:0; margin:12px 0; border-radius:22px; }
            .brand-panel { padding:22px 24px; }
            .brand-logo-card { width:220px; }
            .brand-message,.brand-features,.brand-foot { display:none; }
            .login-card {
                padding: 32px 24px 36px;
                margin: 0;
                border-radius: 0;
            }
            .welcome h1 {
                font-size: 1.75rem;
            }
            .btn-login {
                height: 52px;
            }
            .form-control {
                height: 52px;
                font-size: 0.9rem;
            }
        }

        @media (min-width:541px) and (max-width:850px) {
            .auth-shell { grid-template-columns:.78fr 1.22fr; }
            .brand-panel { padding:42px 28px; }
            .brand-logo-card { width:235px; }
            .brand-message h2 { font-size:1.7rem; }
            .login-card { padding:52px 38px 36px; }
        }

        /* optional remove default number spinners / extra improvements */
        input:-webkit-autofill,
        input:-webkit-autofill:focus {
            transition: background-color 0s 600000s, color 0s 600000s;
        }

    </style>
</head>
<body class="theme-{{ $__loginStyle }}">

    <!-- Dynamic buttery background layers -->
    <div class="bg-blur bg1"></div>
    <div class="bg-blur bg2"></div>
    <div class="bg-blur bg3"></div>

    <main class="auth-shell">
        <section class="brand-panel" aria-label="MultiSite CRM platform overview">
            <div class="industry-flow" aria-hidden="true">
                <span class="flow-line l1"></span><span class="flow-line l2"></span><span class="flow-line l3"></span>
                <span class="flow-node workflow"><i class="fa-solid fa-diagram-project"></i></span>
                <span class="flow-node customer"><i class="fa-solid fa-user-group"></i></span>
                <span class="flow-node box"><i class="fa-solid fa-box-open"></i></span>
                <span class="flow-node print"><i class="fa-solid fa-print"></i></span>
                <span class="flow-node growth"><i class="fa-solid fa-arrow-trend-up"></i></span>
            </div>
            <div>
                <div class="brand-logo-card">
                    <img src="{{ url('brand-assets/multisite-crm-logo.svg') }}" alt="MultiSite CRM">
                </div>
                <div class="brand-message">
                    <span class="brand-kicker">{{ $__loginCopy['kicker'] }}</span>
                    <h2>{{ $__loginCopy['headline'] }}</h2>
                    <p>{{ $__loginCopy['description'] }}</p>
                </div>
                <div class="brand-features">
                    <div class="brand-feature"><i class="fa-solid fa-layer-group"></i><span>Multiple workspaces, one account</span></div>
                    <div class="brand-feature"><i class="fa-solid fa-chart-line"></i><span>Live sales and operations visibility</span></div>
                    <div class="brand-feature"><i class="fa-solid fa-shield-halved"></i><span>Role-based secure access</span></div>
                </div>
            </div>
            <div class="brand-foot"><i class="fa-solid fa-lock"></i> Protected business environment</div>
        </section>

    <div class="login-card">
        <div class="login-visuals" aria-hidden="true">
            <span class="login-flow-line a"></span><span class="login-flow-line b"></span><span class="login-flow-line c"></span>
            <span class="login-flow-icon sales"><i class="fa-solid fa-sack-dollar"></i></span>
            <span class="login-flow-icon clients"><i class="fa-solid fa-users-viewfinder"></i></span>
            <span class="login-flow-icon analytics"><i class="fa-solid fa-chart-column"></i></span>
            <span class="login-flow-icon target"><i class="fa-solid fa-bullseye"></i></span>
        </div>
        <div class="portal-brand">
            <img src="{{ url('brand-assets/multisite-crm-logo.svg') }}" alt="MultiSite CRM — Connect, Manage, Grow">
        </div>

        <div class="welcome">
            <span class="login-kicker"><i class="fa-solid fa-circle" style="font-size:5px"></i> Secure sign in</span>
            <h1>{{ $__loginCopy['welcome'] }}</h1>
            <p>{{ $__loginCopy['signin'] }}</p>
        </div>

        <!-- Dynamic error message from backend / frontend preview demo (works with session/old errors) -->
        <div id="dynamicErrorContainer" @if(session('error')) style="display: flex;" @else style="display: none;" @endif class="alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span id="errorMsgSpan">@if(session('error')) {{ session('error') }} @endif</span>
        </div>

        <!-- main form - action points to CRM route. On demo or local, we keep the route but prevent actual full page reload unwanted? Better: keep action live, but for frontend smooth testing we can handle if route not exist gracefully, but not needed. -->
        <form action="{{ route('crm.login') }}" method="POST" id="loginForm">
            <!-- CSRF token - Laravel style, it will be replaced if blade, but raw html works -->
            <input type="hidden" name="_token" value="{{ csrf_token() }}" id="csrfTokenField">
            <!-- if csrf_token not available in static environment it's fine; but the design remains same -->
            
            <!-- Email Field -->
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input type="email" name="email" id="emailInput" class="form-control" 
                           placeholder="name@company.com" required autocomplete="email"
                           value="{{ old('email') }}">
                </div>
            </div>

            <!-- Password Field with show/hide and clear -->
            <div class="form-group">
                <label class="form-label">Password</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <input type="password" name="password" id="passwordInput" class="form-control" 
                           placeholder="••••••••" required autocomplete="current-password"
                           style="padding-right: 80px;">
                    <div class="password-actions">
                        <i class="fa-solid fa-xmark" id="clearPasswordIcon" style="display: none; cursor: pointer;"></i>
                        <i class="fa-solid fa-eye" id="togglePasswordIcon" style="cursor: pointer;"></i>
                    </div>
                </div>
            </div>

            <!-- Remember me -->
            <label class="remember">
                <input type="checkbox" name="remember" id="rememberCheckbox"> 
                <span>Keep me signed in</span>
            </label>

            <!-- Login Button -->
            <button type="submit" class="btn-login" id="loginBtn">
                <span>Sign In →</span>
            </button>
        </form>

        <div class="footer">
            <i class="fa-solid fa-shield-halved" style="margin-right:5px;color:#667085"></i>
            Secure Business CRM Access
        </div>
    </div>
    </main>

    <script>
        (function() {
            // DOM elements
            const passwordField = document.getElementById('passwordInput');
            const togglePassIcon = document.getElementById('togglePasswordIcon');
            const clearPassIcon = document.getElementById('clearPasswordIcon');
            const loginBtn = document.getElementById('loginBtn');
            const loginForm = document.getElementById('loginForm');
            const emailField = document.getElementById('emailInput');
            const errorContainer = document.getElementById('dynamicErrorContainer');
            const errorMsgSpan = document.getElementById('errorMsgSpan');
            
            // ----- Helper: show error message (smooth)
            function showErrorMessage(msg) {
                if (errorContainer && errorMsgSpan) {
                    errorMsgSpan.innerText = msg;
                    errorContainer.style.display = 'flex';
                    // auto hide after 5 seconds? but not mandatory, user can see then clear on next focus/submit
                    setTimeout(() => {
                        if(errorContainer.style.display === 'flex') {
                            errorContainer.style.opacity = '0';
                            setTimeout(() => {
                                if(errorContainer.style.opacity === '0') errorContainer.style.display = 'none';
                                errorContainer.style.opacity = '';
                            }, 300);
                        }
                    }, 4800);
                } else {
                    alert(msg); // fallback
                }
            }
            
            // Hide error when user starts typing
            function hideErrorOnInput() {
                if (errorContainer && errorContainer.style.display === 'flex') {
                    errorContainer.style.opacity = '0';
                    setTimeout(() => {
                        errorContainer.style.display = 'none';
                        errorContainer.style.opacity = '';
                    }, 150);
                }
            }
            if (emailField) emailField.addEventListener('input', hideErrorOnInput);
            if (passwordField) passwordField.addEventListener('input', hideErrorOnInput);
            
            // ----- Password visibility toggle (butter smooth)
            if (togglePassIcon && passwordField) {
                togglePassIcon.addEventListener('click', function(e) {
                    e.preventDefault();
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    // Toggle eye / eye-slash icons
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                    // keep focus on field for better UX
                    passwordField.focus();
                });
            }
            
            // ----- Clear password field with nice interaction
            if (clearPassIcon && passwordField) {
                // show/hide clear button based on input content
                const updateClearVisibility = () => {
                    clearPassIcon.style.display = passwordField.value.length > 0 ? 'inline-block' : 'none';
                };
                passwordField.addEventListener('input', updateClearVisibility);
                updateClearVisibility();
                
                clearPassIcon.addEventListener('click', function(e) {
                    e.preventDefault();
                    passwordField.value = '';
                    updateClearVisibility();
                    passwordField.focus();
                    // also if password visible/hide remains same, but fine
                    // trigger input event for any listeners
                    const evt = new Event('input', { bubbles: true });
                    passwordField.dispatchEvent(evt);
                });
            }
            
            // ----- Additional smooth feedback: prevent accidental double submit, but also 
            // simulate client-side validation without backend flicker (improves perceived smoothness)
            loginForm.addEventListener('submit', function(event) {
                // Clear any previous inline error from frontend side
                let hasError = false;
                let errorText = '';
                
                const emailVal = emailField ? emailField.value.trim() : '';
                const passVal = passwordField ? passwordField.value : '';
                
                if (!emailVal) {
                    errorText = 'Email address is required';
                    hasError = true;
                } else if (!emailVal.includes('@') || !emailVal.includes('.')) {
                    errorText = 'Please enter a valid email address';
                    hasError = true;
                } else if (!passVal) {
                    errorText = 'Password cannot be empty';
                    hasError = true;
                }
                
                if (hasError) {
                    event.preventDefault();
                    showErrorMessage(errorText);
                    // also add small shake effect on card? subtle - smooth but no lag
                    const card = document.querySelector('.login-card');
                    if (card) {
                        card.style.transform = 'translateX(4px)';
                        setTimeout(() => { if(card) card.style.transform = ''; }, 80);
                        setTimeout(() => { if(card) card.style.transform = ''; }, 150);
                    }
                    return false;
                }
                
                // but we also want to ensure we keep all existing functionality: the blade if(session('error')) 
                // shows error on initial load; but here we preserve that as original design.
                // This front-end validation only improves smoothness and system speed.
                // However, to maintain "system smooth" we also ensure loading state
                const btn = loginBtn;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-pulse"></i> Signing...';
                btn.disabled = true;
                // we re-enable only after actual submit? but form will submit; in case of network issues we re-enable after timeout? 
                // but that's fine. If submit fails (because of backend, page reloads). For maximum smoothness:
                setTimeout(() => {
                    if (btn.disabled === true) {
                        // if form hasn't been submitted due to some race, reenable after 2 sec (rare)
                        btn.disabled = false;
                        btn.innerHTML = originalText;
                    }
                }, 3000);
                
                // proceed: the form will POST to route('crm.login')
                return true;
            });
            
            // Prefill from old values / remove any extra server-side errors flickering?
            // Also better cleanup: if there is a session error from blade, we avoid duplication but fine.
            // ensure that 'clear password' and 'eye' icons always work even after dynamic.
            
            // Also small note: The page uses `{{ session('error') }}` from blade – it's server side. We'll respect it.
            // On local static version we just mimic smooth glass.
            // Improve performance: Disable heavy animations on low-end devices? But all CSS transforms are GPU accelerated.
            // For extra speed, we can add will-change to card
            const loginCard = document.querySelector('.login-card');
            if (loginCard) {
                loginCard.style.willChange = 'transform';
            }
            
            // Additionally, we ensure that the blur backdrop is not overkill, but modern devices handle it.
            // For password managers compatibility, no issues.
            
            // Optional: Clear error after manual input in email/password.
            // also handle scenario where backend error display (via blade) exists and we might want to remove?
            // Since original design shows an error div using if(session('error')), we will not touch it.
            // but we can also re-hide native alert? no.
            
            // Optimize background animation smoothness (requestAnimationFrame not needed, but ensure they don't cause repaint jitter)
            // Also for better performance, reduce filter impact by setting will-change on blobs
            const blobs = document.querySelectorAll('.bg-blur');
            blobs.forEach(blob => {
                blob.style.willChange = 'transform';
            });
            
            // For toggle password accessibility (prevent accidental form submit)
            const toggleIcons = document.querySelectorAll('.password-actions i');
            toggleIcons.forEach(icon => {
                icon.addEventListener('click', (e) => e.stopPropagation());
            });
            
            // double-check that the login button loading state doesn't cause multiple submits
            let submitting = false;
            loginForm.addEventListener('submit', function(e) {
                if (submitting) {
                    e.preventDefault();
                    return false;
                }
                submitting = true;
                // Re-enable submitting after form actually sends, but if page reloads, it's ok.
                setTimeout(() => { submitting = false; }, 5000);
            });
            
            // minor adjustment: clear password field cross shows/hide on page load
            if (passwordField && clearPassIcon) {
                if (passwordField.value.length > 0) clearPassIcon.style.display = 'inline-block';
                else clearPassIcon.style.display = 'none';
            }
            
            // ensure that in case of any backend validation flash, the form remains interactive
            console.log("Business CRM login ready");
        })();
    </script>
</body>
</html>
