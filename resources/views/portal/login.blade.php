<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes" />
    <title>Secure Customer Portal Login</title>

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
            --primary: {{ $portalBrand['primary'] }};
            --primary-dark: {{ $portalBrand['primary_dark'] }};
            --primary-soft: {{ $portalBrand['primary_soft'] }};
            --primary-ring: {{ $portalBrand['primary_ring'] }};
            --text-main: #111827;
            --text-gray: #4b5563;
            --muted: #6b7280;
            --glass-border: rgba(255, 255, 255, 0.45);
            --glass-bg: rgba(255, 255, 255, 0.2);
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: {{ $portalBrand['page_bg'] }};
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;   /* allow scroll on tiny screens but smooth */
        }

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
            background: var(--primary-ring);
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
            background: var(--primary-ring);
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
            max-width: 460px;
            margin: 30px 20px;
            padding: 40px 40px 42px;
            border-radius: 38px;
            background: rgba(255, 255, 255, 0.22);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border: 1px solid var(--glass-border);
            box-shadow: 0 20px 42px rgba(0, 0, 0, 0.05), 0 2px 4px rgba(0, 0, 0, 0.02), inset 0 1px 0 rgba(255,255,255,0.5);
            transition: box-shadow 0.2s ease, transform 0.2s ease;
            position: relative;
            z-index: 10;
        }

        .login-card:hover {
            box-shadow: 0 26px 48px rgba(0, 0, 0, 0.08);
        }

        .portal-mark {
            text-align: center;
            margin: 0 auto 24px;
            width: 76px;
            height: 76px;
            border-radius: 24px;
            display: grid;
            place-items: center;
            color: #fff;
            font-size: 1.75rem;
            background: linear-gradient(145deg, var(--primary), var(--primary-dark));
            box-shadow: 0 18px 34px -16px var(--primary-ring);
        }

        /* heading */
        .welcome {
            text-align: center;
            margin-bottom: 34px;
        }

        .welcome h1 {
            font-size: 2.1rem;
            font-weight: 600;
            color: #374151;
            letter-spacing: -0.02em;
            margin-bottom: 8px;
        }

        .welcome p {
            color: var(--muted);
            font-size: 0.96rem;
            font-weight: 400;
        }

        /* form groups */
        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            color: #4b5563;
            letter-spacing: -0.2px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            left: 18px;
            transform: translateY(-50%);
            color: var(--primary-dark);
            font-size: 1rem;
            transition: color 0.2s;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            height: 56px;
            border-radius: 24px;
            border: 1px solid rgba(200, 210, 180, 0.5);
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(4px);
            padding: 0 18px 0 50px;
            font-size: 0.95rem;
            font-weight: 400;
            color: #374151;
            outline: none;
            transition: all 0.2s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            font-family: 'Inter', sans-serif;
        }

        .form-control:focus {
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.85);
            box-shadow: 0 0 0 4px var(--primary-ring);
        }

        .form-control::placeholder {
            color: #a0a98d;
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
            color: var(--primary-dark);
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
            color: #3f4a2e;
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
            border-radius: 32px;
            background: linear-gradient(105deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            font-size: 1rem;
            font-weight: 700;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.25s ease;
            box-shadow: 0 12px 24px -10px var(--primary-ring);
            letter-spacing: 0.3px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            background: linear-gradient(105deg, var(--primary-dark) 0%, var(--primary) 100%);
            box-shadow: 0 18px 28px -12px var(--primary-ring);
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
            color: var(--primary-dark);
            font-size: 0.78rem;
            font-weight: 500;
            letter-spacing: 0.2px;
        }

        /* small devices - buttery */
        @media (max-width: 540px) {
            .login-card {
                padding: 32px 24px 36px;
                margin: 20px;
                border-radius: 32px;
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

        /* optional remove default number spinners / extra improvements */
        input:-webkit-autofill,
        input:-webkit-autofill:focus {
            transition: background-color 0s 600000s, color 0s 600000s;
        }

    </style>
</head>
<body>

    <!-- Dynamic buttery background layers -->
    <div class="bg-blur bg1"></div>
    <div class="bg-blur bg2"></div>
    <div class="bg-blur bg3"></div>

    <div class="login-card">
        <div class="portal-mark" aria-hidden="true">
            <i class="fa-solid fa-shield-halved"></i>
        </div>

        <div class="welcome">
            <h1>Customer Portal</h1>
            <p>Sign in with the email and password provided with your order.</p>
        </div>

        <div id="dynamicErrorContainer" @if(session('info') || $errors->any()) style="display: flex;" @else style="display: none;" @endif class="alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span id="errorMsgSpan">
                @if(session('info')) {{ session('info') }} @endif
                @if($errors->any()) {{ $errors->first() }} @endif
            </span>
        </div>

        <form action="{{ route('portal.do_login') }}" method="POST" id="loginForm">
            <!-- CSRF token - Laravel style, it will be replaced if blade, but raw html works -->
            <input type="hidden" name="_token" value="{{ csrf_token() }}" id="csrfTokenField">
            <!-- if csrf_token not available in static environment it's fine; but the design remains same -->
            
            <!-- Email Field -->
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-envelope input-icon"></i>
                    <input type="email" name="email" id="emailInput" class="form-control" 
                           placeholder="you@example.com" required autocomplete="email"
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
            <i class="fa-solid fa-lock"></i> Secure access · Your workspace is selected automatically
        </div>
    </div>

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
            
            // One more thing: if page has pre-existing error from blade (php side), we let it be. It's fine.
            // Additionally we add a small fix: if the logo image fails to load, our fallback text appears gracefully.
            const logoImg = document.getElementById('brandLogo');
            if (logoImg && logoImg.complete && logoImg.naturalWidth === 0) {
                logoImg.style.display = 'none';
                const fallbackDiv = document.getElementById('fallbackTextLogo');
                if (fallbackDiv) fallbackDiv.style.display = 'block';
            } else if (logoImg) {
                logoImg.addEventListener('error', function() {
                    this.style.display = 'none';
                    const fallbackDiv = document.getElementById('fallbackTextLogo');
                    if (fallbackDiv) fallbackDiv.style.display = 'block';
                });
            }
            
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
            console.log("✅ Glass login ready — ultra smooth interaction, no lag");
        })();
    </script>
</body>
</html>
