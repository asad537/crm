@php
    $__isAlMassaWorkspace = isset($activeCrmWorkspace) && $activeCrmWorkspace->slug === 'mybox-packaging-app';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $__isAlMassaWorkspace ? 'Al Massa Packaging CRM' : 'CRM Panel' }} - @yield('title')</title>
    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Inter:wght@400;500;600&display=swap"
        rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- Favicon -->
    @if($__isAlMassaWorkspace)
        <link rel="icon" type="image/png" href="{{ asset('al-massa-packaging-logo.png') }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('al-massa-packaging-logo.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('al-massa-packaging-logo.png') }}">
    @else
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon-square.svg') }}">
    @endif

    <!-- DateRangePicker CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

    <style>
        /* GLOBAL STYLES & VARIABLES */
        * {
            box-sizing: border-box;
        }

        :root {
            --bg-body: {{ $__isAlMassaWorkspace ? '#f8f5ef' : '#F4F5F9' }};
            --primary-purple: {{ $__isAlMassaWorkspace ? '#f45a24' : '#6c5ce7' }};
            --primary-hover: {{ $__isAlMassaWorkspace ? '#dc4313' : '#5848d8' }};
            --primary-soft: {{ $__isAlMassaWorkspace ? '#fff0e8' : '#eeebff' }};
            /* Raw RGB triplet so rgba(var(--primary-rgb), <alpha>) themes correctly while keeping each alpha. */
            --primary-rgb: {{ $__isAlMassaWorkspace ? '244, 90, 36' : '108, 92, 231' }};
            --primary-shadow: {{ $__isAlMassaWorkspace ? 'rgba(244, 90, 36, 0.28)' : 'rgba(108, 92, 231, 0.3)' }};
            --text-dark: {{ $__isAlMassaWorkspace ? '#171717' : '#1e293b' }};
            --text-gray: {{ $__isAlMassaWorkspace ? '#766f68' : '#94a3b8' }};
            --card-bg: #ffffff;
            --sidebar-bg: {{ $__isAlMassaWorkspace ? '#fffdf9' : '#ffffff' }};
            --border-radius-base: 16px;
            --success-green: #00b894;
            --danger-red: #ff7675;
            --sidebar-width: 205px;
        }

        body {
            background-color: var(--bg-body);
            font-family: 'DM Sans', 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            color: var(--text-dark);
            height: 100vh;
            height: 100dvh;
            display: flex;
            overflow: hidden;
            font-size: 0.85rem;
        }

        /* SIDEBAR */
        .custom-sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);

            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            height: 100vh;
            border-right: 1px solid #f1f5f9;
            transition: all 0.3s ease;
        }

        .brand-logo {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            padding: 1.5rem 0 0 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #1e293b;
        }

        .brand-logo i {
            background: #1e293b;
            color: white;
            padding: 8px;
            border-radius: 8px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .workspace-switch {
            width: 165px;
            display: flex;
            align-items: center;
            gap: 9px;
            margin-top: 4px;
            padding: 9px 10px;
            border: 1px solid color-mix(in srgb, var(--primary-purple) 28%, white);
            border-radius: 11px;
            background: var(--primary-soft);
            color: var(--text-dark);
            text-decoration: none;
            transition: all .2s ease;
        }

        .workspace-switch > i {
            width: 28px;
            height: 28px;
            padding: 0;
            border-radius: 8px;
            background: var(--primary-purple);
            color: #fff;
            font-size: .72rem;
        }

        .workspace-switch-copy { min-width: 0; flex: 1; line-height: 1.15; }
        .workspace-switch-copy small { display: block; color: var(--text-gray); font-size: .52rem; font-weight: 600; }
        .workspace-switch-copy strong { display: block; color: var(--text-dark); font-size: .68rem; margin-top: 2px; }
        .workspace-switch .switch-arrow { width: auto; height: auto; padding: 0; background: transparent; color: var(--primary-purple); font-size: .62rem; }
        .workspace-switch:hover { transform: translateY(-1px); border-color: var(--primary-purple); box-shadow: 0 6px 14px var(--primary-shadow); }

        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            flex: 1;
            overflow-y: auto;
        }

        .nav-item {
            display: flex;
            align-items: center;
            padding: 0.65rem 0.85rem;
            border-radius: 10px;
            color: #64748b;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.82rem;
            white-space: nowrap;
            transition: all 0.2s;
            gap: 8px;
        }

        .nav-item i {
            width: 20px;
            margin-right: 9px;
            font-size: 0.95rem;
        }

        .nav-item.active {
            background-color: var(--primary-purple);
            color: white;
            box-shadow: 0 4px 12px var(--primary-shadow);
        }

        .nav-item:hover:not(.active) {
            background-color: #f8fafc;
            color: #1e293b;
        }

        .nav-item .arrow {
            margin-left: auto;
            font-size: 0.78rem;
            opacity: 0.6;
            flex: 0 0 auto;
        }

        .nav-count {
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            border-radius: 999px;
            background: #ef4444;
            color: #fff;
            font-size: 0.56rem;
            font-weight: 800;
            line-height: 16px;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            animation: pulse-badge 1.5s infinite;
        }

        .nav-count.dot {
            min-width: 12px;
            width: 12px;
            height: 12px;
            padding: 0;
            border-radius: 50%;
            line-height: 12px;
            font-size: 0;
        }

        .nav-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-width: 0;
            flex: 1 1 auto;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .nav-right {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex: 0 0 auto;
            margin-left: auto;
        }

        /* USER PROFILE BOTTOM */
        .sidebar-footer {
            margin-top: auto;
            border-top: 1px solid #f1f5f9;
            padding-top: 1.5rem;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin-bottom: 2rem;
            padding-left: 0.85rem;
            padding-right: 0.85rem;
            min-width: 0;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: #e0e7ff;
            color: var(--primary-purple);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            min-width: 0;
            flex: 1;
        }

        .user-name {
            font-size: 0.65rem;
            font-weight: 500;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 78px;
        }

        .user-role {
            font-size: 0.6rem;
            color: #94a3b8;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 78px;
        }

        .logout-form {
            margin-left: 0;
            flex: 0 0 auto;
        }

        .logout-btn {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: none;
            border: none;
            cursor: pointer;
            color: #000000;
            font-size: 1.2rem;
            padding: 0;
        }

        /* MAIN AREA */
        .main-area {
            flex: 1;
            min-width: 0;
            padding: 1rem 1.25rem 2rem 1.25rem;
            overflow-y: auto;
            position: relative;
            width: 100%;
            height: 100%;
            overflow-x: hidden;
        }

        .main-area.crm-loading {
            opacity: 0.55;
            pointer-events: none;
            transition: opacity 0.15s ease;
        }

        /* TOP HEADER */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .top-title h1 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 700;
            color: #1e293b;
            white-space: nowrap;
        }

        #live-time {
            display: inline-block;
            width: 104px;
            min-width: 104px;
            text-align: center;
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum";
        }

        .top-title p {
            margin: 0 0 0 12px;
            color: #94a3b8;
            font-size: 0.82rem;
            white-space: nowrap;
            align-self: flex-end;
            padding-bottom: 2px;
        }

        .top-actions {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .search-bar {
            background: white;
            padding: 0.65rem 1.25rem;
            border-radius: 99px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 250px;
            color: #94a3b8;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        }

        .search-bar input {
            border: none;
            outline: none;
            width: 100%;
            font-size: 0.9rem;
            color: #475569;
        }

        .notif-btn {
            width: 44px;
            height: 44px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
            color: #1e293b;
            cursor: pointer;
        }

        /* MENU TOGGLE FOR MOBILE */
        .menu-toggle {
            display: none;
            font-size: 1.5rem;
            color: #1e293b;
            cursor: pointer;
            margin-right: 1rem;
        }

        /* OVERLAY FOR MOBILE */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 998;
        }

        /* RESPONSIVE MEDIA QUERIES */
        @media (max-width: 900px) {
            .custom-sidebar {
                position: fixed;
                left: -260px;
                top: 0;
                z-index: 999;
                box-shadow: 5px 0 15px rgba(0, 0, 0, 0.05);
            }

            .custom-sidebar.open {
                left: 0;
            }

            .menu-toggle {
                display: block;
            }

            .sidebar-overlay.open {
                display: block;
            }

            .main-area {
                padding: 1rem 1rem;
            }

            .top-bar {
                margin-bottom: 1.5rem;
            }

            .search-bar {
                width: 100%;
                max-width: 220px;
            }
        }

        @media (max-width: 600px) {
            .top-bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .top-actions {
                width: 100%;
                justify-content: space-between;
            }

            .search-bar {
                flex: 1;
                max-width: none;
            }

            .nav-menu {
                height: auto;
            }

            .sidebar-footer {
                padding-bottom: 2rem;
                /* iOS safe area */
            }
        }

        /* PENDING BADGE PULSE */
        @keyframes pulse-badge {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.75; transform: scale(1.1); }
        }

        /* SCROLLBAR */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        /* ALERTS */
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: opacity .3s ease, transform .3s ease, max-height .35s ease,
                margin .35s ease, padding .35s ease;
            max-height: 160px;
        }

        .alert.auto-dismiss {
            opacity: 0;
            transform: translateY(-8px);
            max-height: 0;
            margin-top: 0;
            margin-bottom: 0;
            padding-top: 0;
            padding-bottom: 0;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        /* PAGINATION CUSTOMIZATION */
        .pagination {
            display: flex;
            padding-left: 0;
            list-style: none;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .page-link {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            padding: 0;
            margin-left: 0;
            line-height: 1;
            color: #64748b;
            background-color: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .page-link:hover {
            background-color: #f1f5f9;
            color: var(--primary-purple);
            border-color: #cbd5e1;
        }

        .page-item.active .page-link {
            z-index: 3;
            color: #fff;
            background-color: var(--primary-purple);
            border-color: var(--primary-purple);
            box-shadow: 0 2px 6px var(--primary-shadow);
        }

        .page-item.disabled .page-link {
            color: #cbd5e1;
            pointer-events: none;
            background-color: #fff;
            border-color: #e2e8f0;
        }

        /* COMPACT TABLE STYLES */
        .content-card {
            zoom: 0.88;
        }
        .table th, .table td {
            padding: 0.6rem 0.65rem !important;
            vertical-align: middle;
            font-size: 0.82rem;
        }
        .table thead th {
            background: #f8fafc;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.025em;
        }

        /* CUSTOM TOAST SYSTEM */
        .toast-container {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        }

        .premium-toast {
            background: white;
            color: #1e293b;
            padding: 12px 24px;
            border-radius: 50px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 0.9rem;
            min-width: 300px;
            justify-content: center;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            pointer-events: auto;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .premium-toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .premium-toast.success {
            border-left: 4px solid #10b981;
        }

        .premium-toast.error {
            border-left: 4px solid #ef4444;
        }

        .premium-toast i {
            font-size: 1.1rem;
        }

        .toast-success i {
            color: #10b981;
        }

        .toast-error i {
            color: #ef4444;
        }

        /* CUSTOM CONFIRM MODAL */
        .confirm-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            z-index: 11000;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
        }

        .confirm-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .confirm-modal {
            background: white;
            width: 90%;
            max-width: 400px;
            border-radius: 24px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            transform: scale(0.9);
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .confirm-overlay.show .confirm-modal {
            transform: scale(1);
        }

        .confirm-icon {
            width: 60px;
            height: 60px;
            background: #fef2f2;
            color: #ef4444;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1.5rem;
        }

        .confirm-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .confirm-text {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        .confirm-btns {
            display: flex;
            gap: 1rem;
        }

        .confirm-btn {
            flex: 1;
            padding: 0.85rem;
            border-radius: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-size: 0.9rem;
        }

        .btn-cancel {
            background: #f1f5f9;
            color: #475569;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
        }

        .btn-confirm { background: #ef4444; color: white; }
        .btn-confirm:hover { background: #dc2626; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2); }
        .btn-primary-confirm { background: var(--primary-purple); color: white; }
        .btn-primary-confirm:hover { background: var(--primary-hover); box-shadow: 0 4px 12px var(--primary-shadow); }

        .dashboard-workspace-switch {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: 4px;
            padding: 5px 9px;
            border: 1px solid color-mix(in srgb, var(--primary-purple) 22%, #e2e8f0);
            border-radius: 10px;
            background: var(--card-bg);
            box-shadow: 0 4px 14px rgba(15,23,42,.06);
        }
        .dashboard-workspace-switch i { color: var(--primary-purple); font-size: .75rem; }
        .dashboard-workspace-label {
            color: var(--text-gray);
            font-size: .6rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
            white-space: nowrap;
        }
        .dashboard-workspace-switch select {
            min-width: 155px;
            border: 0;
            outline: 0;
            background: transparent;
            color: var(--text-dark);
            font: inherit;
            font-size: .76rem;
            font-weight: 800;
            cursor: pointer;
        }


        @yield('styles')

        /* Workspace-wide Al Massa brand enforcement for page-local legacy styles. */
        body.al-massa-crm,
        body.al-massa-crm .main-area {
            background-color: var(--bg-body) !important;
        }
        .al-massa-crm .btn-primary,
        .al-massa-crm .ts-primary,
        .al-massa-crm .et-primary,
        .al-massa-crm .ef-primary,
        .al-massa-crm .action-btn,
        .al-massa-crm .btn-upload,
        .al-massa-crm .filter-btn.active,
        .al-massa-crm .stage-step.active,
        .al-massa-crm .page-item.active .page-link {
            background: var(--primary-purple) !important;
            border-color: var(--primary-purple) !important;
            box-shadow: 0 4px 12px var(--primary-shadow) !important;
        }
        .al-massa-crm .btn-primary:hover,
        .al-massa-crm .ts-primary:hover,
        .al-massa-crm .et-primary:hover,
        .al-massa-crm .ef-primary:hover,
        .al-massa-crm .action-btn:hover,
        .al-massa-crm .btn-upload:hover { background: var(--primary-hover) !important; }
        .al-massa-crm .design-card::before,
        .al-massa-crm .prepress-card::before,
        .al-massa-crm .stat-box.s-purple::before { background: linear-gradient(90deg,#f45a24,#ff8a4c) !important; }
        .al-massa-crm .empty-icon {
            color: var(--primary-purple) !important;
            background: none !important;
            -webkit-background-clip: initial !important;
            background-clip: initial !important;
            -webkit-text-fill-color: var(--primary-purple) !important;
        }
        .al-massa-crm .design-hero-icon,
        .al-massa-crm .prepress-hero-icon,
        .al-massa-crm .stat-icon,
        .al-massa-crm .icon-box.box-purple {
            color: var(--primary-purple) !important;
            background: linear-gradient(135deg,var(--primary-purple),#ff8a4c) !important;
        }
        .al-massa-crm .design-hero-icon,
        .al-massa-crm .prepress-hero-icon,
        .al-massa-crm .stat-icon,
        .al-massa-crm .icon-box.box-purple { background: var(--primary-soft) !important; }
        .al-massa-crm .empty-icon-wrapper::after { border-top-color: var(--primary-purple) !important; }
        .al-massa-crm .design-eyebrow,
        .al-massa-crm .prepress-eyebrow,
        .al-massa-crm .fulfillment-eyebrow,
        .al-massa-crm .ticket-subtitle i,
        .al-massa-crm .ticket-number,
        .al-massa-crm .ticket-section-title i,
        .al-massa-crm .proof-link-main i,
        .al-massa-crm .back-link,
        .al-massa-crm .panel-title i,
        .al-massa-crm .select-all,
        .al-massa-crm .chat-role,
        .al-massa-crm .chat-product { color: var(--primary-purple) !important; }
        .al-massa-crm .chat-item.active { background: var(--primary-soft) !important; border-color: var(--primary-purple) !important; }
        .al-massa-crm .s-completed {
            background: var(--primary-soft) !important;
            color: var(--primary-hover) !important;
        }
        .al-massa-crm .et-ticket-row:hover,
        .al-massa-crm .et-ticket-row:focus { box-shadow: inset 4px 0 0 var(--primary-purple) !important; }
        .al-massa-crm input:not([type="checkbox"]):not([type="radio"]):focus,
        .al-massa-crm select:focus,
        .al-massa-crm textarea:focus,
        .al-massa-crm input:not([type="checkbox"]):not([type="radio"]):focus-visible,
        .al-massa-crm select:focus-visible,
        .al-massa-crm textarea:focus-visible {
            outline: none !important;
            border-color: var(--primary-purple) !important;
            box-shadow: 0 0 0 3px var(--primary-shadow) !important;
        }
        .al-massa-crm input[type="checkbox"]:focus,
        .al-massa-crm input[type="checkbox"]:focus-visible,
        .al-massa-crm input[type="radio"]:focus,
        .al-massa-crm input[type="radio"]:focus-visible {
            outline: none !important;
            box-shadow: none !important;
        }
        .al-massa-crm [style*="background: #6366f1"], .al-massa-crm [style*="background:#6366f1"],
        .al-massa-crm [style*="background: #4f46e5"], .al-massa-crm [style*="background:#4f46e5"],
        .al-massa-crm [style*="background: #7c3aed"], .al-massa-crm [style*="background:#7c3aed"] { background: var(--primary-purple) !important; }
        .al-massa-crm [style*="color: #6366f1"], .al-massa-crm [style*="color:#6366f1"],
        .al-massa-crm [style*="color: #4f46e5"], .al-massa-crm [style*="color:#4f46e5"],
        .al-massa-crm [style*="color: #7c3aed"], .al-massa-crm [style*="color:#7c3aed"] { color: var(--primary-purple) !important; }
        .al-massa-crm [style*="border-color: #6366f1"], .al-massa-crm [style*="border-color:#6366f1"],
        .al-massa-crm [style*="border-color: #4f46e5"], .al-massa-crm [style*="border-color:#4f46e5"],
        .al-massa-crm [style*="border-color: #7c3aed"], .al-massa-crm [style*="border-color:#7c3aed"] { border-color: var(--primary-purple) !important; }
        /* Keep numeric entry clean across the CRM. Calculated displays are not inputs
           and remain unchanged. Add data-keep-zero to any future field that must
           intentionally load with a visible zero. */
        input[type="number"] {
            -moz-appearance: textfield;
            appearance: textfield;
        }
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>

<body class="{{ $__isAlMassaWorkspace ? 'al-massa-crm' : 'my-box-crm' }}">

    <!-- OVERLAY -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>

    <!-- SIDEBAR -->
    <div class="custom-sidebar" id="sidebar">
        <div class="brand-logo" style="justify-content:space-between;padding:.45rem 1rem 0 1.25rem;margin-bottom:.35rem;">
            <a href="{{ route('crm.dashboard') }}" title="Go to dashboard"
                style="display:flex; flex-direction: column; align-items:flex-start; gap:0; text-decoration:none; cursor:pointer;">
                @if($__isAlMassaWorkspace)
                    <img src="{{ asset('al-massa-packaging-logo.png') }}" alt="Al Massa Packaging — go to dashboard"
                        style="width:125px;height:125px;object-fit:contain;margin:0 0 3px 20px;">
                @else
                    <img src="{{ asset('my-box-printing-logo.svg') }}" alt="My Box Printing — go to dashboard"
                        style="height:57px;width:auto;margin:0 0 10px -35px;">
                @endif
            </a>
            <i class="fas fa-times" style="display:none;" onclick="toggleSidebar()"></i>
        </div>

        @php
            $__navUser = Auth::guard('crm')->user();
            $__isProductionRole = $__navUser->isDesigner() || $__navUser->isPrepress() || $__navUser->isRetention() || $__navUser->isQC() || $__navUser->isShipping() || $__navUser->isProductionManager() || $__navUser->isPressOperator() || $__navUser->isFinishingOperator() || $__navUser->isWarehouse() || $__navUser->isAccounts();
            $__isSalesRole = $__navUser->isSales() || $__navUser->isSalesManager() || $__navUser->isAdmin();
            $__sidebarCount = function ($key, $callback) use ($activeCrmWorkspace, $__navUser) {
                return \Illuminate\Support\Facades\Cache::remember(
                    'crm:sidebar:'.$activeCrmWorkspace->id.':'.$__navUser->id.':'.$key,
                    30,
                    $callback
                );
            };
            $__appProjectsCount = $__sidebarCount('app_projects', function () use ($activeCrmWorkspace) {
                return $activeCrmWorkspace->slug === 'my-box-printing'
                ? \App\CustomProject::where(function($q) {
                    $q->where('status', 'new')
                      ->orWhereHas('dielines', function($dq) {
                          $dq->where('status', 'pending')->where('is_company_upload', false);
                      });
                })->count()
                : 0;
            });
            $__inboxUnreadCount = $__sidebarCount('inbox_unread', function () use ($activeCrmWorkspace, $__navUser) {
                return \App\CrmEmail::where('is_spam', false)
                ->where('workspace_id', $activeCrmWorkspace->id)
                ->where('is_rejected', false)
                ->where('status', 'New')
                ->when(!$__navUser->isAdmin() && !$__navUser->isSalesManager(), function($q) use ($__navUser) {
                    $q->where('assigned_to', $__navUser->id);
                })
                ->count();
            });
            $__chatUnreadCount = $__sidebarCount('chat_unread', function () use ($activeCrmWorkspace, $__navUser) {
                return \App\CrmEmail::whereHas('messages', function($q) {
                    $q->where('is_read', false)->where('sender_type', 'client');
                })
                ->where('workspace_id', $activeCrmWorkspace->id)
                ->when(!$__navUser->isAdmin() && !$__navUser->isSalesManager(), function($q) use ($__navUser) {
                    $q->where('assigned_to', $__navUser->id);
                })
                ->count();
            });
            $__teamUnreadCount = $__sidebarCount('team_unread', function () use ($activeCrmWorkspace, $__navUser) {
                return \Illuminate\Support\Facades\DB::table('crm_internal_messages')
                ->where('workspace_id', $activeCrmWorkspace->id)
                ->where('receiver_id', $__navUser->id)
                ->where('is_read', 0)
                ->count();
            });
        @endphp

        <nav class="nav-menu">
            {{-- Dashboard: Everyone --}}
            <a href="{{ route('crm.dashboard') }}"
                class="nav-item {{ request()->routeIs('crm.dashboard') ? 'active' : '' }}">
                <i class="fas fa-chart-pie"></i> Dashboard
            </a>

            {{-- App Projects: Admin/Sales only (mobile app panel) --}}
            @if($__isSalesRole && $activeCrmWorkspace->slug === 'my-box-printing')
            <a href="{{ route('crm.app_projects') }}"
                class="nav-item {{ request()->routeIs('crm.app_projects') ? 'active' : '' }}">
                <i class="fas fa-mobile-alt"></i> App Projects
                @if($__appProjectsCount > 0)
                    <span class="nav-count" title="{{ $__appProjectsCount }} open projects">{{ $__appProjectsCount }}</span>
                @endif
                <i class="fas fa-chevron-right arrow"></i>
            </a>
            @endif

            {{-- Inbox: Hide from production-only roles --}}
            @if(!$__navUser->isDesigner() && !$__navUser->isPrepress() && !$__navUser->isEstimator() && !$__navUser->isProductionManager() && !$__navUser->isPressOperator() && !$__navUser->isQC() && !$__navUser->isWarehouse() && !$__navUser->isAccounts() && !$__navUser->isShipping())
            <a href="{{ route('crm.emails.index') }}"
                class="nav-item {{ (request()->routeIs('crm.emails.index') || request()->routeIs('crm.emails.show')) && request()->query('context') !== 'quotes' ? 'active' : '' }}">
                <i class="fas fa-inbox"></i>
                Quotes
                <span class="nav-right">
                    @if($__inboxUnreadCount > 0)
                        <span class="nav-count">{{ $__inboxUnreadCount }}</span>
                    @endif
                    <i class="fas fa-chevron-right arrow"></i>
                </span>
            </a>
            <a href="{{ route('crm.inquiries.index') }}"
                class="nav-item {{ request()->routeIs('crm.inquiries.*') || request()->routeIs('crm.emails.create_form') ? 'active' : '' }}">
                <i class="fas fa-clipboard-list"></i>
                <span class="nav-label">Inquiries</span>
                <span class="nav-right"><i class="fas fa-chevron-right arrow"></i></span>
            </a>
            @endif

            {{-- Chats: Sales roles only --}}
            @if($__isSalesRole)
            <a href="{{ route('crm.chats.index') }}"
                class="nav-item {{ request()->routeIs('crm.chats.*') ? 'active' : '' }}">
                <i class="fas fa-comments"></i> Chats
                <span class="nav-right">
                    @if($__chatUnreadCount > 0)
                        <span class="nav-count">{{ $__chatUnreadCount }}</span>
                    @endif
                    <i class="fas fa-chevron-right arrow"></i>
                </span>
            </a>
            @endif

            {{-- Get Estimate tickets: Sales and Estimators --}}
            @if($__isSalesRole || $__navUser->isEstimator() || $__navUser->isTeamLead())
            @php
                $__estimateTicketCount = $__sidebarCount('estimate_tickets', function () use ($activeCrmWorkspace, $__navUser) {
                    return \App\EstimateTicket::where('workspace_id', $activeCrmWorkspace->id)
                    ->where(function($q) use ($__navUser) {
                        if ($__navUser->isTeamLead()) {
                            $q->where(function($review) {
                                $review->where('status', 'team_lead_review')->whereNull('team_lead_id');
                            })->orWhere(function($mine) use ($__navUser) {
                                $mine->where('status', 'team_lead_open')->where('team_lead_id', $__navUser->id);
                            });
                        } elseif ($__navUser->isEstimator()) {
                            $q->where('status', 'pending');
                            $q->orWhere(function($mine) use ($__navUser) {
                                $mine->whereIn('status', ['open', 'revision_requested'])
                                    ->where('estimator_id', $__navUser->id);
                            });
                        } else {
                            $q->where('status', 'pending');
                            $q->orWhereIn('status', ['open', 'revision_requested']);
                        }
                    })
                    ->when($__navUser->isSales(), function($q) use ($__navUser) { $q->where('requested_by', $__navUser->id); })
                    ->count();
                });
            @endphp
            <a href="{{ route('crm.estimate_tickets.index') }}"
                class="nav-item {{ request()->routeIs('crm.estimate_tickets.*') ? 'active' : '' }}">
                <i class="fas fa-calculator"></i> Get Estimate
                <span class="nav-right">
                    @if($__estimateTicketCount > 0)<span class="nav-count">{{ $__estimateTicketCount }}</span>@endif
                    <i class="fas fa-chevron-right arrow"></i>
                </span>
            </a>
            @endif

            {{-- Team Chat: Everyone --}}
            <a href="{{ route('crm.team_chat.index') }}"
                class="nav-item {{ request()->routeIs('crm.team_chat.*') ? 'active' : '' }}" id="agent-chat-toggle">
                <i class="fas fa-headset"></i> Team Chat
                <span class="nav-right">
                    @if($__teamUnreadCount > 0)
                        <span class="nav-count">{{ $__teamUnreadCount }}</span>
                    @endif
                    <i class="fas fa-chevron-right arrow"></i>
                </span>
            </a>

            {{-- Spam: Sales roles only --}}
            @if($__isSalesRole)
            <a href="{{ route('crm.emails.rejected') }}"
                class="nav-item {{ request()->routeIs('crm.emails.rejected') ? 'active' : '' }}">
                <i class="fas fa-times-circle"></i> Rejected Leads
                <i class="fas fa-chevron-right arrow"></i>
            </a>
            <a href="{{ route('crm.emails.spam') }}"
                class="nav-item {{ request()->routeIs('crm.emails.spam') ? 'active' : '' }}">
                <i class="fas fa-exclamation-circle"></i> Spam
                <i class="fas fa-chevron-right arrow"></i>
            </a>
            @endif

            {{-- Sales Orders: Sales roles only --}}
            @if($__isSalesRole)
            <a href="{{ route('crm.sales_orders.index') }}"
                class="nav-item {{ request()->routeIs('crm.sales_orders.*') ? 'active' : '' }}">
                <i class="fas fa-truck"></i> Order Tracking
                <i class="fas fa-chevron-right arrow"></i>
            </a>
            @endif

            {{-- Design Tickets: Designer only --}}
            @if($__navUser->isDesigner() || $__navUser->isAdmin())
            @php
                $__designTicketsCount = $__sidebarCount('design_tickets', function () use ($activeCrmWorkspace) {
                    return \Illuminate\Support\Facades\Schema::hasTable('design_requirement_tickets')
                    ? \App\DesignRequirementTicket::where('workspace_id', $activeCrmWorkspace->id)->where('status', 'new')->whereNull('claimed_by')->count()
                    : \App\SalesOrder::whereHas('lead', function($q) use ($activeCrmWorkspace) {
                        $q->where('workspace_id', $activeCrmWorkspace->id);
                    })->where('status', 'in_design')->count();
                });
            @endphp
                <a href="{{ route('crm.design_tickets.index') }}"
                class="nav-item {{ request()->routeIs('crm.design_tickets.*') ? 'active' : '' }}">
                <i class="fas fa-paint-brush" style="margin-top:2px;"></i>
                <span class="nav-label">
                    Design
                </span>
                <span class="nav-right">
                    @if($__designTicketsCount > 0)
                        <span class="nav-count">{{ $__designTicketsCount }}</span>
                    @endif
                    <i class="fas fa-chevron-right arrow"></i>
                </span>
            </a>
            @endif

            {{-- Design Jobs: Al Massa workspace only. Designer + Admin manage; Sales (CSR) can view. --}}
            @if($activeCrmWorkspace->slug === 'mybox-packaging-app' && ($__navUser->isDesigner() || $__navUser->isAdmin() || $__navUser->isSales()))
            @php
                $__designJobsCount = $__sidebarCount('design_jobs', function () use ($activeCrmWorkspace) {
                    return \Illuminate\Support\Facades\Schema::hasTable('design_jobs')
                        ? \App\DesignJob::where('workspace_id', $activeCrmWorkspace->id)
                            ->where('status', '!=', 'delivered')->count()
                        : 0;
                });
            @endphp
                <a href="{{ route('crm.design_jobs.index') }}"
                class="nav-item {{ request()->routeIs('crm.design_jobs.*') ? 'active' : '' }}">
                <i class="fas fa-briefcase" style="margin-top:2px;"></i>
                <span class="nav-label">
                    Jobs
                </span>
                <span class="nav-right">
                    @if($__designJobsCount > 0)
                        <span class="nav-count">{{ $__designJobsCount }}</span>
                    @endif
                    <i class="fas fa-chevron-right arrow"></i>
                </span>
            </a>
            @endif

            {{-- Prepress Tickets: Prepress only --}}
            @if($__navUser->isPrepress() || $__navUser->isAdmin())
            @php
                $__prepressTicketsCount = $__sidebarCount('prepress_tickets', function () use ($activeCrmWorkspace) {
                    return \App\SalesOrder::whereHas('lead', function($q) use ($activeCrmWorkspace) {
                    $q->where('workspace_id', $activeCrmWorkspace->id);
                })->where('status', 'prepress')->count();
                });
            @endphp
                <a href="{{ route('crm.prepress_tickets.index') }}"
                class="nav-item {{ request()->routeIs('crm.prepress_tickets.*') ? 'active' : '' }}">
                <i class="fas fa-clipboard-check" style="margin-top:2px;"></i>
                <span class="nav-label">
                    Prepress
                </span>
                <span class="nav-right">
                    @if($__prepressTicketsCount > 0)
                        <span class="nav-count">{{ $__prepressTicketsCount }}</span>
                    @endif
                    <i class="fas fa-chevron-right arrow"></i>
                </span>
            </a>
            @endif

            {{-- Production --}}
            @if($__navUser->isProductionManager() || $__navUser->isPressOperator() || $__navUser->isQC() || $__navUser->isWarehouse() || $__navUser->isAdmin())
                <div style="font-size: 0.78rem; font-weight: 800; color: #111827; margin: 1rem 0 0.5rem 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">
                    @if($__navUser->isProductionManager() || $__navUser->isAdmin())
                        Production Tickets
                    @else
                        Production
                    @endif
                </div>

                @if($__navUser->isProductionManager() || $__navUser->isAdmin())
                <a href="{{ route('crm.production_jobs.index') }}"
                    class="nav-item {{ request()->routeIs('crm.production_jobs.*') || request()->routeIs('crm.production_machines.*') ? 'active' : '' }}">
                    <i class="fas fa-industry"></i>
                    <span class="nav-label">Production</span>
                    <i class="fas fa-chevron-right arrow"></i>
                </a>
                @endif

                @if($__navUser->isPressOperator() || $__navUser->isProductionManager() || $__navUser->isAdmin())
                @php
                    $__pressTicketsQuery = \App\ProductionJob::whereHas('salesOrder.lead', function($q) use ($activeCrmWorkspace) {
                        $q->where('workspace_id', $activeCrmWorkspace->id);
                    })->whereIn('status', ['scheduled', 'press_setup', 'adjustments_required', 'production_ready', 'full_production']);
                    if ($__navUser->isPressOperator()) {
                        $__pressTicketsQuery->where('press_operator_id', $__navUser->id);
                    }
                    $__pressTicketsCount = $__pressTicketsQuery->count();
                @endphp
                <a href="{{ route('crm.press_tickets.index') }}"
                    class="nav-item {{ request()->routeIs('crm.press_tickets.*') ? 'active' : '' }}">
                    <i class="fas fa-print" style="margin-top:2px;"></i>
                    <span class="nav-label">
                        Press
                    </span>
                    <span class="nav-right">
                        @if($__pressTicketsCount > 0)
                            <span class="nav-count">{{ $__pressTicketsCount }}</span>
                        @endif
                        <i class="fas fa-chevron-right arrow"></i>
                    </span>
                </a>
                @endif

                @if($__navUser->isQC() || $__navUser->isProductionManager() || $__navUser->isAdmin())
                @php
                    $__qcTicketsQuery = \App\ProductionJob::whereHas('salesOrder.lead', function($q) use ($activeCrmWorkspace) {
                        $q->where('workspace_id', $activeCrmWorkspace->id);
                    })->where(function($q) {
                        $q->where('status', 'final_quality_control')
                          ->orWhere(function($sq) {
                              $sq->where('status', 'first_sheet_review')
                                 ->whereHas('firstSheetChecks', function($cq) {
                                     $cq->whereIn('status', ['pending', 'pending_qc']);
                                 });
                          });
                    });
                    if ($__navUser->isQC() && \Illuminate\Support\Facades\Schema::hasColumn('production_jobs', 'qc_inspector_id')) {
                        $__qcTicketsQuery->where('qc_inspector_id', $__navUser->id);
                    }
                    $__qcTicketsCount = $__qcTicketsQuery->count();
                @endphp
                <a href="{{ route('crm.qc_tickets.index') }}"
                    class="nav-item {{ request()->routeIs('crm.qc_tickets.*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-check" style="margin-top:2px;"></i>
                    <span class="nav-label">
                        QC
                    </span>
                    <span class="nav-right">
                        @if($__qcTicketsCount > 0)
                            <span class="nav-count">{{ $__qcTicketsCount }}</span>
                        @endif
                        <i class="fas fa-chevron-right arrow"></i>
                    </span>
                </a>
                @endif


                @if($__navUser->isWarehouse() || $__navUser->isProductionManager() || $__navUser->isAdmin())
                @php
                    $__warehouseTicketsCount = \App\SalesOrder::whereHas('lead', function($q) use ($activeCrmWorkspace) {
                            $q->where('workspace_id', $activeCrmWorkspace->id);
                        })
                        ->where(function($q) {
                            $q->where('shipping_stage', 'warehouse_ready')
                              ->orWhere('status', 'warehouse_ready');
                        })
                        ->count();
                @endphp
                <a href="{{ route('crm.warehouse_tickets.index') }}"
                    class="nav-item {{ request()->routeIs('crm.warehouse_tickets.*') ? 'active' : '' }}">
                    <i class="fas fa-warehouse" style="margin-top:2px;"></i>
                    <span class="nav-label">
                        Warehouse
                    </span>
                    <span class="nav-right">
                        @if($__warehouseTicketsCount > 0)
                            <span class="nav-count">{{ $__warehouseTicketsCount }}</span>
                        @endif
                        <i class="fas fa-chevron-right arrow"></i>
                    </span>
                </a>
                @endif
            @endif

            @if($__navUser->isAccounts() || $__navUser->isSalesManager() || $__navUser->isAdmin())
                @php
                    $__accountsTicketsCount = \App\SalesOrder::whereHas('lead', function($q) use ($activeCrmWorkspace) {
                            $q->where('workspace_id', $activeCrmWorkspace->id);
                        })
                        ->where(function($q) {
                            $q->whereIn('shipping_stage', ['balance_payment_check', 'final_payment_pending', 'delivered', 'final_invoice', 'payment_posted'])
                              ->orWhereIn('status', ['pending_payment', 'balance_payment_check', 'final_payment_pending', 'delivered', 'final_invoice', 'payment_posted']);
                        })
                        ->count();
                @endphp
                <a href="{{ route('crm.accounts_tickets.index') }}"
                    class="nav-item {{ request()->routeIs('crm.accounts_tickets.*') ? 'active' : '' }}">
                    <i class="fas fa-file-invoice-dollar"></i>
                    <span class="nav-label">Account Tickets</span>
                    <span class="nav-right">
                        @if($__accountsTicketsCount > 0)
                            <span class="nav-count">{{ $__accountsTicketsCount }}</span>
                        @endif
                        <i class="fas fa-chevron-right arrow"></i>
                    </span>
                </a>
                <a href="{{ route('crm.general_ledger.index') }}"
                    class="nav-item {{ request()->routeIs('crm.general_ledger.*') ? 'active' : '' }}">
                    <i class="fas fa-book"></i>
                    <span class="nav-label">Accounts</span>
                    <span class="nav-right">
                        <i class="fas fa-chevron-right arrow"></i>
                    </span>
                </a>
                <a href="{{ route('crm.vendor_purchases.index') }}"
                    class="nav-item {{ request()->routeIs('crm.vendor_purchases.*') ? 'active' : '' }}">
                    <i class="fas fa-truck-loading"></i>
                    <span class="nav-label">Vendors</span>
                    <span class="nav-right">
                        <i class="fas fa-chevron-right arrow"></i>
                    </span>
                </a>
                <a href="{{ route('crm.customer_sales.index') }}"
                    class="nav-item {{ request()->routeIs('crm.customer_sales.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span class="nav-label">Customers</span>
                    <span class="nav-right">
                        <i class="fas fa-chevron-right arrow"></i>
                    </span>
                </a>
            @endif

            @if($__navUser->isShipping() || $__navUser->isSalesManager() || $__navUser->isAdmin())
                @php
                    $__shippingTicketsCount = \App\SalesOrder::whereHas('lead', function($q) use ($activeCrmWorkspace) {
                            $q->where('workspace_id', $activeCrmWorkspace->id);
                        })
                        ->where(function($q) {
                            $q->whereIn('shipping_stage', ['ready_to_ship', 'shipping_department', 'shipping_label_generated', 'in_transit'])
                              ->orWhereIn('status', ['ready_to_ship', 'shipping_department', 'shipping_label_generated', 'in_transit']);
                        })
                        ->count();
                @endphp
                <a href="{{ route('crm.shipping_tickets.index') }}"
                    class="nav-item {{ request()->routeIs('crm.shipping_tickets.*') ? 'active' : '' }}">
                    <i class="fas fa-shipping-fast"></i>
                    <span class="nav-label">Shipping</span>
                    <span class="nav-right">
                        @if($__shippingTicketsCount > 0)
                            <span class="nav-count">{{ $__shippingTicketsCount }}</span>
                        @endif
                        <i class="fas fa-chevron-right arrow"></i>
                    </span>
                </a>
            @endif

            @if($__navUser->isRetention() || $__navUser->isSalesManager() || $__navUser->isAdmin())
            <a href="{{ route('crm.retention_tickets.index') }}"
                class="nav-item {{ request()->routeIs('crm.retention_tickets.*') ? 'active' : '' }}">
                <i class="fas fa-user-clock"></i>
                <span class="nav-label">Retention</span>
                <span class="nav-right">
                    <i class="fas fa-chevron-right arrow"></i>
                </span>
            </a>
            @endif

            {{-- Invoice: Everyone EXCEPT production-only roles and Estimator (Accounts included) --}}
            @if(!$__navUser->isDesigner() && !$__navUser->isPrepress() && !$__navUser->isEstimator() && !$__navUser->isProductionManager() && !$__navUser->isPressOperator() && !$__navUser->isFinishingOperator() && !$__navUser->isQC() && !$__navUser->isWarehouse() && !$__navUser->isShipping())
            <a href="{{ route('crm.orders.index') }}"
                class="nav-item {{ request()->routeIs('crm.orders.*') ? 'active' : '' }}">
                <i class="fas fa-file-invoice"></i>
                <span class="nav-label">Invoice</span>
                <span class="nav-right">
                    <i class="fas fa-chevron-right arrow"></i>
                </span>
            </a>
            @endif


            {{-- Admin / Manager section --}}
            @if($__navUser->isAdmin() || $__navUser->isSalesManager())
                <div style="font-size: 0.78rem; font-weight: 800; color: #111827; margin: 1rem 0 0.5rem 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;">
                    {{ $__navUser->isAdmin() ? 'Admin' : 'Manager' }}</div>

                <a href="{{ route('crm.users.index') }}"
                    class="nav-item {{ request()->routeIs('crm.users.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    <span class="nav-label">Team</span>
                    <span class="nav-right">
                        <i class="fas fa-chevron-right arrow"></i>
                    </span>
                </a>
            @endif

            @if($__navUser->isAdmin())
                <a href="{{ route('crm.estimation_rates.index') }}"
                    class="nav-item {{ request()->routeIs('crm.estimation_rates.*') ? 'active' : '' }}">
                    <i class="fas fa-sliders-h"></i>
                    <span class="nav-label">Estimation Rates</span>
                    <span class="nav-right">
                        <i class="fas fa-chevron-right arrow"></i>
                    </span>
                </a>

                <a href="{{ route('crm.team_performance') }}"
                    class="nav-item {{ request()->routeIs('crm.team_performance') ? 'active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span class="nav-label">Performance</span>
                    <span class="nav-right">
                        <i class="fas fa-chevron-right arrow"></i>
                    </span>
                </a>

                <a href="{{ route('crm.reports.index') }}"
                    class="nav-item {{ request()->routeIs('crm.reports.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-pie"></i>
                    <span class="nav-label">Reports</span>
                    <span class="nav-right">
                        <i class="fas fa-chevron-right arrow"></i>
                    </span>
                </a>

                <a href="{{ route('crm.logs.index') }}"
                    class="nav-item {{ request()->routeIs('crm.logs.*') ? 'active' : '' }}">
                    <i class="fas fa-history"></i>
                    <span class="nav-label">Logs</span>
                    <span class="nav-right">
                        <i class="fas fa-chevron-right arrow"></i>
                    </span>
                </a>
            @endif

            @if($__navUser->isAdmin() || $__navUser->isSuperAdmin() || $__navUser->isAccounts())
                <a href="{{ route('crm.deletion_logs.index') }}"
                    class="nav-item {{ request()->routeIs('crm.deletion_logs.*') ? 'active' : '' }}">
                    <i class="fas fa-trash-restore" style="margin-top:2px;"></i>
                    <span class="nav-label">Deletion Logs</span>
                    <span class="nav-right">
                        <i class="fas fa-chevron-right arrow"></i>
                    </span>
                </a>
            @endif

        </nav>

        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-avatar">
                    {{ strtoupper(substr(Auth::guard('crm')->user()->name, 0, 1)) }}
                </div>
                <div class="user-info">
                    <div class="user-name">{{ Auth::guard('crm')->user()->name }}</div>
                    <div class="user-role" style="font-weight: 700; color: #64748b;">{{ Auth::guard('crm')->user()->getRoleLabel() }}</div>
                </div>
                <form action="{{ route('crm.logout') }}" method="POST" class="logout-form">
                    {{ csrf_field() }}
                    <button type="submit" class="logout-btn">
                        <i class="fas fa-power-off"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT AREA -->
    <div class="main-area">
        <!-- TOP NAV -->
        <div class="top-bar">
            <div class="top-title" style="display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-bars menu-toggle" onclick="toggleSidebar()"></i>
                <div style="display:flex; align-items:baseline; gap:0.75rem;">
                    <h1>
                        @hasSection('title')
                            @yield('title')
                        @else
                            Dashboard
                        @endif
                    </h1>
                    <div style="display: flex; align-items: center; gap: 0.5rem; color:var(--text-gray);">
                        <span>{{ now()->format('l, F j, Y') }}</span>
                        <span id="live-time"
                            style="color: var(--primary-purple); font-weight: 700; background: var(--primary-soft); padding: 2px 8px; border-radius: 6px; font-size: 0.85rem;"></span>
                        @if(request()->is('crm/dashboard*'))
                            @php
                                $__dashboardWorkspaces = Auth::guard('crm')->user()->workspaces()
                                    ->where('crm_workspaces.is_active', true)->orderBy('name')->get();
                            @endphp
                            @if(Auth::guard('crm')->user()->isAdmin() && $__dashboardWorkspaces->count() > 1)
                                <form id="dashboardWorkspaceForm" method="POST" class="dashboard-workspace-switch">
                                    {{ csrf_field() }}
                                    <i class="fas fa-exchange-alt"></i>
                                    <span class="dashboard-workspace-label">Switch Project</span>
                                    <select aria-label="Switch CRM workspace" onchange="this.form.action='{{ url('/crm/select-workspace') }}/'+this.value; this.form.submit();">
                                        @foreach($__dashboardWorkspaces as $__workspaceOption)
                                            <option value="{{ $__workspaceOption->id }}" {{ $__workspaceOption->id === $activeCrmWorkspace->id ? 'selected' : '' }}>
                                                {{ $__workspaceOption->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
            <div style="margin-left: auto;">
                @yield('header_actions')
            </div>
        </div>

        @yield('content')
        <div style="height: 2rem; width: 100%;"></div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
        }

        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const timeElement = document.getElementById('live-time');
            if (timeElement) {
                timeElement.innerText = timeString;
            }
        }
        setInterval(updateTime, 1000);
        updateTime();

        // Automatically dismiss every CRM success/error banner after 2 seconds.
        document.querySelectorAll('.alert').forEach(function (alert) {
            setTimeout(function () {
                alert.classList.add('auto-dismiss');
                setTimeout(function () { alert.remove(); }, 400);
            }, 2000);
        });

        // AJAX Auto-Refresh for Listing Pages (Inbox, Spam, Leads, Logs, etc.)
        document.addEventListener('DOMContentLoaded', function () {
            const autoRefreshKeywords = ['inbox', 'spam', 'leads', 'logs', 'show-orders', 'team-performance'];
            const currentPath = window.location.pathname;
            const isListingPage = autoRefreshKeywords.some(keyword => currentPath.includes(keyword));

            if (isListingPage) {
                // Create a small status indicator
                const statusDiv = document.createElement('div');
                statusDiv.style.cssText = "position: fixed; bottom: 10px; right: 10px; font-size: 0.75rem; color: #94a3b8; background: white; padding: 4px 8px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); opacity: 0; transition: opacity 0.3s;";
                statusDiv.innerText = "Updated just now";
                document.body.appendChild(statusDiv);

                setInterval(() => {
                    // Don't refresh if user is interacting with filters or forms (checking if any input has focus)
                    if (document.activeElement.tagName === 'INPUT' || document.activeElement.tagName === 'SELECT' || document.querySelector('.sidebar-overlay.open')) {
                        return;
                    }

                    fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(response => response.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');

                            // Find the new content container (Table area)
                            const newContent = doc.querySelector('.content-card');
                            const currentContent = document.querySelector('.content-card');

                            if (newContent && currentContent) {
                                // Update HTML only if different (simple check, or just replace)
                                if (newContent.innerHTML !== currentContent.innerHTML) {
                                    currentContent.innerHTML = newContent.innerHTML;

                                    // Show "Updated" toast
                                    statusDiv.innerText = "New data loaded";
                                    statusDiv.style.opacity = '1';
                                    setTimeout(() => { statusDiv.style.opacity = '0'; }, 3000);
                                }
                            }
                        })
                        .catch(e => console.error('Auto-refresh failed', e));

                }, 15000); // Check every 15 seconds
            }
        });
        // Toast System
        function showToast(message, type = 'success') {
            let container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                document.body.appendChild(container);
            }
            const toast = document.createElement('div');
            toast.className = `premium-toast ${type}`;
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            toast.innerHTML = `<i class="fas ${icon}"></i> <span>${message}</span>`;

            container.appendChild(toast);
            setTimeout(() => toast.classList.add('show'), 10);

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 400);
            }, 2000);
        }

        // Custom Confirm System
        let confirmCallback = null;
        function customConfirm(title, text, callback, btnText = 'Yes, Delete', btnClass = 'btn-confirm') {
            document.getElementById('confirmTitle').innerText = title;
            document.getElementById('confirmText').innerText = text;
            const confirmBtn = document.getElementById('confirmBtn');
            confirmBtn.innerText = btnText;
            confirmBtn.className = 'confirm-btn ' + btnClass;
            confirmCallback = callback;
            document.getElementById('confirmOverlay').classList.add('show');
        }

        function closeConfirm(confirmed) {
            document.getElementById('confirmOverlay').classList.remove('show');
            if (confirmed && confirmCallback) {
                confirmCallback();
            }
            confirmCallback = null;
        }

        // Handle Laravel Session Messages with Toast
        @if(session('success'))
            showToast("{{ session('success') }}", 'success');
        @endif
        @if(session('error'))
            showToast("{{ session('error') }}", 'error');
        @endif

        // ── ONLINE HEARTBEAT ──────────────────────────────────
        function sendPing() {
            if (document.hidden) {
                return;
            }

            fetch("{{ route('crm.internal_chat.ping') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                }
            }).catch(() => {});
        }
        sendPing();
        setInterval(sendPing, 10000);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) {
                sendPing();
            }
        });
        // ─────────────────────────────────────────────────────

        // CRM partial navigation: keep the sidebar in place and swap the right pane.
        (function () {
            if (window.__crmPartialNavigationBound) {
                return;
            }
            window.__crmPartialNavigationBound = true;

            function isPartialNavLink(link) {
                if (!link || link.dataset.noAjaxNav !== undefined) return false;
                if (link.target && link.target !== '_self') return false;
                if (link.hasAttribute('download')) return false;
                if (link.closest('form')) return false;

                const url = new URL(link.href, window.location.href);
                if (url.origin !== window.location.origin) return false;
                if (!url.pathname.startsWith('/crm/')) return false;

                return true;
            }

            function setActiveNav(url) {
                const target = new URL(url, window.location.href);
                const items = Array.from(document.querySelectorAll('.custom-sidebar .nav-item'));
                let bestMatch = null;
                let bestLength = -1;

                items.forEach(item => {
                    const itemUrl = new URL(item.href, window.location.href);
                    const matches = itemUrl.pathname === target.pathname
                        || (target.pathname.startsWith(itemUrl.pathname + '/') && itemUrl.pathname !== '/crm/dashboard');
                    if (matches && itemUrl.pathname.length > bestLength) {
                        bestMatch = item;
                        bestLength = itemUrl.pathname.length;
                    }
                });
                items.forEach(item => item.classList.toggle('active', item === bestMatch));
            }

            function replacePageAssets(doc) {
                const nextStyle = doc.head ? doc.head.querySelector('style') : null;
                const currentStyle = document.head.querySelector('style');
                if (nextStyle && currentStyle) {
                    currentStyle.textContent = nextStyle.textContent;
                }
            }

            function runPageScripts(doc) {
                document.querySelectorAll('script[data-crm-page-script]').forEach(script => script.remove());

                // Only execute scripts that belong to the newly loaded page content.
                const nextMain = doc.querySelector('.main-area');
                let inlineScripts = nextMain
                    ? Array.from(nextMain.querySelectorAll('script:not([src])'))
                    : [];

                const nextScriptsContainer = doc.querySelector('#crm-page-scripts');
                if (nextScriptsContainer) {
                    inlineScripts = inlineScripts.concat(Array.from(nextScriptsContainer.querySelectorAll('script:not([src])')));
                }

                inlineScripts.forEach(sourceScript => {
                    const script = document.createElement('script');
                    script.dataset.crmPageScript = '1';
                    script.textContent = sourceScript.textContent;
                    document.body.appendChild(script);
                });

                document.dispatchEvent(new CustomEvent('crm:page-loaded'));
            }

            function updateMainAreaFromHtml(html, url, pushState) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const nextMain = doc.querySelector('.main-area');
                const currentMain = document.querySelector('.main-area');

                if (!nextMain || !currentMain) {
                    window.location.href = url;
                    return;
                }

                replacePageAssets(doc);
                currentMain.innerHTML = nextMain.innerHTML;
                currentMain.scrollTop = 0;
                currentMain.classList.remove('crm-loading');

                document.title = doc.title || document.title;
                setActiveNav(url);

                if (pushState) {
                    window.history.pushState({ crmPartial: true }, doc.title || '', url);
                }

                runPageScripts(doc);
            }

            function loadCrmPage(url, pushState) {
                const currentMain = document.querySelector('.main-area');
                if (currentMain) {
                    currentMain.classList.add('crm-loading');
                }

                fetch(url, {
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(response => {
                        if (!response.ok) throw new Error('Navigation failed');
                        return response.text();
                    })
                    .then(html => updateMainAreaFromHtml(html, url, pushState))
                    .catch(() => {
                        window.location.href = url;
                    });
            }

            document.addEventListener('click', function (event) {
                if (event.defaultPrevented) return;
                const link = event.target.closest('a[href]');
                if (!isPartialNavLink(link)) return;

                event.preventDefault();
                loadCrmPage(link.href, true);

                const sidebar = document.getElementById('sidebar');
                const overlay = document.querySelector('.sidebar-overlay');
                if (sidebar && overlay) {
                    sidebar.classList.remove('open');
                    overlay.classList.remove('open');
                }
            });

            window.addEventListener('popstate', function () {
                loadCrmPage(window.location.href, false);
            });
        })();
    </script>

    <!-- DateRangePicker Dependencies (Order is important) -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>



    <!-- GLOBAL OVERLAYS -->
    <div class="toast-container" id="toast-container"></div>

    <div class="confirm-overlay" id="confirmOverlay">
        <div class="confirm-modal">
            <div class="confirm-icon"><i class="fas fa-trash-alt"></i></div>
            <div class="confirm-title" id="confirmTitle">Confirm Action</div>
            <div class="confirm-text" id="confirmText">Are you sure you want to proceed?</div>
            <div class="confirm-btns">
                <button class="confirm-btn btn-cancel" onclick="closeConfirm(false)">Cancel</button>
                <button class="confirm-btn btn-confirm" id="confirmBtn" onclick="closeConfirm(true)">Yes</button>
            </div>
        </div>
    </div>

    <div id="crm-page-scripts">
        @yield('scripts')
    </div>
    <script>
        (function () {
            function clearDefaultNumberZeros(root) {
                var scope = root && root.querySelectorAll ? root : document;
                var fields = [];

                if (scope.matches && scope.matches('input[type="number"]')) {
                    fields.push(scope);
                }

                fields = fields.concat(Array.prototype.slice.call(
                    scope.querySelectorAll('input[type="number"]')
                ));

                fields.forEach(function (field) {
                    if (!field.hasAttribute('data-keep-zero') && field.value.trim() === '0') {
                        field.value = '';
                    }
                });
            }

            function initializeNumberFields() {
                clearDefaultNumberZeros(document);

                if (!document.body || window.__crmNumberFieldObserver) return;

                window.__crmNumberFieldObserver = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        Array.prototype.forEach.call(mutation.addedNodes, function (node) {
                            if (node.nodeType === 1) clearDefaultNumberZeros(node);
                        });
                    });
                });
                window.__crmNumberFieldObserver.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeNumberFields);
            } else {
                initializeNumberFields();
            }
            document.addEventListener('crm:page-loaded', function () {
                clearDefaultNumberZeros(document.querySelector('.main-area') || document);
            });
        })();
    </script>
</body>

</html>
