<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Tracking #{{ $order->id }} — {{ $portalBrand['name'] }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --brand: {{ $portalBrand['primary'] }};
            --brand-strong: {{ $portalBrand['primary_dark'] }};
            --brand-soft: {{ $portalBrand['primary_soft'] }};
            --brand-ring: {{ $portalBrand['primary_ring'] }};
            --brand-rgb: {{ $portalBrand['primary_rgb'] }};
            --ink: {{ $portalBrand['ink'] }};
            --ink-soft: {{ $portalBrand['ink_soft'] }};
            --muted: {{ $portalBrand['muted'] }};
            --line: {{ $portalBrand['line'] }};
            --line-strong: {{ $portalBrand['line_strong'] }};
            --paper: #ffffff;
            --paper-soft: {{ $portalBrand['paper_soft'] }};
            --dark: {{ $portalBrand['dark'] }};
            --dark-2: {{ $portalBrand['dark_2'] }};
            --blue: #2563eb;
            --orange: #ea580c;
            --purple: #7c3aed;
            --yellow: #a16207;
            --red: #dc2626;
            --shadow-sm: 0 8px 22px rgba(17, 27, 13, 0.07);
            --shadow-md: 0 18px 50px rgba(17, 27, 13, 0.12);
            --shadow-lg: 0 26px 70px rgba(17, 27, 13, 0.18);
            --radius-sm: 12px;
            --radius-md: 18px;
            --radius-lg: 28px;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
            font-family: 'Inter', Arial, sans-serif;
            color: var(--ink);
            background:
                radial-gradient(circle at 12% 10%, rgba(var(--brand-rgb), 0.16), transparent 32%),
                radial-gradient(circle at 88% 0%, rgba(var(--brand-rgb), 0.12), transparent 28%),
                linear-gradient(180deg, {{ $portalBrand['page_bg'] }} 0%, var(--brand-soft) 48%, #ffffff 100%);
            overflow-x: hidden;
        }

        a {
            color: inherit;
        }

        button,
        textarea {
            font: inherit;
        }

        .page-shell {
            min-height: 100vh;
        }

        .topbar {
            background: var(--dark);
            color: rgba(255, 255, 255, 0.82);
            font-size: 0.78rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .topbar-inner,
        .nav-inner,
        .container {
            width: min(1180px, calc(100% - 36px));
            margin: 0 auto;
        }

        .topbar-inner {
            min-height: 38px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }

        .topbar-left,
        .topbar-right {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .topbar-item {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            white-space: nowrap;
        }

        .topbar-item i {
            color: var(--brand);
        }

        .nav {
            position: sticky;
            top: 0;
            z-index: 20;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(18px);
            border-bottom: 1px solid var(--line);
        }

        .nav-inner {
            min-height: 74px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }

        .brand-lockup {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--ink);
            text-decoration: none;
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 15px;
            display: grid;
            place-items: center;
            color: var(--dark);
            background: linear-gradient(135deg, #a7ee54, var(--brand));
            box-shadow: 0 12px 24px rgba(var(--brand-rgb), 0.28);
            font-weight: 900;
            letter-spacing: -0.05em;
        }

        .brand-text {
            line-height: 1.05;
        }

        .brand-name {
            font-size: 1.04rem;
            font-weight: 900;
            letter-spacing: -0.03em;
        }

        .brand-name span {
            color: var(--brand);
        }

        .brand-subtitle {
            margin-top: 4px;
            color: rgba(255, 255, 255, 0.58);
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .secure-pill,
        .logout-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            height: 40px;
            padding: 0 14px;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 800;
            text-decoration: none;
            white-space: nowrap;
        }

        .secure-pill {
            color: var(--brand-strong);
            background: var(--brand-soft);
            border: 1px solid rgba(var(--brand-rgb), 0.20);
        }

        .secure-pill i {
            color: var(--brand);
        }

        .logout-link {
            color: var(--dark);
            background: var(--brand);
            border: 1px solid rgba(var(--brand-rgb), 0.4);
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .logout-link:hover {
            transform: translateY(-1px);
            background: var(--brand-strong);
            box-shadow: 0 12px 24px rgba(var(--brand-rgb), 0.22);
        }

        .container {
            padding: 34px 0 58px;
        }

        .hero {
            position: relative;
            overflow: hidden;
            border-radius: var(--radius-lg);
            padding: 28px;
            color: #ffffff;
            background:
                linear-gradient(135deg, rgba(16, 25, 12, 0.97), rgba(29, 43, 22, 0.96)),
                radial-gradient(circle at 82% 20%, rgba(var(--brand-rgb), 0.24), transparent 35%);
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.10);
        }

        .hero::before,
        .hero::after {
            content: "";
            position: absolute;
            pointer-events: none;
            border-radius: 999px;
        }

        .hero::before {
            width: 340px;
            height: 340px;
            right: -115px;
            top: -170px;
            background: rgba(var(--brand-rgb), 0.16);
            border: 1px solid rgba(var(--brand-rgb), 0.26);
        }

        .hero::after {
            width: 170px;
            height: 170px;
            right: 125px;
            bottom: -105px;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .hero-content {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 22px;
            align-items: center;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            width: fit-content;
            padding: 7px 12px;
            border-radius: 999px;
            color: #dcf9bf;
            background: rgba(var(--brand-rgb), 0.13);
            border: 1px solid rgba(var(--brand-rgb), 0.26);
            font-size: 0.74rem;
            font-weight: 900;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .hero h1 {
            margin-top: 15px;
            max-width: 720px;
            font-size: clamp(2rem, 4vw, 3.55rem);
            line-height: 0.98;
            letter-spacing: -0.065em;
            font-weight: 900;
        }

        .hero-sub {
            margin-top: 14px;
            max-width: 690px;
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.98rem;
            line-height: 1.65;
        }

        .hero-product {
            margin-top: 20px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .mini-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 38px;
            padding: 8px 12px;
            border-radius: 999px;
            color: rgba(255, 255, 255, 0.86);
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.10);
            font-size: 0.82rem;
            font-weight: 700;
        }

        .mini-chip i {
            color: var(--brand);
        }

        .status-panel {
            position: relative;
            min-width: 255px;
            padding: 18px;
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(12px);
        }

        .status-label {
            color: rgba(255, 255, 255, 0.58);
            font-size: 0.72rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .status-main {
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .status-orb {
            width: 54px;
            height: 54px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            color: var(--dark);
            background: linear-gradient(135deg, #c8ff8a, var(--brand));
            box-shadow: 0 14px 28px rgba(var(--brand-rgb), 0.28);
            font-size: 1.26rem;
        }

        .status-name {
            font-size: 1.05rem;
            font-weight: 900;
            letter-spacing: -0.02em;
        }

        .status-note {
            margin-top: 4px;
            color: rgba(255, 255, 255, 0.62);
            font-size: 0.78rem;
            line-height: 1.45;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 34px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.77rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .status-success { background: #dcfce7; color: #166534; }
        .status-blue { background: #dbeafe; color: #1d4ed8; }
        .status-orange { background: #ffedd5; color: #c2410c; }
        .status-purple { background: #f3e8ff; color: #6d28d9; }
        .status-yellow { background: #fef9c3; color: #854d0e; }
        .status-red { background: #fee2e2; color: #b91c1c; }

        .summary-row {
            margin: 20px 0 0;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .summary-tile {
            padding: 17px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.82);
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(223, 234, 214, 0.95);
        }

        .summary-label {
            display: flex;
            align-items: center;
            gap: 7px;
            color: var(--muted);
            font-size: 0.72rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .summary-label i {
            color: var(--brand-strong);
        }

        .summary-value {
            margin-top: 8px;
            color: var(--ink);
            font-size: 0.92rem;
            line-height: 1.4;
            font-weight: 800;
            word-break: break-word;
        }

        .main-grid {
            margin-top: 20px;
            display: grid;
            grid-template-columns: minmax(0, 1fr) 405px;
            gap: 20px;
            align-items: start;
        }

        .content-stack {
            display: grid;
            gap: 20px;
        }

        .card {
            position: relative;
            overflow: hidden;
            border-radius: var(--radius-md);
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(223, 234, 214, 0.98);
            box-shadow: var(--shadow-sm);
        }

        .card::before {
            content: "";
            position: absolute;
            inset: 0 0 auto;
            height: 3px;
            background: linear-gradient(90deg, var(--brand), rgba(var(--brand-rgb), 0), var(--brand));
            opacity: 0.65;
        }

        .card-header {
            min-height: 76px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            border-bottom: 1px solid var(--line);
        }

        .header-title-wrap {
            display: flex;
            align-items: center;
            gap: 13px;
            min-width: 0;
        }

        .card-header-icon {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            color: var(--brand-strong);
            background: var(--brand-soft);
            border: 1px solid rgba(var(--brand-rgb), 0.20);
            flex: 0 0 auto;
        }

        .card-title {
            color: var(--ink);
            font-size: 1rem;
            font-weight: 900;
            letter-spacing: -0.025em;
        }

        .card-subtitle {
            margin-top: 3px;
            color: var(--muted);
            font-size: 0.78rem;
            line-height: 1.4;
            font-weight: 600;
        }

        .card-body {
            padding: 22px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 13px;
        }

        .meta-item {
            padding: 14px;
            border-radius: 16px;
            background: var(--paper-soft);
            border: 1px solid var(--line);
        }

        .meta-item.wide {
            grid-column: 1 / -1;
        }

        .meta-label {
            display: flex;
            align-items: center;
            gap: 7px;
            color: var(--muted);
            font-size: 0.69rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.09em;
        }

        .meta-label i {
            color: var(--brand-strong);
        }

        .meta-value {
            margin-top: 7px;
            color: var(--ink-soft);
            font-size: 0.91rem;
            line-height: 1.5;
            font-weight: 800;
            word-break: break-word;
        }

        .tracking-box {
            margin-top: 16px;
            position: relative;
            overflow: hidden;
            padding: 18px;
            border-radius: 18px;
            background:
                linear-gradient(135deg, rgba(var(--brand-rgb), 0.16), rgba(255, 255, 255, 0.8)),
                #ffffff;
            border: 1px solid rgba(var(--brand-rgb), 0.28);
        }

        .tracking-box::after {
            content: "";
            position: absolute;
            width: 120px;
            height: 120px;
            right: -48px;
            top: -48px;
            border-radius: 50%;
            background: rgba(var(--brand-rgb), 0.18);
        }

        .tracking-top {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
        }

        .tracking-label {
            color: var(--brand-strong);
            font-size: 0.7rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.09em;
        }

        .tracking-number {
            margin-top: 6px;
            color: var(--ink);
            font-size: clamp(1rem, 2vw, 1.28rem);
            font-weight: 900;
            letter-spacing: -0.035em;
            word-break: break-word;
        }

        .carrier-pill {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 10px;
            border-radius: 999px;
            color: var(--dark);
            background: #ffffff;
            border: 1px solid rgba(var(--brand-rgb), 0.25);
            box-shadow: 0 8px 18px rgba(17, 27, 13, 0.06);
            font-size: 0.76rem;
            font-weight: 900;
            white-space: nowrap;
        }

        .timeline {
            position: relative;
            display: grid;
            gap: 0;
        }

        .timeline-step {
            display: grid;
            grid-template-columns: 48px minmax(0, 1fr);
            gap: 14px;
            position: relative;
            padding-bottom: 18px;
        }

        .timeline-step:last-child {
            padding-bottom: 0;
        }

        .timeline-step:not(:last-child)::before {
            content: "";
            position: absolute;
            top: 48px;
            left: 23px;
            width: 3px;
            height: calc(100% - 30px);
            border-radius: 999px;
            background: var(--line-strong);
        }

        .timeline-step.completed:not(:last-child)::before {
            background: linear-gradient(180deg, var(--brand), var(--brand-strong));
        }

        .timeline-step.active:not(:last-child)::before {
            background: linear-gradient(180deg, var(--brand), var(--line-strong));
        }

        .tl-dot {
            position: relative;
            z-index: 1;
            width: 48px;
            height: 48px;
            border-radius: 17px;
            display: grid;
            place-items: center;
            background: #ffffff;
            border: 2px solid var(--line-strong);
            color: #93a587;
            font-size: 1rem;
            font-weight: 900;
            box-shadow: 0 8px 20px rgba(17, 27, 13, 0.06);
        }

        .timeline-step.completed .tl-dot {
            color: var(--dark);
            background: var(--brand-soft);
            border-color: rgba(var(--brand-rgb), 0.58);
        }

        .timeline-step.active .tl-dot {
            color: var(--dark);
            background: linear-gradient(135deg, #c8ff8a, var(--brand));
            border-color: var(--brand-strong);
            box-shadow: 0 0 0 6px var(--brand-ring), 0 12px 26px rgba(var(--brand-rgb), 0.22);
            animation: activePulse 2s ease-in-out infinite;
        }

        @keyframes activePulse {
            0%, 100% { box-shadow: 0 0 0 6px var(--brand-ring), 0 12px 26px rgba(var(--brand-rgb), 0.22); }
            50% { box-shadow: 0 0 0 10px rgba(var(--brand-rgb), 0.10), 0 12px 26px rgba(var(--brand-rgb), 0.16); }
        }

        .tl-content {
            padding: 3px 0 0;
        }

        .tl-label-row {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tl-label {
            color: var(--ink);
            font-size: 0.96rem;
            line-height: 1.4;
            font-weight: 900;
            letter-spacing: -0.02em;
        }

        .timeline-step:not(.completed):not(.active) .tl-label {
            color: #91a385;
        }

        .current-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 9px;
            border-radius: 999px;
            color: var(--dark);
            background: var(--brand);
            font-size: 0.68rem;
            font-weight: 900;
        }

        .tl-date {
            margin-top: 5px;
            color: var(--muted);
            font-size: 0.78rem;
            line-height: 1.45;
            font-weight: 650;
        }

        .timeline-step.completed .tl-date {
            color: var(--brand-strong);
        }

        .support-strip {
            display: grid;
            grid-template-columns: 48px minmax(0, 1fr);
            gap: 14px;
            padding: 18px;
            border-radius: var(--radius-md);
            color: #ffffff;
            background: linear-gradient(135deg, var(--dark), var(--dark-2));
            box-shadow: var(--shadow-md);
        }

        .support-icon {
            width: 48px;
            height: 48px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            color: var(--dark);
            background: var(--brand);
            font-size: 1.15rem;
        }

        .support-title {
            font-weight: 900;
            letter-spacing: -0.025em;
        }

        .support-copy {
            margin-top: 4px;
            color: rgba(255, 255, 255, 0.68);
            font-size: 0.86rem;
            line-height: 1.55;
        }

        .support-links {
            margin-top: 12px;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 9px;
        }

        .support-link {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 36px;
            padding: 8px 11px;
            border-radius: 999px;
            color: rgba(255, 255, 255, 0.86);
            text-decoration: none;
            font-size: 0.78rem;
            font-weight: 850;
            background: rgba(255, 255, 255, 0.09);
            border: 1px solid rgba(255, 255, 255, 0.10);
        }

        .support-link i {
            color: var(--brand);
        }

        .chat-card {
            position: sticky;
            top: 94px;
        }

        .agent-card {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 14px;
            border-radius: 18px;
            background: var(--paper-soft);
            border: 1px solid var(--line);
        }

        .agent-avatar {
            position: relative;
            width: 50px;
            height: 50px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            color: var(--dark);
            background: linear-gradient(135deg, #c8ff8a, var(--brand));
            font-size: 1.08rem;
            font-weight: 900;
            box-shadow: 0 12px 22px rgba(var(--brand-rgb), 0.20);
            flex: 0 0 auto;
        }

        .agent-avatar::after {
            content: "";
            position: absolute;
            right: -2px;
            bottom: -2px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #22c55e;
            border: 3px solid #ffffff;
        }

        .agent-name {
            color: var(--ink);
            font-size: 0.95rem;
            font-weight: 900;
            letter-spacing: -0.02em;
        }

        .agent-role {
            margin-top: 3px;
            color: var(--muted);
            font-size: 0.76rem;
            line-height: 1.4;
            font-weight: 700;
        }

        .alert-success {
            margin-top: 14px;
            padding: 12px 14px;
            border-radius: 14px;
            color: #166534;
            background: #dcfce7;
            border: 1px solid #bbf7d0;
            font-size: 0.84rem;
            line-height: 1.45;
            font-weight: 800;
        }

        .chat-wrap {
            display: flex;
            flex-direction: column;
            height: 540px;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px 18px;
            display: flex;
            flex-direction: column;
            gap: 13px;
            background:
                linear-gradient(rgba(255, 255, 255, 0.74), rgba(255, 255, 255, 0.74)),
                radial-gradient(circle at 10% 0%, rgba(var(--brand-rgb), 0.10), transparent 30%);
        }

        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: transparent;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: var(--line-strong);
            border-radius: 999px;
        }

        .chat-bubble {
            max-width: 84%;
            display: flex;
            flex-direction: column;
        }

        .bubble-customer {
            align-self: flex-end;
        }

        .bubble-agent {
            align-self: flex-start;
        }

        .bubble-sender {
            margin: 0 2px 4px;
            color: var(--muted);
            font-size: 0.72rem;
            font-weight: 900;
        }

        .bubble-customer .bubble-sender {
            text-align: right;
        }

        .bubble-inner {
            padding: 11px 14px;
            border-radius: 16px;
            font-size: 0.88rem;
            line-height: 1.58;
            word-break: break-word;
            box-shadow: 0 10px 20px rgba(17, 27, 13, 0.06);
        }

        .bubble-customer .bubble-inner {
            color: var(--dark);
            background: linear-gradient(135deg, #c8ff8a, var(--brand));
            border-bottom-right-radius: 5px;
        }

        .bubble-agent .bubble-inner {
            color: var(--ink-soft);
            background: #ffffff;
            border: 1px solid var(--line);
            border-bottom-left-radius: 5px;
        }

        .bubble-meta {
            margin: 4px 2px 0;
            color: var(--muted);
            font-size: 0.7rem;
            font-weight: 650;
        }

        .bubble-customer .bubble-meta {
            text-align: right;
        }

        .chat-empty {
            margin: auto;
            max-width: 260px;
            text-align: center;
            color: var(--muted);
            font-size: 0.88rem;
            line-height: 1.55;
            font-weight: 700;
        }

        .chat-empty i {
            display: grid;
            place-items: center;
            width: 58px;
            height: 58px;
            margin: 0 auto 13px;
            border-radius: 20px;
            color: var(--brand-strong);
            background: var(--brand-soft);
            font-size: 1.35rem;
        }

        .chat-form {
            border-top: 1px solid var(--line);
            padding: 13px;
            display: flex;
            align-items: flex-end;
            gap: 9px;
            background: rgba(255, 255, 255, 0.96);
        }

        .chat-input-wrap {
            position: relative;
            flex: 1;
        }

        .chat-input {
            width: 100%;
            min-height: 44px;
            max-height: 132px;
            padding: 12px 14px;
            resize: none;
            outline: none;
            color: var(--ink);
            background: var(--paper-soft);
            border: 1px solid var(--line-strong);
            border-radius: 15px;
            font-size: 0.88rem;
            line-height: 1.45;
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .chat-input:focus {
            border-color: var(--brand);
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(var(--brand-rgb), 0.13);
        }

        .chat-send {
            width: 46px;
            height: 46px;
            border: none;
            border-radius: 15px;
            display: grid;
            place-items: center;
            cursor: pointer;
            color: var(--dark);
            background: var(--brand);
            font-size: 1rem;
            font-weight: 900;
            box-shadow: 0 12px 24px rgba(var(--brand-rgb), 0.24);
            transition: transform 0.16s ease, background 0.16s ease, opacity 0.16s ease;
            flex: 0 0 auto;
        }

        .chat-send:hover {
            transform: translateY(-1px);
            background: var(--brand-strong);
        }

        .chat-send:disabled {
            cursor: not-allowed;
            opacity: 0.72;
            transform: none;
        }

        .footer-note {
            margin-top: 20px;
            text-align: center;
            color: var(--muted);
            font-size: 0.78rem;
            line-height: 1.6;
            font-weight: 700;
        }

        @media (max-width: 1020px) {
            .main-grid {
                grid-template-columns: 1fr;
            }

            .chat-card {
                position: relative;
                top: auto;
            }

            .chat-wrap {
                height: 500px;
            }
        }

        @media (max-width: 820px) {
            .topbar-inner {
                justify-content: center;
            }

            .topbar-right {
                display: none;
            }

            .hero-content {
                grid-template-columns: 1fr;
            }

            .status-panel {
                min-width: 0;
            }

            .summary-row {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 620px) {
            .topbar-inner,
            .nav-inner,
            .container {
                width: min(100% - 24px, 1180px);
            }

            .nav-inner {
                min-height: 68px;
            }

            .brand-subtitle,
            .secure-pill {
                display: none;
            }

            .brand-mark {
                width: 38px;
                height: 38px;
                border-radius: 13px;
            }

            .logout-link {
                height: 38px;
                padding: 0 12px;
                font-size: 0.74rem;
            }

            .container {
                padding-top: 20px;
            }

            .hero {
                padding: 22px 18px;
                border-radius: 22px;
            }

            .hero h1 {
                font-size: 2.1rem;
            }

            .hero-sub {
                font-size: 0.9rem;
            }

            .summary-row,
            .meta-grid {
                grid-template-columns: 1fr;
            }

            .card-header {
                align-items: flex-start;
                flex-direction: column;
                min-height: auto;
                padding: 17px;
            }

            .card-body {
                padding: 17px;
            }

            .tracking-top {
                flex-direction: column;
            }

            .chat-bubble {
                max-width: 92%;
            }

            .chat-wrap {
                height: 480px;
            }
        }
    </style>
</head>
<body>
@php
    $stage = $order->shipping_stage ?: $order->status;
    $stageMap = [
        'order_completed' => ['status-success', 'Completed', 'fa-circle-check', 'Your order has been completed successfully.'],
        'delivered'       => ['status-success', 'Delivered', 'fa-box-open', 'Your package has reached the delivery address.'],
        'in_transit'      => ['status-blue', 'In Transit', 'fa-truck-fast', 'Your order is moving through the shipping network.'],
        'ready_to_ship'   => ['status-purple', 'Ready to Ship', 'fa-box', 'Your order is packed and waiting for carrier pickup.'],
        'warehouse_ready' => ['status-purple', 'At Warehouse', 'fa-warehouse', 'Your order is prepared at the warehouse.'],
        'cancelled'       => ['status-red', 'Cancelled', 'fa-circle-xmark', 'This order has been cancelled.'],
    ];
    $pill = $stageMap[$stage] ?? ['status-yellow', 'In Progress', 'fa-clock', 'Your order is currently being processed.'];
@endphp

<div class="page-shell">
    <div class="topbar">
        <div class="topbar-inner">
            <div class="topbar-left">
                <span class="topbar-item"><i class="fas fa-phone"></i> {{ $portalBrand['support_phone'] }}</span>
                <span class="topbar-item"><i class="fas fa-envelope"></i> {{ $portalBrand['support_email'] }}</span>
            </div>
            <div class="topbar-right">
                <span class="topbar-item"><i class="fas fa-gem"></i> Premium custom packaging support</span>
            </div>
        </div>
    </div>

    <header class="nav">
        <div class="nav-inner">
            <a class="brand-lockup" href="{{ $portalBrand['website'] }}" style="align-items: center; gap: 15px;">
                <img src="{{ $portalBrand['logo'] }}" alt="{{ $portalBrand['name'] }} Logo" style="height:{{ $portalBrand['is_al_massa'] ? '68px' : '55px' }}; width:auto; max-width:210px; object-fit:contain;">
            </a>

            <div class="nav-actions">
                @if(isset($allOrders) && count($allOrders) > 1)
                <div style="position: relative; display: inline-block;" class="order-dropdown">
                    <button class="secure-pill" style="cursor:pointer; background:#fff; border-color:var(--line); color:var(--ink-soft); outline:none;" onclick="toggleOrderDropdown(event)">
                        <i class="fas fa-box-open"></i> Your Orders ({{ count($allOrders) }}) <i class="fas fa-chevron-down" style="font-size:0.7rem; margin-left:4px;"></i>
                    </button>
                    <div id="orderDropdown" style="display:none; position:absolute; right:0; top:45px; background:#fff; border:1px solid var(--line); border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.1); width:280px; z-index:100; max-height:380px; overflow-y:auto; padding:8px;">
                        <div style="padding:8px 12px; font-size:0.75rem; color:#6f8068; font-weight:700; text-transform:uppercase; border-bottom:1px solid #f0f0f0; margin-bottom:4px;">Order History</div>
                        @foreach($allOrders as $o)
                            <a href="{{ route('portal.track', $o->id) }}?token={{ \App\Http\Controllers\CustomerPortalController::getToken($o) }}" style="display:flex; flex-direction:column; padding:10px 12px; text-decoration:none; color:var(--ink); border-radius:8px; margin-bottom:2px; transition:background 0.2s; {{ $o->id == $order->id ? 'background:var(--brand-soft); border:1px solid var(--line-strong);' : 'border:1px solid transparent;' }}" onmouseover="this.style.background='var(--paper-soft)'" onmouseout="this.style.background='{{ $o->id == $order->id ? 'var(--brand-soft)' : 'transparent' }}'">
                                <div style="display:flex; justify-content:space-between; align-items:center;">
                                    <span style="font-weight: 700; font-size: 0.85rem;">Order #{{ $o->id }}</span>
                                    <span style="font-size:0.65rem; padding:3px 6px; background:{{ $o->payment_status == 'received' || $o->payment_status == 'approved' ? $portalBrand['primary_soft'] : '#fff1f2' }}; color:{{ $o->payment_status == 'received' || $o->payment_status == 'approved' ? $portalBrand['primary_dark'] : '#e11d48' }}; border-radius:4px; font-weight:700; letter-spacing:0.5px;">{{ strtoupper($o->payment_status == 'received' || $o->payment_status == 'approved' ? 'PAID' : 'PENDING') }}</span>
                                </div>
                                <div style="font-size:0.75rem; color:#6f8068; margin-top:6px;"><i class="fa-regular fa-calendar" style="margin-right:3px;"></i> {{ $o->created_at->format('M d, Y') }}</div>
                            </a>
                        @endforeach
                    </div>
                </div>
                <script>
                    function toggleOrderDropdown(e) {
                        e.stopPropagation();
                        var d = document.getElementById('orderDropdown');
                        d.style.display = d.style.display === 'none' ? 'block' : 'none';
                    }
                    document.addEventListener('click', function(e) {
                        var dropdown = document.getElementById('orderDropdown');
                        var btn = document.querySelector('.order-dropdown button');
                        if (dropdown && !dropdown.contains(e.target) && !btn.contains(e.target)) {
                            dropdown.style.display = 'none';
                        }
                    });
                </script>
                @else
                <div class="secure-pill"><i class="fas fa-shield-halved"></i> Secure Portal</div>
                @endif
                <a href="{{ route('portal.logout') }}" class="logout-link"><i class="fas fa-arrow-right-from-bracket"></i> Logout</a>
            </div>
        </div>
    </header>

    <main class="container">
        <section class="hero">
            <div class="hero-content">
                <div>
                    <div class="eyebrow"><i class="fas fa-location-dot"></i> Live order status</div>
                    <h1>Order Tracking — #{{ $order->id }}</h1>
                    <p class="hero-sub">
                        Stay updated on your custom packaging order, shipping details, payment status, and sales agent messages from one clean dashboard.
                    </p>

                    <div class="hero-product">
                        <span class="mini-chip"><i class="fas fa-box-open"></i> {{ $lead ? ($lead->product_name ?? 'Your Order') : 'Your Order' }}</span>
                        @if($lead && $lead->client_name)
                            <span class="mini-chip"><i class="fas fa-user"></i> {{ $lead->client_name }}</span>
                        @endif
                    </div>
                </div>

                <aside class="status-panel">
                    <div class="status-label">Current status</div>
                    <div class="status-main">
                        <div class="status-orb"><i class="fas {{ $pill[2] }}"></i></div>
                        <div>
                            <div class="status-name">{{ $pill[1] }}</div>
                            <div class="status-note">{{ $pill[3] }}</div>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="summary-row" aria-label="Order quick summary">
            <div class="summary-tile">
                <div class="summary-label"><i class="fas fa-hashtag"></i> Order</div>
                <div class="summary-value">#{{ $order->id }}</div>
            </div>
            <div class="summary-tile">
                <div class="summary-label"><i class="fas fa-calendar-days"></i> Date placed</div>
                <div class="summary-value">{{ $order->created_at->format('M d, Y') }}</div>
            </div>
            <div class="summary-tile">
                <div class="summary-label"><i class="fas fa-credit-card"></i> Payment</div>
                <div class="summary-value">{{ ucfirst(str_replace('_', ' ', $order->payment_status ?? 'Pending')) }}</div>
            </div>
            <div class="summary-tile">
                <div class="summary-label"><i class="fas fa-circle-info"></i> Status</div>
                <div class="summary-value"><span class="status-pill {{ $pill[0] }}"><i class="fas {{ $pill[2] }}"></i> {{ $pill[1] }}</span></div>
            </div>
        </section>

        <section class="main-grid">
            <div class="content-stack">
                <div class="card">
                    <div class="card-header">
                        <div class="header-title-wrap">
                            <div class="card-header-icon"><i class="fas fa-file-invoice"></i></div>
                            <div>
                                <div class="card-title">Order Summary</div>
                                <div class="card-subtitle">A clear overview of your product, payment, and delivery information.</div>
                            </div>
                        </div>
                        <span class="status-pill {{ $pill[0] }}"><i class="fas {{ $pill[2] }}"></i> {{ $pill[1] }}</span>
                    </div>

                    <div class="card-body">
                        <div class="meta-grid">
                            <div class="meta-item">
                                <div class="meta-label"><i class="fas fa-hashtag"></i> Order number</div>
                                <div class="meta-value">#{{ $order->id }}</div>
                            </div>

                            <div class="meta-item">
                                <div class="meta-label"><i class="fas fa-calendar-check"></i> Date placed</div>
                                <div class="meta-value">{{ $order->created_at->format('M d, Y') }}</div>
                            </div>

                            @if($lead && $lead->product_name)
                                <div class="meta-item wide">
                                    <div class="meta-label"><i class="fas fa-cube"></i> Product</div>
                                    <div class="meta-value">{{ $lead->product_name }}</div>
                                </div>
                            @endif

                            @if($lead && $lead->quantity)
                                <div class="meta-item">
                                    <div class="meta-label"><i class="fas fa-layer-group"></i> Quantity</div>
                                    <div class="meta-value">{{ number_format($lead->quantity) }}</div>
                                </div>
                            @endif

                            <div class="meta-item">
                                <div class="meta-label"><i class="fas fa-wallet"></i> Payment status</div>
                                <div class="meta-value">{{ ucfirst(str_replace('_', ' ', $order->payment_status ?? 'Pending')) }}</div>
                            </div>

                            @if($order->shipping_address)
                                <div class="meta-item wide">
                                    <div class="meta-label"><i class="fas fa-location-dot"></i> Shipping address</div>
                                    <div class="meta-value">{{ $order->shipping_address }}</div>
                                </div>
                            @endif
                        </div>

                        @if($order->tracking_number)
                            <div class="tracking-box">
                                <div class="tracking-top">
                                    <div>
                                        <div class="tracking-label">Tracking number</div>
                                        <div class="tracking-number">{{ $order->tracking_number }}</div>
                                    </div>

                                    @if($order->shipping_carrier)
                                        <div class="carrier-pill"><i class="fas fa-truck"></i> {{ $carrierLabels[$order->shipping_carrier] ?? strtoupper($order->shipping_carrier) }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <div class="header-title-wrap">
                            <div class="card-header-icon"><i class="fas fa-route"></i></div>
                            <div>
                                <div class="card-title">Order Progress</div>
                                <div class="card-subtitle">Follow each production and shipping milestone as it updates.</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="timeline">
                            @foreach($timeline as $i => $step)
                                <div class="timeline-step {{ $step['completed'] ? 'completed' : ($step['active'] ? 'active' : '') }}">
                                    <div class="tl-dot">
                                        @if($step['completed'])
                                            <i class="fas fa-check"></i>
                                        @else
                                            {{ $step['icon'] }}
                                        @endif
                                    </div>

                                    <div class="tl-content">
                                        <div class="tl-label-row">
                                            <div class="tl-label">{{ $step['label'] }}</div>
                                            @if($step['active'])
                                                <span class="current-tag"><i class="fas fa-sparkles"></i> Current</span>
                                            @endif
                                        </div>

                                        @if($step['date'])
                                            <div class="tl-date">{{ \Carbon\Carbon::parse($step['date'])->format('M d, Y') }}</div>
                                        @elseif($step['completed'])
                                            <div class="tl-date">Completed</div>
                                        @else
                                            <div class="tl-date">Pending update</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="support-strip">
                    <div class="support-icon"><i class="fas fa-headset"></i></div>
                    <div>
                        <div class="support-title">Need help with your custom packaging order?</div>
                        <div class="support-copy">Our support team can help with order updates, shipping details, artwork questions, and packaging production information.</div>
                        <div class="support-links">
                            <a class="support-link" href="tel:{{ $portalBrand['support_phone_link'] }}"><i class="fas fa-phone"></i> {{ $portalBrand['support_phone'] }}</a>
                            <a class="support-link" href="mailto:{{ $portalBrand['support_email'] }}"><i class="fas fa-envelope"></i> {{ $portalBrand['support_email'] }}</a>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="card chat-card">
                <div class="card-header">
                    <div class="header-title-wrap">
                        <div class="card-header-icon"><i class="fas fa-comments"></i></div>
                        <div>
                            <div class="card-title">Chat with Your Sales Agent</div>
                            <div class="card-subtitle">Send a message without leaving your order page.</div>
                        </div>
                    </div>
                </div>

                <div class="card-body" style="padding-bottom: 0;">
                    @if($agent)
                        <div class="agent-card">
                            <div class="agent-avatar">{{ strtoupper(substr($agent->name, 0, 1)) }}</div>
                            <div>
                                <div class="agent-name">{{ $agent->name }}</div>
                                <div class="agent-role">Sales Agent · {{ $portalBrand['name'] }}</div>
                            </div>
                        </div>
                    @else
                        <div class="agent-card">
                            <div class="agent-avatar"><i class="fas fa-user-headset"></i></div>
                            <div>
                                <div class="agent-name">{{ $portalBrand['name'] }} Support</div>
                                <div class="agent-role">A sales agent will respond when available.</div>
                            </div>
                        </div>
                    @endif

                    @if(session('chat_success'))
                        <div class="alert-success"><i class="fas fa-check-circle"></i> Message sent. Your agent will reply shortly.</div>
                    @endif
                </div>

                <div class="chat-wrap">
                    <div class="chat-messages" id="chatMessages">
                        @forelse($messages as $msg)
                            <div class="chat-bubble {{ $msg->sender_type === 'customer' ? 'bubble-customer' : 'bubble-agent' }}" id="msg-{{ $msg->id }}">
                                <div class="bubble-sender">{{ $msg->sender_type === 'customer' ? 'You' : ($agent ? $agent->name : 'Agent') }}</div>
                                <div class="bubble-inner">{{ $msg->message_body }}</div>
                                <div class="bubble-meta">{{ \Carbon\Carbon::parse($msg->created_at)->format('M d, h:i A') }}</div>
                            </div>
                        @empty
                            <div class="chat-empty" id="chatEmpty">
                                <i class="fas fa-comments"></i>
                                No messages yet. Send a message to your sales agent below.
                            </div>
                        @endforelse
                    </div>

                    <div class="chat-form">
                        <div class="chat-input-wrap">
                            <textarea class="chat-input" rows="1" placeholder="Type your message..." id="chatInput"></textarea>
                        </div>
                        <button class="chat-send" type="button" id="chatSendBtn" onclick="sendChatMessage()" aria-label="Send message">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </aside>
        </section>

        <div class="footer-note">
            {{ $portalBrand['name'] }} customer portal · Secure order tracking for custom boxes and packaging.
        </div>
    </main>
</div>

<script>
    const chatInput    = document.getElementById('chatInput');
    const chatMessages = document.getElementById('chatMessages');
    const chatSendBtn  = document.getElementById('chatSendBtn');

    const messagePostUrl = @json(url("/portal/track/" . $order->id . "/message"));
    const messagePollUrl = @json(url("/portal/track/" . $order->id . "/messages"));
    const portalToken    = @json($token);
    const csrfToken      = @json(csrf_token());
    const agentName      = @json($agent ? $agent->name : 'Agent');

    let lastId  = {{ $messages->count() ? $messages->last()->id : 0 }};
    let sending = false;

    function formatMessageDate(value) {
        const date = value ? new Date(value) : new Date();
        return date.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function removeEmptyState() {
        const empty = document.getElementById('chatEmpty');
        if (empty) empty.remove();
    }

    function appendMessage(msg) {
        if (!msg || !msg.id) return;
        if (document.getElementById('msg-' + msg.id)) return;

        removeEmptyState();

        const isCustomer = msg.sender_type === 'customer';
        const wrapper = document.createElement('div');
        wrapper.id = 'msg-' + msg.id;
        wrapper.className = 'chat-bubble ' + (isCustomer ? 'bubble-customer' : 'bubble-agent');

        const sender = document.createElement('div');
        sender.className = 'bubble-sender';
        sender.textContent = isCustomer ? 'You' : agentName;

        const body = document.createElement('div');
        body.className = 'bubble-inner';
        body.textContent = msg.message_body || msg.message || '';

        const meta = document.createElement('div');
        meta.className = 'bubble-meta';
        meta.textContent = formatMessageDate(msg.created_at);

        wrapper.appendChild(sender);
        wrapper.appendChild(body);
        wrapper.appendChild(meta);
        chatMessages.appendChild(wrapper);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        lastId = Math.max(lastId, Number(msg.id) || lastId);
    }

    if (chatInput) {
        chatInput.addEventListener('input', function () {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 132) + 'px';
        });

        chatInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendChatMessage();
            }
        });
    }

    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function setSendingState(active) {
        sending = active;
        chatSendBtn.disabled = active;
        chatSendBtn.innerHTML = active
            ? '<i class="fas fa-spinner fa-spin"></i>'
            : '<i class="fas fa-paper-plane"></i>';
    }

    function sendChatMessage() {
        if (sending || !chatInput) return;

        const msg = chatInput.value.trim();
        if (!msg) return;

        setSendingState(true);

        fetch(messagePostUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ token: portalToken, message: msg })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                appendMessage({
                    id: data.id,
                    sender_type: 'customer',
                    message_body: msg,
                    created_at: new Date().toISOString()
                });

                chatInput.value = '';
                chatInput.style.height = 'auto';
            } else {
                alert(data.message || 'Failed to send. Please try again.');
            }
        })
        .catch(() => alert('Failed to send. Please try again.'))
        .finally(() => setSendingState(false));
    }

    function pollMessages() {
        fetch(messagePollUrl + '?token=' + encodeURIComponent(portalToken) + '&since=' + encodeURIComponent(lastId), {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(messages => {
            if (!Array.isArray(messages)) return;
            messages.forEach(msg => appendMessage(msg));
        })
        .catch(() => {});
    }

    setInterval(pollMessages, 5000);
</script>
</body>
</html>
