@extends('crm.layout')

@section('title', 'Email Details')

@section('content')
    @php
        $currentCrmUser = Auth::guard('crm')->user();
        $salesOrderQuantityOptions = is_array($email->estimate_quantity_options)
            ? array_values($email->estimate_quantity_options)
            : [];
        $salesOrderCurrency = $email->invoice_currency ?: 'USD';

        if (empty($salesOrderQuantityOptions) && !empty($latestOrderEstimate)) {
            $salesOrderQuantityOptions = $latestOrderEstimate->options->map(function ($option) {
                $approvedTotal = $option->offer_price !== null
                    ? (float) $option->offer_price
                    : ($option->discounted_price !== null ? (float) $option->discounted_price : (float) $option->total_price);
                return [
                    'quantity' => (int) $option->quantity,
                    'price' => $approvedTotal,
                    'unit_price' => $option->quantity > 0 ? $approvedTotal / $option->quantity : 0,
                ];
            })->values()->all();
        }

        $hasOrderReadyEstimate = $email->estimate_status === 'approved' || !empty($latestOrderEstimate);
        $canCreateSalesOrder = !$email->salesOrder
            && $hasOrderReadyEstimate
            && ($currentCrmUser->isAdmin()
                || $currentCrmUser->isSalesManager()
                || ($currentCrmUser->isSales() && (int) $email->assigned_to === (int) $currentCrmUser->id));
    @endphp
    <style>
        :root {
            --primary-color: var(--primary-purple);
            /* Indigo 600 */
            --primary-light: #eef2ff;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-color);
        }

        .back-nav {
            margin-bottom: 1.5rem;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            background: white;
            border: 1px solid var(--border-color);
            color: var(--secondary-color);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: var(--shadow-sm);
        }

        .btn-back:hover {
            color: #1e293b;
            border-color: #cbd5e1;
        }

        .main-container {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 2rem;
            align-items: start;
        }

        /* Cards */
        .crm-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: box-shadow 0.2s;
        }

        .crm-card:hover {
            box-shadow: var(--shadow-md);
        }

        .card-header-title {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #94a3b8;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 0.75rem;
        }

        /* Info Items */
        .info-item {
            margin-bottom: 1.25rem;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 0.35rem;
            display: block;
        }

        .value {
            font-size: 0.95rem;
            font-weight: 600;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            word-break: break-word;
        }

        .value a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .value a:hover {
            text-decoration: underline;
        }

        .icon-box {
            width: 28px;
            height: 28px;
            background: #f1f5f9;
            color: #64748b;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        /* Header Section */
        .email-header {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
        }

        .email-subject {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            line-height: 1.2;
        }

        .email-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 0.5rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 0.35rem 0.85rem;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .badge-verified {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .badge-spam {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Message Content */
        .message-container {
            position: relative;
        }

        .product-highlight {
            background: linear-gradient(to right, #eef2ff, #fff);
            border-left: 4px solid var(--primary-color);
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .product-highlight-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--primary-purple);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }

        .product-highlight-value {
            font-size: 1.25rem;
            font-weight: 800;
            color: #1e1b4b;
        }

        .message-body {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            font-size: 1rem;
            line-height: 1.7;
            color: #334155;
            white-space: pre-line;
        }

        /* Action Buttons */
        .actions-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .email-header-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid #eef2f7;
        }

        .actions-group form {
            margin: 0;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            min-height: 38px;
            padding: 0.5rem 0.8rem;
            border-radius: 9px;
            font-weight: 700;
            font-size: 0.85rem;
            line-height: 1;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background-color 0.18s, border-color 0.18s, color 0.18s, transform 0.18s, box-shadow 0.18s;
            text-decoration: none;
            white-space: nowrap;
        }

        .btn-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.08);
        }

        .btn-action i {
            font-size: 0.9rem;
        }

        .btn-no-spam {
            background: #dcfce7;
            color: #166534;
        }

        .btn-no-spam:hover {
            background: #bbf7d0;
        }

        .btn-spam {
            background: #fff;
            color: #dc2626;
            border-color: #fecaca;
        }

        .btn-spam:hover {
            background: #fef2f2;
            border-color: #fca5a5;
        }

        .btn-rejected {
            background: #fff;
            color: #c2410c;
            border-color: #fed7aa;
        }

        .btn-rejected:hover {
            background: #fff7ed;
            border-color: #fdba74;
        }

        .btn-create-inquiry {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
        }

        .btn-create-inquiry:hover {
            color: #fff;
            filter: brightness(0.94);
        }

        .btn-qualified {
            background: #059669;
            color: #fff;
            border-color: #059669;
        }

        .btn-qualified:hover { background: #047857; border-color: #047857; }

        .btn-assign {
            background: var(--primary-purple);
            color: #fff;
            border-color: var(--primary-purple);
        }

        .btn-assign:hover { background: var(--primary-purple); border-color: var(--primary-purple); }

        .btn-delete {
            background: #ef4444;
            color: white;
            width: 40px;
            padding: 0.55rem;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .chat-input-container.reply-drag-active {
            border-color: var(--primary-purple) !important;
            background: #eef2ff !important;
            box-shadow: 0 0 0 4px rgba(var(--primary-rgb), 0.12);
        }

        .reply-drop-overlay {
            position: absolute;
            inset: 0;
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 8px;
            border: 2px dashed var(--primary-purple);
            border-radius: 12px;
            background: rgba(238, 242, 255, .96);
            color: var(--primary-purple);
            font-weight: 800;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity .15s ease;
        }

        .reply-drop-overlay.active { opacity: 1; visibility: visible; pointer-events: auto; }

        .attachment-chip {
            display: flex;
            align-items: center;
            gap: 9px;
            max-width: 260px;
            padding: 8px 9px 8px 11px;
            background: #fff;
            border: 1px solid #dbe3ef;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(15, 23, 42, .06);
        }

        .attachment-chip-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .8rem; font-weight: 700; color: #334155; }
        .attachment-chip-open { display:flex; min-width:0; flex:1; align-items:center; gap:9px; color:inherit; text-decoration:none; cursor:pointer; }
        .attachment-chip-open:hover .attachment-chip-name { color:var(--primary-purple); text-decoration:underline; }
        .attachment-chip-preview { width: 34px; height: 34px; flex: 0 0 34px; object-fit: cover; border-radius: 7px; border: 1px solid #e2e8f0; background: #fff; }
        .attachment-chip-remove { flex: 0 0 26px; width: 26px; height: 26px; padding: 0; border: none; border-radius: 7px; background: #fef2f2; color: #dc2626; cursor: pointer; }
        .attachment-chip-remove:hover { background: #fee2e2; }

        /* Attachment */
        .attachment-section {
            margin-top: 2rem;
        }

        .attachment-preview {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
            transition: all 0.2s;
        }

        .attachment-preview:hover {
            border-color: var(--primary-color);
            background: #f1f5f9;
        }

        .attachment-preview img {
            max-width: 100%;
            max-height: 500px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            cursor: zoom-in;
        }

        @media (max-width: 1024px) {
            .main-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .email-header {
                padding: 1.5rem;
            }

            .email-header>div:first-child {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 1rem;
            }

            .actions-group {
                width: 100%;
                justify-content: flex-start;
                flex-wrap: wrap;
            }

            .email-header-bottom {
                flex-direction: column;
                align-items: flex-start;
            }

            .email-subject {
                font-size: 1.25rem;
            }
        }

        @media (max-width: 640px) {
            .crm-card {
                padding: 1rem;
            }

            .email-header {
                padding: 1rem;
            }

            .actions-group .btn-action {
                flex: 1;
                justify-content: center;
                min-width: 120px;
            }

            .email-header-bottom .actions-group {
                display: grid;
                grid-template-columns: 1fr 1fr;
            }

            .email-header-bottom .actions-group form,
            .email-header-bottom .actions-group .btn-action {
                width: 100%;
            }

            .email-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .chat-footer-top {
                flex-direction: column !important;
            }

            .chat-footer-top label {
                width: 100%;
                text-align: center;
            }

            .chat-footer-bottom {
                flex-direction: column !important;
                gap: 1rem;
                align-items: stretch !important;
            }

            .chat-footer-bottom button {
                width: 100%;
            }

            .product-highlight {
                padding: 0.75rem 1rem;
            }

            .product-highlight img {
                height: 60px;
                width: 60px;
            }

            .product-highlight-value {
                font-size: 1rem;
            }
        }

        /* PREMIUM CHAT ENHANCEMENTS */
        .chat-card {
            border: none !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
            overflow: hidden;
        }

        .chat-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%) !important;
            padding: 1.25rem 1.5rem !important;
            border-bottom: 1px solid #e2e8f0 !important;
        }

        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #10b981;
            background: #ecfdf5;
            padding: 4px 10px;
            border-radius: 99px;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            animation: pulse-green 2s infinite;
        }

        @keyframes pulse-green {
            0% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }

            70% {
                box-shadow: 0 0 0 6px rgba(16, 185, 129, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        .msg-bubble-admin {
            background: linear-gradient(135deg, var(--primary-purple) 0%, var(--primary-purple) 100%) !important;
            color: white !important;
            box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.15) !important;
            border-bottom-right-radius: 4px !important;
            border-top-right-radius: 16px !important;
            border-top-left-radius: 16px !important;
            border-bottom-left-radius: 16px !important;
        }

        .msg-bubble-client {
            background: #ffffff !important;
            color: #1e293b !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
            border: 1px solid #e2e8f0 !important;
            border-bottom-left-radius: 4px !important;
            border-top-right-radius: 16px !important;
            border-top-left-radius: 16px !important;
            border-bottom-right-radius: 16px !important;
        }

        .msg-bubble-admin p, .msg-bubble-client p { margin-top: 0; margin-bottom: 0; }
        .msg-bubble-admin > *:first-child, .msg-bubble-client > *:first-child { margin-top: 0; }
        .msg-bubble-admin > *:last-child, .msg-bubble-client > *:last-child { margin-bottom: 0; }

        .chat-input-container {
            background: white !important;
            border: 1px solid #e2e8f0 !important;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05) !important;
            transition: all 0.2s ease;
            padding: 0.75rem 1rem !important;
        }

        .chat-input-container:focus-within {
            border-color: var(--primary-purple) !important;
            box-shadow: 0 10px 15px -3px rgba(var(--primary-rgb), 0.1) !important;
        }

        .send-btn-enhanced {
            background: var(--primary-purple) !important;
            width: 44px !important;
            height: 44px !important;
            padding: 0 !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .send-btn-enhanced:hover {
            transform: scale(1.05) rotate(-5deg) !important;
            background: var(--primary-purple) !important;
            box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.3) !important;
        }

        .msg-time {
            font-size: 0.65rem !important;
            font-weight: 500 !important;
            color: inherit !important;
            opacity: 0.7 !important;
            margin-top: 4px !important;
        }

        .msg-sender {
            font-weight: 600 !important;
            font-size: 0.7rem !important;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            margin-bottom: 2px;
            opacity: 0.9;
        }

        /* CUSTOM DROPDOWN PROFESSIONAL STYLES */
        .custom-dropdown {
            position: relative;
            width: 100%;
        }

        .dropdown-trigger {
            padding: 0.85rem 1rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            font-size: 0.95rem;
            color: #1e293b;
            transition: all 0.2s;
        }

        .dropdown-trigger:hover {
            border-color: #cbd5e1;
            background: #f1f5f9;
        }

        .dropdown-trigger.active {
            border-color: var(--primary-purple);
            background: white;
            box-shadow: 0 0 0 3px rgba(var(--primary-rgb), 0.1);
        }

        .dropdown-options {
            position: absolute;
            top: calc(100% + 5px);
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            z-index: 10000;
            display: none;
            max-height: 250px;
            overflow-y: auto;
            animation: dropFade 0.2s ease;
        }

        @keyframes dropFade {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .dropdown-option {
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 1px solid #f8fafc;
        }

        .dropdown-option:last-child {
            border-bottom: none;
        }

        .dropdown-option:hover {
            background: var(--primary-soft);
            padding-left: 1.25rem;
        }

        .role-badge-mini {
            font-size: 0.6rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .badge-sales-mini {
            background: #dcfce7;
            color: #166534;
        }

        .badge-manager-mini {
            background: #fce7f3;
            color: #9d174d;
        }

        .badge-admin-mini {
            background: var(--primary-soft);
            color: var(--primary-purple);
        }

        .agent-name-mini {
            font-weight: 600;
            color: #334155;
            font-size: 0.9rem;
        }

        /* Premium conversation workspace */
        .chat-card {
            --chat-primary: var(--primary-purple);
            --chat-primary-hover: var(--primary-hover);
            --chat-primary-soft: var(--primary-soft);
            border: 1px solid #e5eaf1 !important;
            border-radius: 20px !important;
            box-shadow: 0 18px 46px rgba(31, 41, 55, .08) !important;
            background: #fff;
        }

        .chat-header {
            min-height: 76px;
            padding: 1rem 1.25rem !important;
            background: #fff !important;
            border-bottom: 1px solid #edf0f4 !important;
        }

        .chat-avatar {
            width: 42px;
            height: 42px;
            display: grid;
            flex: 0 0 42px;
            place-items: center;
            border-radius: 13px;
            color: var(--chat-primary);
            background: var(--chat-primary-soft);
            font-size: 1.05rem;
        }

        .chat-title {
            color: #172033;
            font-size: 1rem;
            font-weight: 850;
            line-height: 1.1;
        }

        .chat-client-name {
            margin-top: 5px;
            color: #718096;
            font-size: .78rem;
            font-weight: 600;
        }

        .live-indicator {
            color: #087f5b;
            background: #eafaf4;
            border: 1px solid #cff4e5;
            font-size: .68rem;
            font-weight: 800;
        }

        #chat-history {
            min-height: 380px;
            max-height: 560px !important;
            padding: 1.25rem !important;
            background: #f7f8fa !important;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }

        .chat-date-separator {
            width: 100%;
            margin: .55rem 0 1.1rem;
            text-align: center;
        }

        .chat-date-separator span {
            display: inline-flex;
            padding: .3rem .7rem;
            border: 1px solid #e5eaf1;
            border-radius: 999px;
            background: rgba(255,255,255,.92);
            color: #7b8798;
            font-size: .66rem;
            font-weight: 750;
            box-shadow: 0 2px 7px rgba(15,23,42,.035);
        }

        .chat-message {
            width: 100%;
            margin-bottom: 1rem !important;
        }

        .msg-bubble-admin,
        .msg-bubble-client {
            max-width: min(70%, 680px) !important;
            padding: .72rem .92rem !important;
            border-radius: 14px !important;
            font-size: .9rem;
            line-height: 1.5;
            overflow-wrap: anywhere;
        }

        .msg-bubble-admin {
            color: #273449 !important;
            background: linear-gradient(
                135deg,
                color-mix(in srgb, var(--chat-primary) 20%, #fff) 0%,
                color-mix(in srgb, var(--chat-primary) 34%, #fff) 100%
            ) !important;
            border: 1px solid color-mix(in srgb, var(--chat-primary) 22%, #fff) !important;
            border-bottom-right-radius: 5px !important;
            box-shadow: 0 8px 20px color-mix(in srgb, var(--primary-shadow) 60%, transparent) !important;
        }

        .msg-bubble-client {
            color: #273449 !important;
            background: #fff !important;
            border: 1px solid #e1e7ef !important;
            border-bottom-left-radius: 5px !important;
            box-shadow: 0 5px 16px rgba(15,23,42,.055) !important;
        }

        .chat-attachments {
            display: flex;
            max-width: min(70%, 680px) !important;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 5px !important;
        }

        .chat-file-card {
            display: flex !important;
            max-width: 360px;
            min-height: 48px;
            padding: 7px 10px !important;
            border-radius: 11px !important;
            font-size: .77rem !important;
            font-weight: 700 !important;
            overflow-wrap: anywhere;
            box-shadow: 0 5px 14px rgba(15,23,42,.07) !important;
        }

        .chat-file-card.is-admin {
            color: #28364c !important;
            background: #fff !important;
            border-color: #e0e6ee !important;
        }

        .chat-file-icon {
            width: 30px !important;
            height: 30px !important;
            flex: 0 0 30px;
            color: var(--chat-primary) !important;
            background: var(--chat-primary-soft) !important;
        }

        .chat-image-card img {
            width: auto;
            max-width: min(320px, 100%) !important;
            max-height: 300px !important;
            object-fit: cover;
        }

        .chat-message-meta {
            margin-top: 5px !important;
            color: #8793a5 !important;
            font-size: .67rem !important;
            font-weight: 600;
        }

        .chat-composer {
            position: sticky;
            bottom: 0;
            z-index: 10;
            padding: 1rem 1.25rem !important;
            background: rgba(255,255,255,.97) !important;
            border-top: 1px solid #e8edf3 !important;
            border-radius: 0 0 20px 20px !important;
            backdrop-filter: blur(12px);
        }

        .chat-input-container {
            padding: .55rem !important;
            border: 1px solid #dce3ec !important;
            border-radius: 14px !important;
            box-shadow: 0 5px 16px rgba(15,23,42,.045) !important;
        }

        .chat-input-container:focus-within {
            border-color: var(--chat-primary) !important;
            box-shadow: 0 0 0 3px var(--primary-shadow), 0 7px 20px rgba(15,23,42,.06) !important;
        }

        .chat-composer-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .55rem .2rem .05rem;
            border-top: 1px solid #edf0f4;
        }

        .chat-attach-button {
            padding: .45rem .65rem !important;
            color: #5d697b !important;
            background: transparent !important;
            border: 1px solid transparent !important;
        }

        .chat-attach-button:hover {
            color: var(--chat-primary) !important;
            background: var(--chat-primary-soft) !important;
        }

        .chat-send-button {
            min-height: 40px;
            padding: .6rem 1rem !important;
            border-radius: 10px !important;
            background: var(--chat-primary) !important;
            box-shadow: 0 7px 16px var(--primary-shadow);
        }

        .chat-send-button:hover {
            background: var(--chat-primary-hover) !important;
            transform: translateY(-1px);
        }

        .chat-recipient-note {
            margin-top: 9px !important;
            color: #8793a5 !important;
            font-size: .69rem !important;
        }

        .chat-card .cke_chrome {
            border: 0 !important;
            box-shadow: none !important;
        }

        .chat-card .cke_top {
            padding: 5px 6px !important;
            border: 0 !important;
            border-bottom: 1px solid #edf0f4 !important;
            background: #fbfcfd !important;
        }

        .chat-card .cke_bottom {
            display: none !important;
        }

        .chat-card .cke_contents {
            min-height: 104px !important;
        }

        @media (max-width: 760px) {
            .chat-header { padding: .9rem 1rem !important; }
            #chat-history { min-height: 320px; padding: 1rem !important; }
            .msg-bubble-admin, .msg-bubble-client, .chat-attachments { max-width: 88% !important; }
            .chat-composer { padding: .8rem !important; }
            .chat-send-button span { display: none; }
            .chat-send-button { width: 40px; padding: 0 !important; justify-content: center; }
            .live-indicator { padding: 4px 7px; }
        }

        .sales-order-overlay{background:rgba(15,23,42,.52)!important;backdrop-filter:blur(5px)}
        .sales-order-dialog{position:relative;overflow:hidden;max-width:540px!important;padding:0!important;border:1px solid color-mix(in srgb,var(--primary-purple) 18%,#fff);border-radius:20px!important;box-shadow:0 28px 70px rgba(15,23,42,.24)!important}
        .sales-order-dialog:before{content:"";position:absolute;inset:0 0 auto;height:5px;background:linear-gradient(90deg,var(--primary-purple),color-mix(in srgb,var(--primary-purple) 55%,#ffb28c))}
        .sales-order-head{margin:0!important;padding:1.55rem 1.75rem 1.15rem;background:linear-gradient(135deg,#fff,var(--primary-soft))}
        .sales-order-head h3{display:flex;align-items:center;gap:.65rem;color:#172033!important;font-size:1.3rem!important}
        .sales-order-head h3:before{content:"\f570";display:grid;width:38px;height:38px;place-items:center;border-radius:11px;background:var(--primary-purple);color:#fff;font-family:"Font Awesome 5 Free";font-size:.9rem;font-weight:900;box-shadow:0 7px 16px var(--primary-shadow)}
        .sales-order-close{display:grid!important;width:36px;height:36px;place-items:center;border-radius:10px;background:#fff;color:#8391a5!important;transition:.2s}
        .sales-order-close:hover{color:var(--primary-purple)!important;box-shadow:0 5px 14px rgba(15,23,42,.1);transform:rotate(90deg)}
        .sales-order-form{padding:1.4rem 1.75rem 1.75rem}
        .sales-order-field{margin-bottom:1rem!important}
        .sales-order-field label{color:#334155!important;font-size:.76rem!important;font-weight:800!important;letter-spacing:.015em}
        .sales-order-select{min-height:48px;padding:.75rem .9rem!important;border:1.5px solid #d8e0ea!important;border-radius:11px!important;background:#fbfcfe!important;color:#1e293b;font-family:inherit;font-size:.86rem!important;font-weight:650;transition:.2s}
        .sales-order-select:hover{border-color:color-mix(in srgb,var(--primary-purple) 35%,#d8e0ea)!important}
        .sales-order-select:focus{border-color:var(--primary-purple)!important;background:#fff!important;box-shadow:0 0 0 3px var(--primary-shadow)!important}
        .sales-order-submit{min-height:50px;margin-top:.35rem;padding:.85rem 1rem!important;border-radius:12px!important;background:linear-gradient(135deg,var(--primary-purple),var(--primary-hover))!important;box-shadow:0 10px 22px var(--primary-shadow)!important;font-size:.9rem!important;transition:.2s}
        .sales-order-submit:hover{transform:translateY(-1px);filter:brightness(.98);box-shadow:0 14px 28px var(--primary-shadow)!important}
        @media(max-width:600px){.sales-order-dialog{width:94%!important}.sales-order-head{padding:1.25rem 1.15rem 1rem}.sales-order-form{padding:1.15rem}.sales-order-head h3{font-size:1.1rem!important}}
    </style>

    <div class="back-nav">
        @if(request()->query('context') === 'quotes')
        <a href="{{ route('crm.leads.index') }}" class="btn-back">
            <i class="fas fa-arrow-left" style="margin-right: 8px;"></i> Back to Quotes
        </a>
        @else
        <a href="{{ route('crm.emails.index') }}" class="btn-back">
            <i class="fas fa-arrow-left" style="margin-right: 8px;"></i> Back to Inbox
        </a>
        @endif
    </div>

    <!-- Header Card -->
    @if(!Auth::guard('crm')->user()->isEstimator())
    <div class="email-header">
        <div>
            <h1 class="email-subject">{{ $email->subject ?: 'General Inquiry' }}</h1>
        </div>
        <div class="email-header-bottom">
            <div class="email-meta" style="margin-top:0;">
                <span><i class="far fa-calendar-alt"></i> {{ $email->created_at->format('F d, Y') }}</span>
                <span><i class="far fa-clock"></i> {{ $email->created_at->format('h:i A') }}</span>
                @if($email->is_spam)
                    <span class="status-badge badge-spam">Possibly Spam ({{ $email->spam_reason }})</span>
                @else
                    <span class="status-badge badge-verified"><i class="fas fa-shield-check"></i> Verified Inquiry</span>
                @endif
            </div>
            <div class="actions-group">
                @if($email->is_spam)
                    <form action="{{ route('crm.emails.markValid', $email->id) }}" method="POST">
                        {{ csrf_field() }}
                        <button type="submit" class="btn-action btn-no-spam"><i class="fas fa-inbox"></i> Move to Inbox</button>
                    </form>
                @else
                    <form action="{{ route('crm.emails.markSpam', $email->id) }}" method="POST">
                        {{ csrf_field() }}
                        <button type="submit" class="btn-action btn-spam"><i class="fas fa-ban"></i> Mark as Spam</button>
                    </form>
                @endif

                @if(Auth::guard('crm')->user()->isAdmin() || Auth::guard('crm')->user()->isSalesManager() || Auth::guard('crm')->user()->isSales())
                    <a href="{{ route('crm.emails.create_form', ['source_email' => $email->id]) }}" class="btn-action btn-create-inquiry">
                        <i class="fas fa-plus-circle"></i> Create Inquiry
                    </a>
                @endif

                @if(!$email->salesOrder && !$email->is_rejected)
                    <button type="button" class="btn-action btn-rejected" onclick="openRejectLeadModal()"><i class="fas fa-times-circle"></i> Reject Lead</button>
                @endif

                @if(Auth::guard('crm')->user()->canAssign())
                    <button type="button" onclick="openAssignModal()" class="btn-action btn-assign">
                        <i class="fas fa-user-plus"></i> Assign
                        @if($email->assigned_to)
                            <span id="assignBadgeBtn"
                                style="background: rgba(255,255,255,0.25); padding: 1px 8px; border-radius: 99px; font-size: 0.75rem;">
                                {{ \App\CrmUser::find($email->assigned_to)->name ?? '' }}
                            </span>
                        @endif
                    </button>
                @endif

                @if(Auth::guard('crm')->user()->isAdmin())
                    <form action="{{ route('crm.emails.destroy', $email->id) }}" method="POST"
                        onsubmit="return confirm('Delete this inquiry?');">
                        {{ csrf_field() }}
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn-action btn-delete" title="Delete"><i
                                class="fas fa-trash-alt"></i></button>
                    </form>
                @endif
            </div>
        </div>
    </div>
    @else
    <div class="email-header" style="padding: 1.5rem 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <h1 class="email-subject">{{ $email->subject ?: 'General Inquiry' }}</h1>
            <div class="email-meta">
                <span><i class="far fa-calendar-alt"></i> {{ $email->created_at->format('F d, Y') }}</span>
                <span><i class="far fa-clock"></i> {{ $email->created_at->format('h:i A') }}</span>
            </div>
        </div>
    </div>
    @endif

    <div id="rejectLeadModal" style="display:none; position:fixed; inset:0; z-index:100000; background:rgba(15,23,42,.55); padding:20px; align-items:center; justify-content:center; backdrop-filter:blur(2px);">
        <form action="{{ route('crm.emails.markRejected', $email->id) }}" method="POST" style="width:100%; max-width:480px; background:#fff; border-radius:16px; box-shadow:0 24px 60px rgba(15,23,42,.25); overflow:hidden;">
            {{ csrf_field() }}
            <div style="padding:20px 22px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between;">
                <div><div style="font-size:1.05rem; font-weight:800; color:#0f172a;">Reject Lead</div><div style="font-size:.82rem; color:#64748b; margin-top:3px;">Please add a reason for rejecting this lead.</div></div>
                <button type="button" onclick="closeRejectLeadModal()" style="border:none; background:#f1f5f9; color:#64748b; width:32px; height:32px; border-radius:8px; cursor:pointer;"><i class="fas fa-times"></i></button>
            </div>
            <div style="padding:22px;">
                <label for="rejectionNote" style="display:block; font-size:.85rem; font-weight:700; color:#334155; margin-bottom:8px;">Rejection note <span style="color:#dc2626;">*</span></label>
                <textarea id="rejectionNote" name="rejection_note" rows="5" maxlength="1000" required placeholder="e.g. Customer is not interested, invalid requirements, budget issue..." style="width:100%; box-sizing:border-box; resize:vertical; border:1px solid #cbd5e1; border-radius:10px; padding:12px; font:inherit; color:#0f172a; outline:none;"></textarea>
            </div>
            <div style="padding:16px 22px; background:#f8fafc; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="closeRejectLeadModal()" class="btn-action" style="background:#fff; color:#475569; border-color:#cbd5e1;">Cancel</button>
                <button type="submit" class="btn-action btn-rejected" style="background:#c2410c; color:#fff; border-color:#c2410c;"><i class="fas fa-times-circle"></i> Confirm Rejection</button>
            </div>
        </form>
    </div>
    <script>
        function openRejectLeadModal() {
            const modal = document.getElementById('rejectLeadModal');
            modal.style.display = 'flex';
            setTimeout(() => document.getElementById('rejectionNote').focus(), 50);
        }
        function closeRejectLeadModal() {
            document.getElementById('rejectLeadModal').style.display = 'none';
        }
    </script>

    <!-- Forward Modal -->
    <div id="forwardModal"
        style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div
            style="background: white; width: 100%; max-width: 500px; border-radius: 16px; padding: 2rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2); animation: slideIn 0.3s ease;">
            <h3 style="margin-top: 0; margin-bottom: 0.5rem; font-size: 1.25rem;">Forward Inquiry</h3>
            <p style="color: #64748b; margin-top: 0; margin-bottom: 1.5rem; font-size: 0.95rem;">Enter the email address to
                forward this inquiry details.</p>

            <form action="{{ route('crm.emails.forward', $email->id) }}" method="POST">
                {{ csrf_field() }}
                <div style="margin-bottom: 1.5rem;">
                    <label
                        style="display: block; font-weight: 600; font-size: 0.9rem; margin-bottom: 0.5rem; color: #334155;">Recipient
                        Email</label>
                    <div style="position: relative;">
                        <i class="fas fa-envelope"
                            style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                        <input type="email" name="forward_email" required placeholder="recipient@example.com"
                            style="width: 100%; padding: 0.75rem 1rem 0.75rem 2.75rem; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 1rem; outline: none; transition: border 0.2s;"
                            onfocus="this.style.borderColor = '#3b82f6'" onblur="this.style.borderColor = '#cbd5e1'">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem;">
                    <button type="button" onclick="closeForwardModal()"
                        style="padding: 0.6rem 1.2rem; background: white; border: 1px solid #cbd5e1; border-radius: 8px; color: #64748b; font-weight: 600; cursor: pointer;">Cancel</button>
                    <button type="submit"
                        style="padding: 0.6rem 1.5rem; background: #3b82f6; border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                        Send <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openForwardModal() {
            document.getElementById('forwardModal').style.display = 'flex';
        }
        function closeForwardModal() {
            document.getElementById('forwardModal').style.display = 'none';
        }
        // Close modal on outside click
        document.getElementById('forwardModal').addEventListener('click', function (e) {
            if (e.target === this) closeForwardModal();
        });
    </script>

    <style>
        @keyframes slideIn {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>

    <div class="main-container" style="{{ Auth::guard('crm')->user()->isEstimator() ? 'grid-template-columns: 1fr;' : '' }}">

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Client Info -->
            @if(!Auth::guard('crm')->user()->isEstimator())
            <div class="crm-card">
                <div class="card-header-title"><i class="fas fa-user-circle"></i> Client Information</div>

                <div class="info-item">
                    <span class="label">Name</span>
                    <div class="value">
                        <div class="icon-box"><i class="fas fa-user"></i></div>
                        {{ $email->client_name }}
                    </div>
                </div>

                <div class="info-item">
                    <span class="label">Email</span>
                    <div class="value">
                        <div class="icon-box"><i class="fas fa-envelope"></i></div>
                        <a href="mailto:{{ $email->client_email }}">{{ $email->client_email }}</a>
                    </div>
                </div>

                <div class="info-item">
                    <span class="label">Phone</span>
                    <div class="value">
                        <div class="icon-box"><i class="fas fa-phone"></i></div>
                        {{ $email->client_phone ?: 'Not Provided' }}
                    </div>
                </div>

                <div class="info-item">
                    <span class="label">Source</span>
                    <div class="value">
                        <div class="icon-box"><i class="fas fa-bullseye"></i></div>
                        <span style="text-transform: capitalize;">{{ str_replace('_', ' ', strtolower($email->source) === 'form' ? 'website' : ($email->source ?: 'website')) }}</span>
                    </div>
                </div>

                <div class="info-item">
                    <span class="label">Social Media</span>
                    <div class="value" style="flex-direction: column; align-items: start; gap: 0.5rem;">
                        @if($email->linkedin_url)
                            <a href="{{ strpos($email->linkedin_url, 'http') === 0 ? $email->linkedin_url : 'https://' . $email->linkedin_url }}"
                                target="_blank"
                                style="color: #0077b5; display: flex; align-items: center; gap: 6px; font-weight: 500;">
                                <i class="fab fa-linkedin"></i> LinkedIn Profile
                            </a>
                        @endif
                        @if($email->twitter_url)
                            <a href="{{ strpos($email->twitter_url, 'http') === 0 ? $email->twitter_url : 'https://twitter.com/' . $email->twitter_url }}"
                                target="_blank"
                                style="color: #1da1f2; display: flex; align-items: center; gap: 6px; font-weight: 500;">
                                <i class="fab fa-twitter"></i> Twitter
                            </a>
                        @endif
                        @if($email->facebook_url)
                            <a href="{{ strpos($email->facebook_url, 'http') === 0 ? $email->facebook_url : 'https://' . $email->facebook_url }}"
                                target="_blank"
                                style="color: #1877f2; display: flex; align-items: center; gap: 6px; font-weight: 500;">
                                <i class="fab fa-facebook-f"></i> Facebook
                            </a>
                        @endif
                        @if($email->instagram_url)
                            <a href="{{ strpos($email->instagram_url, 'http') === 0 ? $email->instagram_url : 'https://' . $email->instagram_url }}"
                                target="_blank"
                                style="color: #e4405f; display: flex; align-items: center; gap: 6px; font-weight: 500;">
                                <i class="fab fa-instagram"></i> Instagram
                            </a>
                        @endif

                        @if(!$email->linkedin_url && !$email->twitter_url && !$email->facebook_url && !$email->instagram_url)
                            <div
                                style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 0.75rem; width: 100%; margin-top: 0.5rem;">
                                <span
                                    style="font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 0.5rem;">Quick
                                    Search (No API)</span>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                    @php
                                        $searchName = urlencode('"' . $email->client_name . '"');
                                        $searchEmail = urlencode($email->client_email);
                                        $searchDomain = urlencode(substr(strrchr($email->client_email, "@"), 1));
                                    @endphp
                                    <a href="https://www.google.com/search?q={{ $searchName }}+OR+{{ $searchEmail }}+site:linkedin.com"
                                        target="_blank" title="Search LinkedIn"
                                        style="width: 30px; height: 30px; background: #0077b5; color: white; border-radius: 6px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 0.8rem;"><i
                                            class="fab fa-linkedin-in"></i></a>
                                    <a href="https://www.google.com/search?q={{ $searchName }}+OR+{{ $searchEmail }}+site:facebook.com"
                                        target="_blank" title="Search Facebook"
                                        style="width: 30px; height: 30px; background: #1877f2; color: white; border-radius: 6px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 0.8rem;"><i
                                            class="fab fa-facebook-f"></i></a>
                                    <a href="https://www.google.com/search?q={{ $searchName }}+OR+{{ $searchEmail }}+site:instagram.com"
                                        target="_blank" title="Search Instagram"
                                        style="width: 30px; height: 30px; background: #e4405f; color: white; border-radius: 6px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 0.8rem;"><i
                                            class="fab fa-instagram"></i></a>
                                    <a href="https://www.google.com/search?q={{ $searchEmail }}" target="_blank"
                                        title="Search via Email"
                                        style="width: 30px; height: 30px; background: var(--primary-purple); color: white; border-radius: 6px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 0.8rem;"><i
                                            class="fas fa-at"></i></a>
                                    <a href="https://www.google.com/search?q={{ $searchName }}+{{ $searchDomain }}"
                                        target="_blank" title="Search Name + Company"
                                        style="width: 30px; height: 30px; background: #475569; color: white; border-radius: 6px; display: flex; align-items: center; justify-content: center; text-decoration: none; font-size: 0.8rem;"><i
                                            class="fas fa-search"></i></a>
                                </div>
                            </div>
                        @endif

                        <form action="{{ route('crm.emails.findSocial', $email->id) }}" method="POST"
                            style="margin-top: 5px; width: 100%;">
                            {{ csrf_field() }}
                            <button type="submit"
                                style="width: 100%; padding: 0.5rem; background: #eef2ff; color: var(--primary-purple); border: 1px solid #e0e7ff; border-radius: 6px; font-size: 0.75rem; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; transition: all 0.2s;">
                                <i class="fas fa-robot"></i>
                                {{ $email->social_investigated_at ? 'Retry Hunter.io API' : 'Find via Hunter.io API' }}
                            </button>
                        </form>
                    </div>
                </div>

                <div class="info-item">
                    <span class="label">IP Address</span>
                    <div class="value" style="font-size: 0.85rem; color: #000000ff;">
                        @php
                            $cName = $email->country ?: 'Unknown';
                            $cMap = [
                                'Australia' => ['au', 'AUS'],
                                'India' => ['in', 'IND'],
                                'United States' => ['us', 'USA'],
                                'United Kingdom' => ['gb', 'GBR'],
                                'Canada' => ['ca', 'CAN'],
                                'New Zealand' => ['nz', 'NZL'],
                                'China' => ['cn', 'CHN'],
                                'Japan' => ['jp', 'JPN'],
                                'Germany' => ['de', 'DEU'],
                                'France' => ['fr', 'FRA'],
                                'Italy' => ['it', 'ITA'],
                                'Spain' => ['es', 'ESP'],
                                'Brazil' => ['br', 'BRA'],
                                'Mexico' => ['mx', 'MEX'],
                                'Russia' => ['ru', 'RUS'],
                                'South Africa' => ['za', 'ZAF'],
                                'Singapore' => ['sg', 'SGP'],
                                'Malaysia' => ['my', 'MYS'],
                                'Philippines' => ['ph', 'PHL'],
                                'Indonesia' => ['id', 'IDN'],
                                'Thailand' => ['th', 'THA'],
                                'Vietnam' => ['vn', 'VNM'],
                                'Pakistan' => ['pk', 'PAK'],
                                'Bangladesh' => ['bd', 'BGD'],
                                'Sri Lanka' => ['lk', 'LKA'],
                                'Nepal' => ['np', 'NPL'],
                                'Saudi Arabia' => ['sa', 'SAU'],
                                'United Arab Emirates' => ['ae', 'ARE'],
                                'Netherlands' => ['nl', 'NLD'],
                                'Sweden' => ['se', 'SWE'],
                                'Norway' => ['no', 'NOR'],
                                'Denmark' => ['dk', 'DNK'],
                                'Finland' => ['fi', 'FIN'],
                                'Poland' => ['pl', 'POL'],
                                'Turkey' => ['tr', 'TUR'],
                                'Israel' => ['il', 'ISR'],
                                'Egypt' => ['eg', 'EGY'],
                                'South Korea' => ['kr', 'KOR'],
                                'Taiwan' => ['tw', 'TWN'],
                                'Hong Kong' => ['hk', 'HKG'],
                                'Argentina' => ['ar', 'ARG'],
                                'Chile' => ['cl', 'CHL'],
                                'Colombia' => ['co', 'COL'],
                                'Peru' => ['pe', 'PER'],
                                'Ireland' => ['ie', 'IRL'],
                                'Switzerland' => ['ch', 'CHE'],
                                'Austria' => ['at', 'AUT'],
                                'Belgium' => ['be', 'BEL']
                            ];
                            $cCode = $cMap[$cName][0] ?? null;
                            $cIso = $cMap[$cName][1] ?? strtoupper(substr($cName, 0, 3));
                        @endphp

                        @if($cCode)
                            <img src="https://flagcdn.com/w40/{{ $cCode }}.png"
                                srcset="https://flagcdn.com/w80/{{ $cCode }}.png 2x" alt="{{ $cName }}"
                                style="width: 24px; height: auto; border-radius: 3px; margin-right: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                        @else
                            <div style="margin-right:8px; color: #64748b" class="icon-box"><i class="fas fa-globe"></i></div>
                        @endif

                        <span style="font-weight: 600; color: #1e293b;">{{ $email->ip_address }}</span>
                        <span style="color: #64748b; font-weight: 500; margin-left: 4px;">({{ $cIso }})</span>
                    </div>
                </div>
            </div>

            {{-- ===== ASSIGNMENT CARD ===== --}}
            <div class="crm-card" style="border-left: 3px solid var(--primary-purple);">
                <div class="card-header-title"><i class="fas fa-user-check"></i> Assignment</div>
                @php
                    $assignee = $email->assigned_to ? \App\CrmUser::find($email->assigned_to) : null;
                    $assignedBy = $email->assigned_by ? \App\CrmUser::find($email->assigned_by) : null;
                @endphp

                @if($assignee)
                    <div
                        style="background: var(--primary-soft); border: 1px solid var(--primary-soft); border-radius: 10px; padding: 1rem; margin-bottom: 1rem;">
                        <div style="display:flex; align-items:center; gap:10px;">
                            <div
                                style="width:38px; height:38px; background:var(--primary-purple); border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-weight:700; font-size:0.9rem; flex-shrink:0;">
                                {{ substr($assignee->name, 0, 2) }}
                            </div>
                            <div>
                                <div style="font-weight:700; color:#1e293b; font-size:0.95rem;">{{ $assignee->name }}</div>
                                <div
                                    style="font-size:0.72rem; color:var(--primary-purple); font-weight:700; text-transform:uppercase; letter-spacing:0.04em;">
                                    {{ $assignee->getRoleLabel() }}
                                </div>
                            </div>
                        </div>
                        @if($assignedBy)
                            <div
                                style="font-size:0.75rem; color:#64748b; margin-top:0.75rem; border-top:1px solid var(--primary-soft); padding-top:0.5rem;">
                                Assigned by <strong>{{ $assignedBy->name }}</strong>
                                @if($email->assigned_at)
                                    · {{ \Carbon\Carbon::parse($email->assigned_at)->diffForHumans() }}
                                @endif
                            </div>
                        @endif
                    </div>
                @else
                    <div
                        style="text-align:center; padding:1rem; background:#f8fafc; border-radius:10px; border:1px dashed #cbd5e1; margin-bottom:1rem;">
                        <i class="fas fa-user-slash"
                            style="color:#94a3b8; font-size:1.5rem; margin-bottom:0.5rem; display:block;"></i>
                        <span style="font-size:0.85rem; color:#94a3b8;">Not assigned yet</span>
                    </div>
                @endif

                @if(Auth::guard('crm')->user()->canAssign())
                    <button onclick="openAssignModal()"
                        style="width:100%; padding:0.6rem; background:var(--primary-purple); color:white; border:none; border-radius:8px; font-weight:600; font-size:0.85rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; transition:background 0.2s;"
                        onmouseover="this.style.background='var(--primary-purple)'" onmouseout="this.style.background='var(--primary-purple)'">
                        <i class="fas fa-user-plus"></i> {{ $assignee ? 'Re-assign' : 'Assign Now' }}
                    </button>
                @endif

                <button onclick="toggleAssignLogs()"
                    style="width:100%; margin-top:0.5rem; padding:0.5rem; background:white; color:#64748b; border:1px solid #e2e8f0; border-radius:8px; font-size:0.8rem; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px;">
                    <i class="fas fa-history"></i> View Assignment History
                </button>

                <div id="assignLogsContainer" style="display:none; margin-top:1rem;">
                    <div id="assignLogsContent" style="font-size:0.8rem; color:#64748b;">Loading...</div>
                </div>
                </div>
            @endif

            @if(Auth::guard('crm')->user()->isEstimator() && $email->estimate_status != 'approved')
                <form action="{{ route('crm.emails.submit_estimate', $email->id) }}" method="POST" id="estimateForm">
                    {{ csrf_field() }}
                </form>
            @endif

            <!-- Product Specs -->
            @include('crm.emails.partials.product_specs')

        </div>

        @if(!Auth::guard('crm')->user()->isEstimator())

        <!-- Main Content -->
        <div class="message-container">

            <div class="product-highlight">
                <div class="product-highlight-label" style="display: flex; justify-content: space-between; align-items: center;">
                    <span>Product of Interest</span>
                    <button type="button" onclick="document.getElementById('productDisplayArea').style.display='none'; document.getElementById('productEditArea').style.display='block';" style="background:none; border:none; color:var(--primary-purple); cursor:pointer; font-size:0.8rem; font-weight:bold;">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;" id="productDisplayArea">
                    <div>
                        <div class="product-highlight-value">{{ $email->product_name ?: 'General Inquiry' }}</div>
                        @if(isset($productDetails) && $productDetails->prod_url)
                            <a href="{{ url($productDetails->prod_url) }}" target="_blank"
                                style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.85rem; margin-top: 6px; color: var(--primary-color); font-weight: 500; text-decoration: none;">
                                View Product <i class="fas fa-external-link-alt" style="font-size: 0.75rem;"></i>
                            </a>
                        @endif
                    </div>

                    @if(isset($productDetails) && $productDetails->prod_image)
                        <img src="{{ asset('images/' . $productDetails->prod_image) }}" alt="Product Image"
                            style="height: 80px; width: 80px; border-radius: 8px; border: 1px solid #e2e8f0; object-fit: cover;">
                    @endif
                </div>

                <div id="productEditArea" style="display: none; margin-top: 10px;">
                    <form action="{{ route('crm.emails.update_product_name', $email->id) }}" method="POST" style="display: flex; gap: 10px; width: 100%;">
                        {{ csrf_field() }}
                        <input type="text" name="product_name" value="{{ $email->product_name }}" required 
                            style="flex: 1; padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; outline: none;">
                        <button type="button" onclick="document.getElementById('productDisplayArea').style.display='flex'; document.getElementById('productEditArea').style.display='none';" style="padding: 0.5rem 1rem; background: white; border: 1px solid #cbd5e1; border-radius: 6px; color: #64748b; cursor: pointer;">Cancel</button>
                        <button type="submit" style="padding: 0.5rem 1rem; background: var(--primary-purple); border: none; border-radius: 6px; color: white; cursor: pointer; font-weight: 600;">Save</button>
                    </form>
                </div>
            </div>

            <div class="crm-card" style="padding: 0;">
                <div
                    style="padding: 1rem 1.5rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; border-radius: 16px 16px 0 0; font-weight: 700; color: #475569;">
                    <i class="fas fa-align-left" style="margin-right: 8px;"></i> Message Content
                </div>
                <div class="message-body" style="border: none; border-radius: 0 0 16px 16px;">
                    {{ $email->message }}
                </div>
            </div>

            @if($email->file_url)
                @php
                    $ext = pathinfo($email->file_url, PATHINFO_EXTENSION);
                    $isImage = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                @endphp

                <div class="attachment-section">
                    <div class="card-header-title" style="border-bottom: none;"><i class="fas fa-paperclip"></i> Attachment
                    </div>

                    @if($isImage)
                        <div class="attachment-preview">
                            <img src="{{ $email->file_url }}" alt="Attachment" onclick="window.open(this.src, '_blank')">
                            <div style="margin-top: 10px; font-size: 0.8rem; color: #64748b; font-weight: 500;">
                                <i class="fas fa-search-plus"></i> Click image to view full size
                            </div>
                        </div>
                    @endif

                    <div
                        style="margin-top: 1rem; background: white; border: 1px solid #e2e8f0; padding: 1rem; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; box-shadow: var(--shadow-sm);">
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div
                                style="width: 42px; height: 42px; background: #e0f2fe; color: #0284c7; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-file-download" style="font-size: 1.2rem;"></i>
                            </div>
                            <div
                                style="font-size: 0.9rem; font-weight: 600; color: #1e293b; overflow: hidden; text-overflow: ellipsis; max-width: 200px;">
                                {{ basename($email->file_url) }}
                            </div>
                        </div>
                        <a href="{{ $email->file_url }}" target="_blank" class="btn-action"
                            style="background:var(--primary-color); color:white;">
                            Download <i class="fas fa-arrow-down"></i>
                        </a>
                    </div>
                </div>
            @endif

            {{-- ===== ORDER DONE SECTION ===== --}}
            @if($email->status === 'Order Done')
                {{-- Already an Order: Show summary banner --}}
                <div
                    style="background: linear-gradient(135deg,#ecfdf5,#d1fae5); border:1.5px solid #6ee7b7; border-radius:16px; padding:1.5rem; margin-top:1.5rem; display:flex; align-items:flex-start; gap:1rem;">
                    <div
                        style="width:44px;height:44px;background:#10b981;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-check-double" style="color:white;font-size:1.2rem;"></i>
                    </div>
                    <div style="flex:1;">
                        <div style="font-weight:800;color:#065f46;font-size:1rem;margin-bottom:0.25rem;">Order Confirmed</div>
                        <div
                            style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:0.75rem;margin-top:0.75rem;">
                            <div style="background:white;border-radius:10px;padding:0.75rem;border:1px solid #a7f3d0;">
                                <div
                                    style="font-size:0.7rem;font-weight:700;color:#6ee7b7;text-transform:uppercase;letter-spacing:0.05em;">
                                    Unit Price</div>
                                <div style="font-size:1.2rem;font-weight:800;color:#065f46;">
                                    ${{ number_format($email->order_price ?? 0, 2) }}</div>
                            </div>
                            <div style="background:white;border-radius:10px;padding:0.75rem;border:1px solid #a7f3d0;">
                                <div
                                    style="font-size:0.7rem;font-weight:700;color:#6ee7b7;text-transform:uppercase;letter-spacing:0.05em;">
                                    Quantity</div>
                                <div style="font-size:1.2rem;font-weight:800;color:#065f46;">
                                    {{ number_format($email->order_quantity ?? 0) }}
                                </div>
                            </div>
                            <div style="background:white;border-radius:10px;padding:0.75rem;border:1px solid #a7f3d0;">
                                <div
                                    style="font-size:0.7rem;font-weight:700;color:#6ee7b7;text-transform:uppercase;letter-spacing:0.05em;">
                                    Total Value</div>
                                <div style="font-size:1.2rem;font-weight:800;color:#065f46;">
                                    ${{ number_format(($email->order_price ?? 0) * ($email->order_quantity ?? 0), 2) }}</div>
                            </div>
                            <div style="background:white;border-radius:10px;padding:0.75rem;border:1px solid #a7f3d0;">
                                <div
                                    style="font-size:0.7rem;font-weight:700;color:#6ee7b7;text-transform:uppercase;letter-spacing:0.05em;">
                                    Marked By</div>
                                <div style="font-size:0.9rem;font-weight:700;color:#065f46;">
                                    {{ $email->order_marked_by ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                        @if($email->order_notes)
                            <div
                                style="margin-top:0.75rem;background:white;border-radius:10px;padding:0.75rem;border:1px solid #a7f3d0;">
                                <div
                                    style="font-size:0.7rem;font-weight:700;color:#6ee7b7;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.25rem;">
                                    Notes</div>
                                <div style="color:#065f46;font-size:0.9rem;">{{ $email->order_notes }}</div>
                            </div>
                        @endif
                        @if(Auth::guard('crm')->user()->isAdmin())
                            <a href="{{ route('crm.orders.invoice', $email->id) }}" target="_blank"
                                style="display:inline-flex;align-items:center;gap:0.5rem;margin-top:1rem;padding:0.55rem 1.25rem;background:#065f46;color:white;border-radius:10px;font-weight:700;font-size:0.85rem;text-decoration:none;">
                                <i class="fas fa-file-invoice"></i> View Invoice
                            </a>
                        @endif
                    </div>
                </div>

                {{-- ERP Production, Design Proofing & QC Modules removed from inbox detail.
                    These workflows now live in their dedicated Design, Prepress, Production and QC screens. --}}
                @if(false)
                {{-- ERP Production, Design Proofing & QC Modules --}}
                <div style="margin-top: 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; align-items: start;">
                    
                    {{-- LEFT COLUMN: Production Planning & Proof Revisions --}}
                    <div>
                        {{-- Production Status Tracker & Controller --}}
                        <div class="crm-card" style="margin-bottom: 2rem;">
                            <div class="card-header-title">
                                <i class="fas fa-industry" style="color: var(--primary-purple);"></i> Production Stage
                            </div>
                            
                            @php
                                $currentUser = Auth::guard('crm')->user();
                                $canUpdateProd = $currentUser->isAdmin() || $currentUser->isSalesManager() || $currentUser->isShipping() || $currentUser->isDesigner() || $currentUser->isPrepress() || $currentUser->isQC();
                                $canManageProofs = $currentUser->isAdmin() || $currentUser->isSalesManager() || $currentUser->isDesigner();
                                $canSubmitQc = $currentUser->isAdmin() || $currentUser->isSalesManager() || $currentUser->isQC();

                                $prodStatus = $email->production_status ?? 'pending_design';
                                $stages = [
                                    'pending_design' => ['label' => 'Design / Prepress Prep', 'icon' => 'fa-pencil-ruler', 'color' => '#3b82f6'],
                                    'in_production' => ['label' => 'Printing & Gluing', 'icon' => 'fa-cog', 'color' => '#f59e0b'],
                                    'qc_check' => ['label' => 'Quality Control Checks', 'icon' => 'fa-clipboard-check', 'color' => 'var(--primary-purple)'],
                                    'produced' => ['label' => 'Produced (QC Passed)', 'icon' => 'fa-check-double', 'color' => '#10b981'],
                                    'shipping' => ['label' => 'Shipping Dispatch', 'icon' => 'fa-shipping-fast', 'color' => '#06b6d4'],
                                    'shipped' => ['label' => 'Shipped / In Transit', 'icon' => 'fa-truck', 'color' => 'var(--primary-purple)'],
                                    'delivered' => ['label' => 'Delivered', 'icon' => 'fa-home', 'color' => '#10b981'],
                                    'cancelled' => ['label' => 'Cancelled', 'icon' => 'fa-times', 'color' => '#ef4444'],
                                ];
                                $currentStage = isset($stages[$prodStatus]) ? $stages[$prodStatus] : $stages['pending_design'];
                            @endphp

                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.5rem; background: #f8fafc; padding: 0.75rem; border-radius: 12px; border: 1px solid #e2e8f0;">
                                <div style="width: 38px; height: 38px; background: {{ $currentStage['color'] }}15; color: {{ $currentStage['color'] }}; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
                                    <i class="fas {{ $currentStage['icon'] }}"></i>
                                </div>
                                <div>
                                    <div style="font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Current Production Status</div>
                                    <div style="font-size: 0.95rem; font-weight: 800; color: #1e293b;">{{ $currentStage['label'] }}</div>
                                </div>
                            </div>

                            <form action="{{ route('crm.emails.update_production_status', $email->id) }}" method="POST">
                                {{ csrf_field() }}
                                <label style="display: block; font-size: 0.75rem; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 0.4rem;">Update Stage</label>
                                <div style="display: flex; gap: 0.75rem;">
                                    <select name="production_status" style="flex: 1; height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 600; padding: 0 10px; background: white; font-size: 0.85rem;" {{ !$canUpdateProd ? 'disabled' : '' }}>
                                        @foreach($stages as $key => $stageInfo)
                                            <option value="{{ $key }}" {{ $prodStatus == $key ? 'selected' : '' }}>{{ $stageInfo['label'] }}</option>
                                        @endforeach
                                    </select>
                                    @if($canUpdateProd)
                                        <button type="submit" style="background: #1e293b; color: white; border: none; padding: 0 1.25rem; border-radius: 8px; font-weight: 700; cursor: pointer; font-size: 0.85rem; height: 38px;">
                                            UPDATE
                                        </button>
                                    @endif
                                </div>
                                @if(!$canUpdateProd)
                                    <div style="margin-top: 0.5rem; font-size: 0.75rem; color: #94a3b8; display: flex; align-items: center; gap: 0.25rem;">
                                        <i class="fas fa-lock"></i> Only authorized production departments can update production status.
                                    </div>
                                @endif
                            </form>
                        </div>

                        {{-- Artwork Proof Revisions Module --}}
                        <div class="crm-card">
                            <div class="card-header-title" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                <span><i class="fas fa-paint-brush" style="color: #ea580c;"></i> Design Proof Revisions</span>
                                @php
                                    $latestProof = $email->proofRevisions()->first();
                                @endphp
                                @if(!$latestProof)
                                    <span style="font-size: 0.7rem; background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 99px;">No proof uploaded</span>
                                @elseif($latestProof->status === 'approved')
                                    <span style="font-size: 0.7rem; background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 99px; font-weight: 700;">Approved (v{{ $latestProof->version_number }})</span>
                                @elseif($latestProof->status === 'revision_needed')
                                    <span style="font-size: 0.7rem; background: #fee2e2; color: #991b1b; padding: 2px 8px; border-radius: 99px; font-weight: 700;">Revision requested (v{{ $latestProof->version_number }})</span>
                                @else
                                    <span style="font-size: 0.7rem; background: #fff7ed; color: #c2410c; padding: 2px 8px; border-radius: 99px; font-weight: 700;">Pending Review (v{{ $latestProof->version_number }})</span>
                                @endif
                            </div>

                            <!-- Upload Proof Form -->
                            @if($canManageProofs)
                                <form action="{{ route('crm.emails.upload_proof', $email->id) }}" method="POST" enctype="multipart/form-data" style="margin-bottom: 1.5rem; background: #fafafa; padding: 1rem; border-radius: 12px; border: 1px solid #f1f5f9;">
                                    {{ csrf_field() }}
                                    <div style="margin-bottom: 0.75rem;">
                                        <label style="display: block; font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 0.25rem;">Upload New Proof (Max 15MB)</label>
                                        <input type="file" name="file" required style="width: 100%; font-size: 0.8rem; padding: 6px; border: 1px dashed #cbd5e1; border-radius: 6px; background: white;">
                                    </div>
                                    <div style="margin-bottom: 1rem;">
                                        <label style="display: block; font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 0.25rem;">Initial Notes / Specs</label>
                                        <textarea name="feedback_notes" placeholder="Enter design properties, bleed lines, or color profiles..." style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px; font-size: 0.8rem; height: 50px; resize: none;"></textarea>
                                    </div>
                                    <button type="submit" style="width: 100%; background: var(--primary-purple); color: white; border: none; padding: 8px 12px; border-radius: 8px; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                                        <i class="fas fa-upload"></i> Upload &amp; Version
                                    </button>
                                </form>
                            @else
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem; color: #94a3b8; font-size: 0.8rem; text-align: center;">
                                    <i class="fas fa-lock" style="font-size: 1.2rem; margin-bottom: 0.5rem; display: block; color: #cbd5e1;"></i>
                                    Only Design can manage artwork proofs.
                                </div>
                            @endif

                            <!-- Revision Logs -->
                            @if($email->proofRevisions->count() > 0)
                                <div style="display: flex; flex-direction: column; gap: 1rem;">
                                    @foreach($email->proofRevisions as $pProof)
                                        <div style="background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; box-shadow: var(--shadow-sm);">
                                            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.5rem; margin-bottom: 0.5rem;">
                                                <span style="font-weight: 800; font-size: 0.85rem; color: #1e293b;">Version {{ $pProof->version_number }}</span>
                                                <span style="font-size: 0.75rem; color: #64748b;">{{ $pProof->created_at->format('M d, Y h:i A') }}</span>
                                            </div>

                                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem;">
                                                <a href="{{ asset($pProof->file_path) }}" target="_blank" style="color: var(--primary-purple); font-weight: 700; text-decoration: none; font-size: 0.8rem; display: flex; align-items: center; gap: 0.4rem;">
                                                    <i class="fas fa-file-pdf" style="font-size: 1rem;"></i> View Proof File
                                                </a>
                                                @if($pProof->status === 'approved')
                                                    <span style="font-size: 0.75rem; color: #16a34a; font-weight: 700;"><i class="fas fa-check-circle"></i> Approved</span>
                                                @elseif($pProof->status === 'revision_needed')
                                                    <span style="font-size: 0.75rem; color: #dc2626; font-weight: 700;"><i class="fas fa-times-circle"></i> Revision Needed</span>
                                                @else
                                                    <span style="font-size: 0.75rem; color: #ea580c; font-weight: 700;"><i class="fas fa-clock"></i> Pending Review</span>
                                                @endif
                                            </div>

                                            @if($pProof->feedback_notes)
                                                <div style="font-size: 0.75rem; color: #475569; background: #f8fafc; padding: 0.5rem 0.75rem; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 0.75rem; font-style: italic;">
                                                    "{{ $pProof->feedback_notes }}"
                                                </div>
                                            @endif

                                            @if($pProof->status === 'pending')
                                                @if($canManageProofs)
                                                    <div style="display: flex; gap: 0.5rem; border-top: 1px dashed #e2e8f0; padding-top: 0.75rem; margin-top: 0.5rem;">
                                                        <form action="{{ route('crm.proof_revisions.update_status', $pProof->id) }}" method="POST" style="flex: 1;">
                                                            {{ csrf_field() }}
                                                            <input type="hidden" name="status" value="approved">
                                                            <button type="submit" style="width: 100%; background: #10b981; color: white; border: none; padding: 6px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; cursor: pointer;">
                                                                APPROVE
                                                            </button>
                                                        </form>
                                                        <button type="button" onclick="document.getElementById('proofRejectContainer{{ $pProof->id }}').style.display='block'" style="flex: 1; background: #ef4444; color: white; border: none; padding: 6px; border-radius: 6px; font-weight: 700; font-size: 0.75rem; cursor: pointer;">
                                                            REQUEST REVISION
                                                        </button>
                                                    </div>
                                                    <div id="proofRejectContainer{{ $pProof->id }}" style="display: none; margin-top: 0.75rem; border: 1px solid #fecaca; background: #fef2f2; padding: 10px; border-radius: 8px;">
                                                        <form action="{{ route('crm.proof_revisions.update_status', $pProof->id) }}" method="POST">
                                                            {{ csrf_field() }}
                                                            <input type="hidden" name="status" value="revision_needed">
                                                            <label style="display: block; font-size: 0.7rem; font-weight: 800; color: #991b1b; text-transform: uppercase; margin-bottom: 0.25rem;">Revision Notes</label>
                                                            <textarea name="feedback_notes" required placeholder="Color adjustment needed, change text fonts to bold..." style="width: 100%; border: 1px solid #fca5a5; border-radius: 6px; padding: 4px; font-size: 0.75rem; height: 50px; resize: none; margin-bottom: 0.5rem;"></textarea>
                                                            <div style="display: flex; gap: 0.5rem;">
                                                                <button type="submit" style="flex: 1; background: #dc2626; color: white; border: none; padding: 4px; border-radius: 4px; font-weight: 700; font-size: 0.7rem; cursor: pointer;">
                                                                    Submit Revision Request
                                                                </button>
                                                                <button type="button" onclick="document.getElementById('proofRejectContainer{{ $pProof->id }}').style.display='none'" style="flex: 1; background: #64748b; color: white; border: none; padding: 4px; border-radius: 4px; font-weight: 700; font-size: 0.7rem; cursor: pointer;">
                                                                    Cancel
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                @else
                                                    <div style="font-size: 0.75rem; color: #94a3b8; font-style: italic; text-align: center; border-top: 1px dashed #e2e8f0; padding-top: 0.5rem; margin-top: 0.5rem;">
                                                        <i class="fas fa-lock"></i> Pending approval by Design / Prepress.
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- RIGHT COLUMN: Quality Control Scorecard --}}
                    <div>
                        <div class="crm-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 0.75rem; margin-bottom: 1.25rem;">
                                <h4 style="margin: 0; font-size: 0.95rem; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-clipboard-check" style="color: #10b981;"></i> Quality Control Checklist
                                </h4>
                                @php
                                    $latestQc = $email->qualityControls()->orderBy('created_at', 'desc')->first();
                                    $qcPassed = $latestQc && $latestQc->dimension_passed && $latestQc->fold_color_passed && $latestQc->quantity_passed && $latestQc->glue_strength_passed && $latestQc->barcode_scan_passed && $latestQc->packaging_passed;
                                @endphp
                                @if(!$latestQc)
                                    <span style="font-size: 0.7rem; font-weight: 700; color: #ea580c; background: #fff7ed; padding: 0.2rem 0.6rem; border-radius: 9999px; border: 1px solid #ffedd5;">
                                        PENDING QC
                                    </span>
                                @elseif($qcPassed)
                                    <span style="font-size: 0.7rem; font-weight: 700; color: #16a34a; background: #f0fdf4; padding: 0.2rem 0.6rem; border-radius: 9999px; border: 1px solid #dcfce7;">
                                        PASSED QC
                                    </span>
                                @else
                                    <span style="font-size: 0.7rem; font-weight: 700; color: #dc2626; background: #fef2f2; padding: 0.2rem 0.6rem; border-radius: 9999px; border: 1px solid #fee2e2;">
                                        FAILED QC
                                    </span>
                                @endif
                            </div>

                            @if($canSubmitQc)
                                <form action="{{ route('crm.emails.submit_qc', $email->id) }}" method="POST" enctype="multipart/form-data">
                                    {{ csrf_field() }}
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.25rem;">
                                        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #475569; cursor: pointer; font-weight: 500;">
                                            <input type="checkbox" name="dimension_passed" value="1" style="width: 16px; height: 16px; accent-color: var(--primary-purple);" {{ ($latestQc && $latestQc->dimension_passed) ? 'checked' : '' }}>
                                            Dimension Check
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #475569; cursor: pointer; font-weight: 500;">
                                            <input type="checkbox" name="fold_color_passed" value="1" style="width: 16px; height: 16px; accent-color: var(--primary-purple);" {{ ($latestQc && $latestQc->fold_color_passed) ? 'checked' : '' }}>
                                            Fold &amp; Color Check
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #475569; cursor: pointer; font-weight: 500;">
                                            <input type="checkbox" name="quantity_passed" value="1" style="width: 16px; height: 16px; accent-color: var(--primary-purple);" {{ ($latestQc && $latestQc->quantity_passed) ? 'checked' : '' }}>
                                            Quantity Check
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #475569; cursor: pointer; font-weight: 500;">
                                            <input type="checkbox" name="glue_strength_passed" value="1" style="width: 16px; height: 16px; accent-color: var(--primary-purple);" {{ ($latestQc && $latestQc->glue_strength_passed) ? 'checked' : '' }}>
                                            Glue Strength Check
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #475569; cursor: pointer; font-weight: 500;">
                                            <input type="checkbox" name="barcode_scan_passed" value="1" style="width: 16px; height: 16px; accent-color: var(--primary-purple);" {{ ($latestQc && $latestQc->barcode_scan_passed) ? 'checked' : '' }}>
                                            Barcode Scan
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #475569; cursor: pointer; font-weight: 500;">
                                            <input type="checkbox" name="packaging_passed" value="1" style="width: 16px; height: 16px; accent-color: var(--primary-purple);" {{ ($latestQc && $latestQc->packaging_passed) ? 'checked' : '' }}>
                                            Packaging Inspection
                                        </label>
                                    </div>

                                    <div style="margin-bottom: 0.75rem;">
                                        <label style="display: block; font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 0.25rem;">Defect Photo (if any checks fail)</label>
                                        <input type="file" name="photo_defect" class="form-control" style="font-size: 0.8rem; padding: 4px; border: 1px dashed #cbd5e1; background: #fafafa; width: 100%; border-radius: 6px;">
                                    </div>

                                    <div style="margin-bottom: 1rem;">
                                        <label style="display: block; font-size: 0.7rem; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 0.25rem;">QC Comments / Notes</label>
                                        <textarea name="notes" placeholder="Describe thickness, colors, packaging quality, weight..." style="width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 8px; font-size: 0.8rem; height: 60px; resize: vertical;"></textarea>
                                    </div>

                                    <button type="submit" style="width: 100%; background: #10b981; color: white; border: none; padding: 10px; border-radius: 8px; font-weight: 700; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-bottom: 1.25rem;">
                                        <i class="fas fa-check-square"></i> Submit Quality Checklist
                                    </button>
                                </form>
                            @else
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1rem; margin-bottom: 1.25rem; color: #94a3b8; font-size: 0.8rem; text-align: center;">
                                    <i class="fas fa-lock" style="font-size: 1.2rem; margin-bottom: 0.5rem; display: block; color: #cbd5e1;"></i>
                                    Only Quality Control (QC) Department can submit checklist.
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.25rem; opacity: 0.6; pointer-events: none;">
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #475569; font-weight: 500;">
                                        <input type="checkbox" disabled {{ ($latestQc && $latestQc->dimension_passed) ? 'checked' : '' }}>
                                        Dimension Check
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #475569; font-weight: 500;">
                                        <input type="checkbox" disabled {{ ($latestQc && $latestQc->fold_color_passed) ? 'checked' : '' }}>
                                        Fold &amp; Color Check
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #475569; font-weight: 500;">
                                        <input type="checkbox" disabled {{ ($latestQc && $latestQc->quantity_passed) ? 'checked' : '' }}>
                                        Quantity Check
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #475569; font-weight: 500;">
                                        <input type="checkbox" disabled {{ ($latestQc && $latestQc->glue_strength_passed) ? 'checked' : '' }}>
                                        Glue Strength Check
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #475569; font-weight: 500;">
                                        <input type="checkbox" disabled {{ ($latestQc && $latestQc->barcode_scan_passed) ? 'checked' : '' }}>
                                        Barcode Scan
                                    </label>
                                    <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #475569; font-weight: 500;">
                                        <input type="checkbox" disabled {{ ($latestQc && $latestQc->packaging_passed) ? 'checked' : '' }}>
                                        Packaging Inspection
                                    </label>
                                </div>
                            @endif

                            <!-- QC Scorecard History logs -->
                            @if($email->qualityControls->count() > 0)
                                <div style="margin-top: 1.25rem; border-top: 1px solid #f1f5f9; padding-top: 1rem;">
                                    <h5 style="margin: 0 0 0.75rem 0; font-size: 0.75rem; font-weight: 800; color: #475569; text-transform: uppercase;">QC Audit Logs</h5>
                                    <div style="display: flex; flex-direction: column; gap: 0.75rem; max-height: 250px; overflow-y: auto;">
                                        @foreach($email->qualityControls()->orderBy('created_at', 'desc')->get() as $log)
                                            @php
                                                $logPassed = $log->dimension_passed && $log->fold_color_passed && $log->quantity_passed && $log->glue_strength_passed && $log->barcode_scan_passed && $log->packaging_passed;
                                            @endphp
                                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.75rem; font-size: 0.8rem;">
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.25rem;">
                                                    <span style="font-weight: 700; color: #334155;">{{ $log->agent->name ?? 'QC Agent' }}</span>
                                                    <span style="font-size: 0.75rem; color: #64748b;">{{ $log->created_at->format('M d, Y H:i') }}</span>
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.5rem;">
                                                    @if($logPassed)
                                                        <span style="color: #16a34a; font-weight: 700; display: inline-flex; align-items: center; gap: 0.25rem;">
                                                            <i class="fas fa-check-circle"></i> Passed QC
                                                        </span>
                                                    @else
                                                        <span style="color: #dc2626; font-weight: 700; display: inline-flex; align-items: center; gap: 0.25rem;">
                                                            <i class="fas fa-times-circle"></i> Failed QC
                                                        </span>
                                                        <span style="font-size: 0.75rem; color: #64748b;">
                                                            @php
                                                                $failedArr = [];
                                                                if(!$log->dimension_passed) $failedArr[] = 'Dims';
                                                                if(!$log->fold_color_passed) $failedArr[] = 'Color';
                                                                if(!$log->quantity_passed) $failedArr[] = 'Qty';
                                                                if(!$log->glue_strength_passed) $failedArr[] = 'Glue';
                                                                if(!$log->barcode_scan_passed) $failedArr[] = 'Barcode';
                                                                if(!$log->packaging_passed) $failedArr[] = 'Pkg';
                                                                echo implode(', ', $failedArr);
                                                            @endphp
                                                        </span>
                                                    @endif
                                                </div>
                                                @if($log->notes)
                                                    <div style="color: #475569; background: #fff; padding: 0.5rem; border-radius: 4px; border: 1px solid #f1f5f9; font-size: 0.75rem; margin-top: 0.25rem; font-style: italic;">
                                                        "{{ $log->notes }}"
                                                    </div>
                                                @endif
                                                @if($log->photo_defect_path)
                                                    <div style="margin-top: 0.5rem;">
                                                        <a href="{{ asset($log->photo_defect_path) }}" target="_blank" style="color: #3b82f6; text-decoration: none; font-weight: 600; font-size: 0.75rem; display: inline-flex; align-items: center; gap: 0.25rem;">
                                                            <i class="fas fa-image"></i> View Defect Photo
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            @elseif(!Auth::guard('crm')->user()->isAdmin())
                @if($canCreateSalesOrder)
                    <div style="margin-top:1.5rem; border-top:1px solid #e2e8f0; padding-top:1.25rem;">
                        <button type="button" onclick="document.getElementById('createSalesOrderModalBottom').style.display='flex'"
                            style="width:100%;padding:1rem;background:var(--primary-purple);color:white;border:none;border-radius:14px;font-size:1rem;font-weight:800;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.75rem;box-shadow:0 10px 22px var(--primary-shadow);transition:all 0.3s;">
                            <i class="fas fa-file-invoice-dollar" style="font-size:1.2rem;"></i>
                            Create Sales Order
                        </button>
                    </div>

                    <div id="createSalesOrderModalBottom" class="sales-order-overlay" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(15, 23, 42, 0.6);z-index:9999;align-items:center;justify-content:center;padding:1rem;">
                        <div class="sales-order-dialog" style="background:white;width:100%;max-width:500px;border-radius:16px;padding:2rem;box-shadow:0 20px 40px rgba(0,0,0,0.2);">
                            <div class="sales-order-head" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
                                <h3 style="margin:0;font-size:1.25rem;font-weight:700;">Create Sales Order</h3>
                                <i class="fas fa-times sales-order-close" style="cursor:pointer;color:#94a3b8;font-size:1.2rem;" onclick="document.getElementById('createSalesOrderModalBottom').style.display='none'"></i>
                            </div>
                            <form class="sales-order-form" action="{{ route('crm.sales_orders.store') }}" method="POST">
                                {{ csrf_field() }}
                                <input type="hidden" name="crm_email_id" value="{{ $email->id }}">
                                @if(count($salesOrderQuantityOptions) > 0)
                                <div class="sales-order-field" style="margin-bottom:1rem;">
                                    <label style="display:block;font-size:0.85rem;font-weight:600;color:#1e293b;margin-bottom:0.5rem;">Selected Quantity Option</label>
                                    <select class="sales-order-select" id="sales_order_option_bottom" name="estimate_option_index" style="width:100%;padding:0.75rem;border:1px solid #cbd5e1;border-radius:8px;font-size:0.95rem;outline:none;">
                                        @foreach($salesOrderQuantityOptions as $idx => $option)
                                            <option value="{{ $idx }}" data-floor="{{ (float)($option['team_lead_price'] ?? $option['price'] ?? 0) }}" data-offer="{{ (float)($option['price'] ?? 0) }}" data-qty="{{ (int)($option['quantity'] ?? 0) }}">{{ number_format((int)($option['quantity'] ?? 0)) }} pcs — {{ $salesOrderCurrency }} {{ number_format((float)($option['price'] ?? 0), 2) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif
                                <div class="sales-order-field" style="margin-bottom:1rem;padding:1rem;border:1px solid var(--primary-shadow);border-radius:12px;background:var(--primary-soft);">
                                    <label style="display:block;font-size:.85rem;font-weight:700;color:#1e293b;margin-bottom:.5rem;">Sales Offer Price</label>
                                    <div style="display:flex;align-items:center;gap:.5rem;background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:0 .75rem;"><strong>{{ $salesOrderCurrency }}</strong><input id="sales_offer_price_bottom" name="sales_offer_price" type="number" step="0.01" min="0" required style="width:100%;padding:.75rem 0;border:0;outline:0;font-size:.95rem;font-weight:750;"></div>
                                    <div id="sales_offer_help_bottom" style="margin-top:.45rem;color:#64748b;font-size:.72rem;"></div>
                                </div>
                                <div class="sales-order-field" style="margin-bottom:1rem;">
                                    <label style="display:block;font-size:0.85rem;font-weight:600;color:#1e293b;margin-bottom:0.5rem;">Payment Terms</label>
                                    <select class="sales-order-select" name="payment_term" required style="width:100%;padding:0.75rem;border:1px solid #cbd5e1;border-radius:8px;font-size:0.95rem;outline:none;">
                                        <option value="50_advance" selected>50% Advance Required (Default)</option>
                                        <option value="100_deposit">100% Deposit Required</option>
                                        <option value="credit">Credit Customer (Net Terms)</option>
                                    </select>
                                </div>
                                <div id="credit_days_container_bottom" class="sales-order-field" style="display:none;margin-bottom:1rem;">
                                    <label style="display:block;font-size:0.85rem;font-weight:600;color:#1e293b;margin-bottom:0.5rem;">Credit Terms</label>
                                    <select class="sales-order-select" name="credit_days" style="width:100%;padding:0.75rem;border:1px solid #cbd5e1;border-radius:8px;font-size:0.95rem;outline:none;">
                                        <option value="15">Net 15</option>
                                        <option value="30" selected>Net 30</option>
                                        <option value="45">Net 45</option>
                                        <option value="60">Net 60</option>
                                    </select>
                                </div>
                                <button class="sales-order-submit" type="submit" style="width:100%;padding:0.85rem;background:#16a34a;color:white;border:none;border-radius:8px;font-weight:bold;font-size:1rem;cursor:pointer;display:flex;justify-content:center;align-items:center;gap:8px;">
                                    <i class="fas fa-check"></i> Generate Order
                                </button>
                            </form>
                        </div>
                    </div>
                    <script>
                        (function () {
                            const termSelect = document.querySelector('#createSalesOrderModalBottom select[name="payment_term"]');
                            const creditDays = document.getElementById('credit_days_container_bottom');
                            const optionSelect = document.getElementById('sales_order_option_bottom');
                            const offerInput = document.getElementById('sales_offer_price_bottom');
                            const offerHelp = document.getElementById('sales_offer_help_bottom');
                            const currency = @json($salesOrderCurrency);
                            const syncOfferFloor = () => {
                                const option = optionSelect ? optionSelect.options[optionSelect.selectedIndex] : null;
                                const floor = option ? Number(option.dataset.floor || 0) : Number(@json((float)($email->estimated_price ?? 0)));
                                const qty = option ? Number(option.dataset.qty || 0) : Number(@json((int)($email->quantity ?? 0)));
                                offerInput.min = floor.toFixed(2);
                                offerInput.value = Number(option ? option.dataset.offer || floor : floor).toFixed(2);
                                offerHelp.textContent = 'Minimum approved: '+currency+' '+floor.toFixed(2)+(qty > 0 ? ' · '+currency+' '+(floor/qty).toFixed(4)+' per unit' : '')+'. You may increase this price, but cannot reduce it.';
                            };
                            if (optionSelect) optionSelect.addEventListener('change', syncOfferFloor);
                            syncOfferFloor();
                            if (termSelect && creditDays) {
                                const toggleCreditDays = () => {
                                    creditDays.style.display = (termSelect.value === 'credit') ? 'block' : 'none';
                                };
                                termSelect.addEventListener('change', toggleCreditDays);
                                toggleCreditDays();
                            }
                        })();
                    </script>
                @endif
            @endif

            <!-- Messaging Section -->
            <div class="crm-card chat-card" style="padding: 0; margin-top: 2rem; border: 1px solid #e2e8f0;">
                <div class="chat-header" style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="chat-avatar">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div>
                            <div class="chat-title">Conversation</div>
                            <div class="chat-client-name">{{ $email->client_name
        ?: 'General Inquiry' }}</div>
                        </div>
                    </div>
                    <div class="live-indicator">
                        <span class="live-dot"></span> Online
                    </div>
                </div>

                <div id="chat-history"
                    style="max-height: 500px; overflow-y: auto; padding: 1.5rem; background: #fcfdfe; display: flex; flex-direction: column;">
                    @if(count($email->messages) > 0)
                        @php $lastDateStr = null; @endphp
                        @foreach($email->messages as $msg)
                            @php
                                $msgDateStr = $msg->created_at->format('M j, Y');
                                if ($msgDateStr !== $lastDateStr) {
                                    $lastDateStr = $msgDateStr;
                                    $displayDate = $msgDateStr;
                                    if ($msg->created_at->isToday())
                                        $displayDate = 'Today';
                                    elseif ($msg->created_at->isYesterday())
                                        $displayDate = 'Yesterday';

                                    echo '<div class="chat-date-separator"><span>'
                                        . $displayDate . '</span>
                                        </div>';
                                }
                            @endphp
                            @php
                                $msgHasAttachments = $msg->attachments && count($msg->attachments) > 0;
                                $msgHasText = $msg->message_body && trim($msg->message_body) != '';
                                $hasImageAttachment = false;
                                $isMsgOnlyImage = false;
                                $isTemplate = $msgHasText && strpos($msg->message_body, 'Custom Packaging Quote') !== false;
                                if ($msgHasAttachments) {
                                    foreach ($msg->attachments as $path) {
                                        if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $path)) {
                                            $hasImageAttachment = true;
                                            break;
                                        }
                                    }
                                    $isMsgOnlyImage = !$msgHasText && collect($msg->attachments)->every(function ($p) {
                                        return preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $p);
                                    });
                                }
                            @endphp
                            <div id="msg-{{ $msg->id }}" class="chat-message"
                                style="display: flex; flex-direction: column; align-items: {{ $msg->sender_type == 'admin' ? 'flex-end' : 'flex-start' }}; margin-bottom: 1.5rem;">
                                @if($msgHasText)
                                    <div class="{{ $isTemplate ? '' : ($msg->sender_type == 'admin' ? 'msg-bubble-admin' : 'msg-bubble-client') }}"
                                        style="max-width: {{ $isTemplate ? '100%' : '80%' }}; padding: {{ $isTemplate ? '0' : '0.85rem 1.1rem' }}; position: relative; {{ $isTemplate ? 'color: inherit;' : '' }}">
                                        <div style="font-size: 0.95rem; line-height: 1.55;">
                                            @if($msg->sender_type === 'admin')
                                                {!! $msg->message_body !!}
                                            @else
                                                {!! nl2br(e($msg->message_body)) !!}
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                @if($msgHasAttachments)
                                    <div class="chat-attachments" style="justify-content: {{ $msg->sender_type == 'admin' ? 'flex-end' : 'flex-start' }};">
                                        @foreach($msg->attachments as $path)
                                            @php $isImg = preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $path); @endphp
                                            @if($isImg)
                                                <a href="{{ asset($path) }}" target="_blank" class="chat-image-card"
                                                    style="display: block; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                                    <img src="{{ asset($path) }}"
                                                        style="max-width: 280px; max-height: 350px; display: block;">
                                                </a>
                                            @else
                                                <a href="{{ asset($path) }}" target="_blank" class="chat-file-card {{ $msg->sender_type == 'admin' ? 'is-admin' : '' }}"
                                                    style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: {{ $msg->sender_type == 'admin' ? 'var(--primary-purple)' : '#ffffff' }}; border: 1px solid {{ $msg->sender_type == 'admin' ? 'var(--primary-purple)' : '#e2e8f0' }}; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-decoration: none; color: {{ $msg->sender_type == 'admin' ? '#ffffff' : '#1e293b' }}; font-size: 0.85rem; font-weight: 500;">
                                                    <div class="chat-file-icon"
                                                        style="width: 32px; height: 32px; background: {{ $msg->sender_type == 'admin' ? 'rgba(255,255,255,0.2)' : '#eef2ff' }}; color: {{ $msg->sender_type == 'admin' ? '#fff' : 'var(--primary-purple)' }}; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-file-alt"></i>
                                                    </div>
                                                    {{ basename($path) }}
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                                <div class="chat-message-meta"
                                    style="font-size: 0.75rem; color: #94a3b8; margin-top: 6px; display: flex; align-items: center; gap: 4px;">
                                    {{ $msg->created_at->format('g:i A') }}
                                    @if($msg->sender_type == 'admin')
                                        • {{ strtolower($msg->user ? $msg->user->name : 'admin') }}


                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div style="text-align: center; padding: 2rem; color: #94a3b8;">
                            <i class="fas fa-history" style="font-size: 2rem; margin-bottom: 1rem; opacity: 0.3;"></i>
                            <p>No messages sent yet. Start a conversation below.</p>
                        </div>
                    @endif
                </div>

                <div class="chat-composer"
                    style="padding: 1.5rem; background: #f8fafc; border-top: 1px solid #e2e8f0; border-radius: 0 0 16px 16px;">
                    @if(Auth::guard('crm')->user()->isAdmin() && optional($activeCrmWorkspace)->slug !== 'mybox-packaging-app')
                        {{-- Al Massa admins may reply to clients; other workspaces stay view-only for admins. --}}
                        <div style="text-align: center; color: #94a3b8; padding: 1rem;">
                            Admin can view chat only
                        </div>
                    @else
                        <form id="chat-form" action="{{ route('crm.messages.send', $email->id) }}" method="POST"
                            enctype="multipart/form-data">
                            {{ csrf_field() }}
                            <input type="hidden" name="email_subject" id="email_subject_field">
                            <input type="hidden" name="cc" id="cc_field">
                            <input type="hidden" name="bcc" id="bcc_field">
                            <div id="attachment-tray"
                                style="display:none; padding:10px; margin-bottom:10px; gap:10px; flex-wrap:wrap; background:#f8fafc; border:1px dashed #1f4574ff; border-radius:12px;">
                            </div>

                            <div class="chat-input-container" id="replyDropZone" style="display:block; position:relative; border-radius:12px; background:white; border:1px solid #cbd5e1; padding:10px; box-sizing:border-box; transition:.18s ease;">
                                <div id="replyDropOverlay" class="reply-drop-overlay">
                                    <i class="fas fa-cloud-upload-alt" style="font-size:2rem;"></i>
                                    <span>Drop files to attach</span>
                                    <small style="font-weight:600; color:var(--primary-purple);">They will appear in the attachment list</small>
                                </div>
                                <textarea name="message_body" id="message_body" rows="6" placeholder="Type your message..." style="width: 100%; border: none; outline: none; padding: 0.5rem; font-family: inherit; font-size: 1rem; color: #1e293b; box-sizing: border-box;"></textarea>
                                
                                <div class="chat-composer-actions">
                                    <label for="fileInput" class="chat-attach-button"
                                        style="background: #f8fafc; color: #64748b; font-size: 0.82rem; cursor: pointer; padding: 6px 12px; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; margin: 0; border: 1px solid #e2e8f0; font-weight: 600;"
                                        onmouseover="this.style.background='#eef2ff'; this.style.color='var(--primary-purple)'"
                                        onmouseout="this.style.background='#f8fafc'; this.style.color='#64748b'">
                                        <i class="fas fa-paperclip"></i> Attach Files
                                        <input type="file" id="fileInput" name="attachments[]" multiple style="display:none"
                                            onchange="window.handleFileSelect(this)">
                                    </label>
                                    
                                    <button type="button" id="send-btn" onclick="handleSendClick(event)" class="btn btn-primary chat-send-button" style="padding: 0.5rem 1.5rem; font-weight: 700; background: var(--primary-purple); color: white; border: none; border-radius: 8px; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-paper-plane" id="btn-icon"></i> <span>Send Reply</span>
                                    </button>
                                </div>
                            </div>
                            <div class="chat-recipient-note"
                                style="font-size: 0.75rem; color: #94a3b8; margin-top: 12px; display: flex; align-items: center; gap: 6px; justify-content: center;">
                                <i class="fas fa-shield-alt" style="font-size: 0.7rem;"></i> This message will be sent to
                                <strong>{{ $email->client_email }}</strong>
                            </div>
                        </form>
            @if($email->salesOrder)
                <div style="margin-top:1.25rem; padding:1rem; background:#ffffff; border:1px solid #e2e8f0; border-radius:14px; box-shadow:0 5px 16px rgba(15,23,42,.05);">
                    <a href="{{ route('crm.sales_orders.index') }}" style="width:100%; box-sizing:border-box; min-height:48px; display:flex; align-items:center; justify-content:center; gap:9px; padding:.75rem 1rem; background:#f8fafc; color:#334155; border:1px solid #cbd5e1; border-radius:10px; text-decoration:none; font-weight:800; transition:.18s ease;">
                        <i class="fas fa-file-invoice-dollar" style="color:var(--primary-purple);"></i> View Sales Order
                        <i class="fas fa-arrow-right" style="font-size:.75rem; color:#94a3b8;"></i>
                    </a>
                </div>
            @endif

            {{-- Legacy estimation card replaced by the dedicated Get Estimate ticket module. --}}
            @if(false)
                <style>
                    .estimation-panel {
                        border: 1px solid #e2e8f0;
                        border-left: 4px solid var(--primary-purple);
                        border-radius: 18px;
                        margin-top: 20px;
                        max-width: 540px;
                        background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
                        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
                        overflow: hidden;
                    }
                    .estimation-panel .panel-head {
                        display: flex;
                        align-items: center;
                        gap: 10px;
                        padding: 18px 20px 12px;
                        color: #0f172a;
                        font-size: 0.95rem;
                        font-weight: 800;
                        letter-spacing: 0.03em;
                        text-transform: uppercase;
                    }
                    .estimation-panel .panel-body {
                        padding: 0 20px 20px;
                    }
                    .estimation-panel .soft-box,
                    .estimation-panel .summary-box,
                    .estimation-panel .note-box {
                        border-radius: 14px;
                        border: 1px solid #e2e8f0;
                        background: #f8fafc;
                    }
                    .estimation-panel .summary-box {
                        background: #ffffff;
                        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
                    }
                    .estimation-panel .section-title {
                        font-size: 0.72rem;
                        color: #64748b;
                        text-transform: uppercase;
                        font-weight: 800;
                        letter-spacing: 0.08em;
                        margin-bottom: 6px;
                    }
                    .estimation-panel .value {
                        font-size: 2rem;
                        line-height: 1;
                        font-weight: 800;
                        color: #111827;
                    }
                    .estimation-panel .action-btn {
                        width: 100%;
                        padding: 0.85rem 1rem;
                        border: none;
                        border-radius: 12px;
                        font-weight: 800;
                        cursor: pointer;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        gap: 8px;
                        transition: transform 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
                    }
                    .estimation-panel .action-btn:hover { transform: translateY(-1px); }
                </style>
                <div class="estimation-panel">
                    <div class="panel-head"><i class="fas fa-file-invoice-dollar"></i> Estimation Details</div>
                    @if($email->estimator_id)
                        <div class="panel-body">
                            <div class="soft-box" style="padding: 16px 16px 8px;">
                            <p style="margin:0 0 10px;"><strong style="color:#334155;">Estimator:</strong> {{ $email->estimator->name ?? 'Unknown' }}</p>
                            <p style="margin:0 0 12px;"><strong style="color:#334155;">Status:</strong> <span style="display:inline-flex; align-items:center; padding: 5px 10px; border-radius: 999px; background: {{ $email->estimate_status == 'estimated' ? '#dcfce7' : ($email->estimate_status == 'change_requested' ? '#fee2e2' : '#fef3c7') }}; color: {{ $email->estimate_status == 'estimated' ? '#166534' : ($email->estimate_status == 'change_requested' ? '#991b1b' : '#92400e') }}; font-weight: 800; font-size: 0.72rem; text-transform: uppercase;">{{ str_replace('_', ' ', $email->estimate_status) }}</span></p>
                            @if(in_array($email->estimate_status, ['estimated', 'approved']))
                                @if($email->discount > 0)
                                    @php
                                        $subtotal = 0;
                                        if(is_array($email->estimate_breakdown)) {
                                            foreach($email->estimate_breakdown as $item) {
                                                $subtotal += (float)$item['price'];
                                            }
                                        }
                                        $qty = (float)($email->quantity ?: 1);
                                        $grossTotal = $subtotal * $qty;
                                        $discountPct = $grossTotal > 0 ? round(($email->discount / $grossTotal) * 100) : 0;
                                        $wasteMaterialAmount = (float)($email->waste_material_amount ?: 0);
                                    @endphp
                                    <div style="display: flex; border-radius: 14px; overflow: hidden; margin-top: 14px; box-shadow: 0 10px 24px rgba(15,23,42,0.12);">
                                        <!-- Left Pane (Green) -->
                                        <div style="background: linear-gradient(180deg, #a3d63a 0%, #8dc63f 100%); color: #0f172a; padding: 16px 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 92px; border-right: 2px dashed rgba(15,23,42,0.45); position: relative;">
                                            <i class="fas fa-tags" style="font-size: 1.25rem; margin-bottom: 5px;"></i>
                                            <div style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">Discount</div>
                                            <div style="font-size: 1.5rem; font-weight: 900; line-height: 1.1;">{{ $discountPct }}%</div>
                                            <div style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">OFF</div>
                                        </div>
                                        <!-- Right Pane (Dark Slate) -->
                                        <div style="background: linear-gradient(180deg, #1e293b 0%, #111827 100%); color: white; padding: 16px; flex: 1; display: flex; flex-direction: column; justify-content: center; position: relative; text-align: center;">
                                            <div style="color: #8dc63f; font-size: 1rem; margin-bottom: 4px;"><i class="fas fa-star"></i></div>
                                            <div style="font-size: 0.82rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 4px; opacity: 0.95;">Special Discount</div>
                                            <div style="font-size: 1.25rem; font-weight: 800; color: #8dc63f;">-${{ number_format($email->discount, 2) }}</div>
                                        </div>
                                    </div>
                                @endif

                                @php
                                    $quantityOptions = is_array($email->estimate_quantity_options) ? array_values($email->estimate_quantity_options) : [];
                                @endphp
                                <div class="summary-box" style="display: flex; flex-direction: column; gap: 14px; margin-top: 14px; padding: 18px; text-align: center;">
                                    <div>
                                        <div class="section-title">Final Estimated Price</div>
                                        <div class="value">${{ number_format((float)$email->estimated_price, 2) }}</div>
                                    </div>
                                    @if(count($quantityOptions) > 0)
                                        <div style="display:grid; grid-template-columns:1fr; gap:8px; text-align:left;">
                                            @foreach($quantityOptions as $idx => $option)
                                                @php
                                                    $optionQty = (float)($option['quantity'] ?? 0);
                                                    $optionPrice = (float)($option['price'] ?? 0);
                                                    $optionUnitPrice = $option['unit_price'] ?? ($optionQty > 0 ? $optionPrice / $optionQty : 0);
                                                @endphp
                                                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; padding:10px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px;">
                                                    <span style="font-size:0.82rem; font-weight:800; color:#475569;">{{ number_format((int)($option['quantity'] ?? 0)) }} pcs</span>
                                                    <span style="font-size:0.88rem; font-weight:900; color:#334155;">${{ number_format((float)$optionUnitPrice, 2) }}/unit</span>
                                                    <span style="font-size:0.98rem; font-weight:900; color:#0f172a;">${{ number_format((float)($option['price'] ?? 0), 2) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                    <button type="button" onclick="document.getElementById('estimationModal').style.display='flex'" class="action-btn" style="background: #475569; color: white; box-shadow: 0 8px 18px rgba(71, 85, 105, 0.16);">
                                        <i class="fas fa-eye"></i> View Full Breakdown
                                    </button>
                                </div>

                                <!-- Estimation Modal -->
                                <div id="estimationModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(4px);">
                                    <div style="width: 100%; max-width: 620px; max-height: 90vh; overflow-y: auto; background: #fff; border-radius: 18px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; position: relative;">
                                        <!-- Close button floating top right -->
                                        <button type="button" onclick="document.getElementById('estimationModal').style.display='none'" style="position: absolute; top: 15px; right: 20px; background: rgba(255,255,255,0.2); border: none; font-size: 1.5rem; cursor: pointer; color: white; line-height: 1; border-radius: 50%; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; z-index: 10; transition: background 0.2s;" onmouseover="this.style.background='rgba(255,255,255,0.4)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">&times;</button>

                                        <!-- Pamphlet Header -->
                                        <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 34px 30px; text-align: center; color: white; position: relative;">
                                            <i class="fas fa-box-open" style="font-size: 3rem; color: #8dc63f; margin-bottom: 15px;"></i>
                                            <h1 style="margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: 0.6px; text-transform: uppercase;">Custom Packaging Quote</h1>
                                            <p style="margin: 10px 0 0; color: #94a3b8; font-size: 1rem;">Prepared exclusively for you</p>
                                        </div>

                                        <!-- Pamphlet Body -->
                                        <div style="padding: 30px 30px;">
                                            <h2 style="margin: 0 0 18px; font-size: 1.15rem; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; text-transform: uppercase; letter-spacing: 0.6px;">Order Specifications</h2>
                                            
                                            <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 30px;">
                                                @if(is_array($email->estimate_breakdown) && count($email->estimate_breakdown) > 0)
                                                    @foreach($email->estimate_breakdown as $item)
                                                    <div style="background: #f8fafc; padding: 14px 15px; border-radius: 12px; border: 1px solid #e2e8f0; border-left: 4px solid #8dc63f; flex: 1 1 calc(50% - 15px); min-width: 200px; box-sizing: border-box;">
                                                        <div style="font-size: 0.8rem; color: #64748b; text-transform: uppercase; font-weight: bold; margin-bottom: 4px;">{{ $item['name'] }}</div>
                                                        <div style="font-size: 1.1rem; font-weight: 800; color: #0f172a;">${{ number_format((float)$item['price'], 2) }}</div>
                                                    </div>
                                                    @endforeach
                                                @else
                                                    <div style="background: #f8fafc; padding: 14px 15px; border-radius: 12px; border: 1px solid #e2e8f0; border-left: 4px solid #8dc63f; width: 100%;">
                                                        <div style="font-size: 0.8rem; color: #64748b; text-transform: uppercase; font-weight: bold; margin-bottom: 4px;">General Estimate</div>
                                                        <div style="font-size: 1.1rem; font-weight: 800; color: #0f172a;">${{ number_format((float)$email->estimated_price, 2) }}</div>
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Pricing Summary Area -->
                                            @php
                                                $subtotal = 0;
                                                if(is_array($email->estimate_breakdown)) {
                                                    foreach($email->estimate_breakdown as $item) {
                                                        $subtotal += (float)$item['price'];
                                                    }
                                                }
                                                $qty = (float)($email->quantity ?: 1);
                                                $discount = (float)($email->discount ?: 0);
                                                $wasteMaterialPct = (float)($email->waste_material_percentage ?: 0);
                                                $wasteMaterialAmount = (float)($email->waste_material_amount ?: 0);
                                                $grossTotal = $subtotal * $qty;
                                                $modalDiscountPct = $grossTotal > 0 ? round(($discount / $grossTotal) * 100) : 0;
                                            @endphp
                                            <div style="background: #f1f5f9; padding: 22px; border-radius: 16px; margin-bottom: 18px; border: 1px solid #e2e8f0;">
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 1.1rem;">
                                                    <span style="color: #475569; font-weight: 600;">Unit Price (Subtotal)</span>
                                                    <strong style="color: #1e293b;">${{ number_format($subtotal, 2) }}</strong>
                                                </div>
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 1.1rem; {{ $discount == 0 ? 'border-bottom: 1px solid #cbd5e1; padding-bottom: 15px;' : '' }}">
                                                    <span style="color: #475569; font-weight: 600;">Order Quantity</span>
                                                    <strong style="color: #1e293b;">x {{ $qty }} units</strong>
                                                </div>

                                                @if($wasteMaterialAmount > 0)
                                                <div style="display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 1.1rem; color: #0f766e;">
                                                    <span style="font-weight: 600;">Waste Material ({{ rtrim(rtrim(number_format($wasteMaterialPct, 2), '0'), '.') }}%)</span>
                                                    <strong>+${{ number_format($wasteMaterialAmount, 2) }}</strong>
                                                </div>
                                                @endif

                                                <!-- Discount Ticket -->
                                                @if($discount > 0)
                                                <div style="display: flex; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); width: 100%; margin-bottom: 15px; margin-top: 15px;">
                                                    <!-- Left Pane (Green) -->
                                                    <div style="background: linear-gradient(180deg, #a3d63a 0%, #8dc63f 100%); color: #1e293b; padding: 15px 10px; display: flex; flex-direction: column; align-items: center; justify-content: center; width: 92px; border-right: 2px dashed rgba(15,23,42,0.45); position: relative;">
                                                        <i class="fas fa-tags" style="font-size: 1.25rem; margin-bottom: 5px;"></i>
                                                        <div style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">Discount</div>
                                                        <div style="font-size: 1.5rem; font-weight: 900; line-height: 1.1;">{{ $modalDiscountPct }}%</div>
                                                        <div style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">OFF</div>
                                                    </div>
                                                    <!-- Right Pane (Dark Slate) -->
                                                    <div style="background: linear-gradient(180deg, #1e293b 0%, #111827 100%); color: white; padding: 16px; flex: 1; display: flex; flex-direction: column; justify-content: center; position: relative; text-align: center;">
                                                        <div style="color: #8dc63f; font-size: 1rem; margin-bottom: 4px;"><i class="fas fa-star"></i></div>
                                                        <div style="font-size: 0.82rem; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 4px; opacity: 0.95;">Special Discount</div>
                                                        <div style="font-size: 1.25rem; font-weight: 800; color: #8dc63f;">-${{ number_format($discount, 2) }}</div>
                                                    </div>
                                                </div>
                                                @endif
                                                
                                                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #cbd5e1; padding-top: 15px;">
                                                    <span style="color: #0f172a; font-size: 1.2rem; font-weight: 800;">Gross Total</span>
                                                    <strong style="color: #0f172a; font-size: 1.4rem;">${{ number_format($grossTotal + $wasteMaterialAmount - $discount, 2) }}</strong>
                                                </div>
                                            </div>
                                            @php
                                                $otherQuantityOptions = array_slice($quantityOptions, 1);
                                            @endphp
                                            @if(count($otherQuantityOptions) > 0)
                                            <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; padding:18px; margin-bottom:18px;">
                                                <div style="font-size:0.82rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:12px;">Other Price Options</div>
                                                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; padding:7px 0; color:#64748b; font-size:0.75rem; font-weight:800; text-transform:uppercase;">
                                                    <span>QTY</span><span>Price Per Unit</span><span style="text-align:right;">Price</span>
                                                </div>
                                                @foreach($otherQuantityOptions as $option)
                                                    @php
                                                        $optionQty = (float)($option['quantity'] ?? 0);
                                                        $optionPrice = (float)($option['price'] ?? 0);
                                                        $optionUnitPrice = $option['unit_price'] ?? ($optionQty > 0 ? $optionPrice / $optionQty : 0);
                                                    @endphp
                                                    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; padding:9px 0; border-top:1px solid #f1f5f9;">
                                                        <span style="font-weight:700; color:#475569;">{{ number_format((int)($option['quantity'] ?? 0)) }} pcs</span>
                                                        <strong style="color:#334155;">${{ number_format((float)$optionUnitPrice, 2) }}</strong>
                                                        <strong style="color:#0f172a; text-align:right;">${{ number_format((float)($option['price'] ?? 0), 2) }}</strong>
                                                    </div>
                                                @endforeach
                                            </div>
                                            @endif
                                        </div>

                                        <div style="padding: 15px 30px; background: #f8fafc; text-align: right; border-top: 1px solid #e2e8f0;">
                                            <button type="button" onclick="document.getElementById('estimationModal').style.display='none'" class="action-btn" style="width:auto; display:inline-flex; padding: 0.65rem 1.35rem; background: white; color: #475569; border: 1px solid #cbd5e1; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">Close</button>
                                        </div>
                                    </div>
                                </div>

                                @if($email->sales_agent_notes)
                                    <div class="note-box" style="margin-top: 15px; padding: 14px 14px 13px; background: #f0fdf4; border-left: 4px solid #4ade80;">
                                        <strong style="color: #14532d; font-size: 0.85rem;"><i class="fas fa-comment-dots"></i> Sales Agent Request Notes:</strong><br/>
                                        <div style="margin-top: 4px; font-size: 0.9rem; color: #064e3b;">{!! nl2br(e($email->sales_agent_notes)) !!}</div>
                                    </div>
                                @endif

                                @if($email->estimator_notes)
                                    <div class="note-box" style="margin-top: 15px; padding: 14px 14px 13px; background: #fffbeb; border-left: 4px solid #fbbf24;">
                                        <strong style="color: #b45309; font-size: 0.85rem;"><i class="fas fa-sticky-note"></i> Estimator Notes:</strong><br/>
                                        <div style="margin-top: 4px; font-size: 0.9rem; color: #78350f;">{!! nl2br(e($email->estimator_notes)) !!}</div>
                                    </div>
                                @endif

                                @if(in_array($email->estimate_status, ['estimated', 'approved']) && !Auth::guard('crm')->user()->isEstimator())
                                    <button type="button" onclick="sendEstimateToChat()" class="action-btn" style="margin-top: 15px; background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%); color: white; box-shadow: 0 8px 18px rgba(14, 165, 233, 0.18);">
                                        <i class="fas fa-paper-plane"></i> Send Estimate to Client via Chat
                                    </button>
                                    
                                    <script>
                                    function sendEstimateToChat() {
                                        @php
                                            $jsSubtotal = 0;
                                            if(is_array($email->estimate_breakdown)) {
                                                foreach($email->estimate_breakdown as $item) {
                                                    $jsSubtotal += (float)$item['price'];
                                                }
                                            }
                                            $jsQty = (float)($email->quantity ?: 1);
                                            $jsDiscount = (float)($email->discount ?: 0);
                                            $jsWasteMaterialPct = (float)($email->waste_material_percentage ?: 0);
                                            $jsWasteMaterialAmount = (float)($email->waste_material_amount ?: 0);
                                            $jsQuantityOptions = is_array($email->estimate_quantity_options) ? array_values($email->estimate_quantity_options) : [];
                                            $jsOtherQuantityOptions = array_slice($jsQuantityOptions, 1);
                                            $jsTotal = (float)$email->estimated_price;
                                        @endphp

                                        let html = `
                                        <div style="width: 100%; max-width: 600px; margin: 15px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1); font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; border: 1px solid #e5e7eb;">
                                            <!-- Pamphlet Header -->
                                            <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 40px 30px; text-align: center; color: white; position: relative;">
                                                <i class="fas fa-box-open" style="font-size: 3rem; color: #8dc63f; margin-bottom: 15px;"></i>
                                                <h1 style="margin: 0; font-size: 2.2rem; font-weight: 800; letter-spacing: 1px; text-transform: uppercase;">Custom Packaging Quote</h1>
                                                <p style="margin: 10px 0 0; color: #94a3b8; font-size: 1.1rem;">Prepared exclusively for you</p>
                                            </div>

                                            <!-- Pamphlet Body -->
                                            <div style="padding: 35px 30px;">
                                                <h2 style="margin: 0 0 20px; font-size: 1.3rem; color: #1e293b; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">Order Specifications</h2>
                                                
                                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; min-width: 100%; margin-bottom: 30px;">
                                                    @if(is_array($email->estimate_breakdown) && count($email->estimate_breakdown) > 0)
                                                        @php $breakdownCount = 0; @endphp
                                                        @foreach($email->estimate_breakdown as $item)
                                                            @if($breakdownCount % 2 == 0)
                                                                <tr>
                                                            @endif
                                                            <td width="50%" valign="top" style="width: 50%; padding: 7px;">
                                                                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border-left: 3px solid #8dc63f; box-sizing: border-box;">
                                                                    <div style="font-size: 0.8rem; color: #64748b; text-transform: uppercase; font-weight: bold; margin-bottom: 4px;">{{ $item['name'] }}</div>
                                                                    <div style="font-size: 1.1rem; font-weight: 800; color: #0f172a;">${{ number_format((float)$item['price'], 2) }}</div>
                                                                </div>
                                                            </td>
                                                            @php $breakdownCount++; @endphp
                                                            @if($breakdownCount % 2 == 0 || $loop->last)
                                                                @if($loop->last && $breakdownCount % 2 != 0)
                                                                    <td width="50%" style="width: 50%; padding: 7px;"></td>
                                                                @endif
                                                                </tr>
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        <tr>
                                                            <td width="100%" style="padding: 7px;">
                                                                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border-left: 3px solid #8dc63f; width: 100%;">
                                                                    <div style="font-size: 0.8rem; color: #64748b; text-transform: uppercase; font-weight: bold; margin-bottom: 4px;">General Estimate</div>
                                                                    <div style="font-size: 1.1rem; font-weight: 800; color: #0f172a;">${{ number_format((float)$email->estimated_price, 2) }}</div>
                                                                </div>
                                                            </td>
                                                        </tr>
	                                                    @endif
	                                                </table>

			                                                <!-- Pricing Summary Area -->
                                                <div style="background: #f1f5f9; padding: 25px; border-radius: 12px; margin-bottom: 20px;">
                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; min-width: 100%;">
                                                        <tr>
                                                            <td width="50%" align="left" style="padding-bottom: 12px; color: #475569; font-weight: 600; font-size: 1.1rem; width: 50%;">Unit Price (Subtotal)</td>
                                                            <td width="50%" align="right" style="padding-bottom: 12px; color: #1e293b; font-weight: bold; font-size: 1.1rem; width: 50%; text-align: right;">$${({{ $jsSubtotal }}).toFixed(2)}</td>
                                                        </tr>
                                                        <tr>
                                                            <td width="50%" align="left" style="padding-bottom: 15px; color: #475569; font-weight: 600; font-size: 1.1rem; width: 50%; ${({{ $jsDiscount + $jsWasteMaterialAmount }} > 0) ? '' : 'border-bottom: 1px solid #cbd5e1;'}">Order Quantity</td>
                                                            <td width="50%" align="right" style="padding-bottom: 15px; color: #1e293b; font-weight: bold; font-size: 1.1rem; width: 50%; text-align: right; ${({{ $jsDiscount + $jsWasteMaterialAmount }} > 0) ? '' : 'border-bottom: 1px solid #cbd5e1;'}">x {{ $jsQty }} units</td>
                                                        </tr>
                                                    </table>`;

                                        @if($jsWasteMaterialAmount > 0)
                                        html += `
                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; min-width: 100%; margin-bottom: 15px;">
                                                        <tr>
                                                            <td width="50%" align="left" style="color: #0f766e; font-weight: 700; font-size: 1.05rem; width: 50%;">Waste Material ({{ rtrim(rtrim(number_format($jsWasteMaterialPct, 2), '0'), '.') }}%)</td>
                                                            <td width="50%" align="right" style="color: #0f766e; font-weight: bold; font-size: 1.05rem; width: 50%; text-align: right;">+$${({{ $jsWasteMaterialAmount }}).toFixed(2)}</td>
                                                        </tr>
                                                    </table>`;
                                        @endif

                                        @if($jsDiscount > 0)
                                        @php
                                            $grossTotal = $jsSubtotal * $jsQty;
                                            $modalDiscountPct = $grossTotal > 0 ? round(($jsDiscount / $grossTotal) * 100) : 0;
                                        @endphp
                                        html += `
                                                    <!-- Discount Ticket -->
                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; min-width: 100%; margin-bottom: 15px; margin-top: 15px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                                                        <tr>
                                                            <td width="30%" align="center" style="width: 30%; background-color: #8dc63f; color: #1e293b; padding: 15px 10px; border-right: 2px dashed #1e293b; vertical-align: middle; text-align: center;">
                                                                <div style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">Discount</div>
                                                                <div style="font-size: 1.5rem; font-weight: 900; line-height: 1.1;">{{ $modalDiscountPct }}%</div>
                                                                <div style="font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">OFF</div>
                                                            </td>
                                                            <td width="70%" align="center" style="width: 70%; background-color: #1e293b; color: white; padding: 15px; vertical-align: middle; text-align: center;">
                                                                <div style="font-size: 0.95rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 4px;">Special Discount</div>
                                                                <div style="font-size: 1.25rem; font-weight: 800; color: #8dc63f;">-$${({{ $jsDiscount }}).toFixed(2)}</div>
                                                            </td>
                                                        </tr>
                                                    </table>`;
                                        @endif

                                        html += `
                                                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="width: 100%; min-width: 100%;">
                                                        <tr>
                                                            <td width="50%" align="left" style="width: 50%; padding-top: 15px; border-top: 2px dashed #cbd5e1; color: #0f172a; font-size: 1.2rem; font-weight: 800;">Gross Total</td>
                                                            <td width="50%" align="right" style="width: 50%; padding-top: 15px; border-top: 2px dashed #cbd5e1; color: #0f172a; font-size: 1.4rem; font-weight: bold; text-align: right;">$${({{ $jsSubtotal * $jsQty + $jsWasteMaterialAmount - $jsDiscount }}).toFixed(2)}</td>
                                                        </tr>
                                                    </table>
                                                </div>

                                        @if(count($jsOtherQuantityOptions) > 0)
                                        html += `
                                                    <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:12px; padding:16px; margin-bottom:25px;">
                                                        <div style="font-size:0.85rem; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.06em; margin-bottom:12px;">Other Price Options</div>
                                                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%; min-width:100%;">
                                                            <tr>
                                                                <td width="33%" align="left" style="padding:8px 0; color:#64748b; font-weight:800; font-size:0.75rem;">QTY</td>
                                                                <td width="33%" align="left" style="padding:8px 0; color:#64748b; font-weight:800; font-size:0.75rem;">Price Per Unit</td>
                                                                <td width="34%" align="right" style="padding:8px 0; color:#64748b; font-weight:800; font-size:0.75rem; text-align:right;">Price</td>
                                                            </tr>
                                                            @foreach($jsOtherQuantityOptions as $option)
                                                            @php
                                                                $optionQty = (float)($option['quantity'] ?? 0);
                                                                $optionPrice = (float)($option['price'] ?? 0);
                                                                $optionUnitPrice = $option['unit_price'] ?? ($optionQty > 0 ? $optionPrice / $optionQty : 0);
                                                            @endphp
                                                            <tr>
                                                                <td width="33%" align="left" style="padding:8px 0; color:#475569; font-weight:700; border-top:1px solid #f1f5f9;">{{ number_format((int)($option['quantity'] ?? 0)) }}</td>
                                                                <td width="33%" align="left" style="padding:8px 0; color:#334155; font-weight:900; border-top:1px solid #f1f5f9;">${{ number_format((float)$optionUnitPrice, 2) }}</td>
                                                                <td width="34%" align="right" style="padding:8px 0; color:#0f172a; font-weight:900; text-align:right; border-top:1px solid #f1f5f9;">${{ number_format((float)($option['price'] ?? 0), 2) }}</td>
                                                            </tr>
                                                            @endforeach
                                                        </table>
                                                    </div>`;
                                        @endif

	                                            </div>
                                        </div>
                                        <div style="background: var(--primary-purple); color: #ffffff; padding: 12px 18px; border-radius: 14px; border-bottom-right-radius: 4px; display: inline-block; margin-top: 15px; font-size: 15px; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); float: right;">
                                            Hi {{ $email->client_name ?: 'there' }}, I've prepared your custom estimate above. Let me know what you think!
                                        </div>
                                        <div style="clear: both;"></div>
                                        `;

                                        if (window.CKEDITOR && CKEDITOR.instances.message_body) {
                                            let currentData = CKEDITOR.instances.message_body.getData();
                                            CKEDITOR.instances.message_body.setData(html + '<br/>' + currentData);
                                            // Scroll to the chat editor
                                            let editorElement = document.getElementById('message_body');
                                            if (editorElement) {
                                                editorElement.scrollIntoView({behavior: 'smooth', block: 'center'});
                                            }
                                        } else {
                                            // Fallback if CKEditor is not initialized
                                            let textarea = document.getElementById('message_body');
                                            if (textarea) {
                                                textarea.value = html + '\n' + textarea.value;
                                                textarea.scrollIntoView({behavior: 'smooth', block: 'center'});
                                            }
                                        }

                                        // Close the estimation modal if it is open
                                        let estModal = document.getElementById('estimationModal');
                                        if (estModal) {
                                            estModal.style.display = 'none';
                                        }
                                    }
                                    </script>
                                @endif
                                
                                @if($email->estimate_status == 'estimated')
                                    <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                                        <form action="{{ route('crm.emails.approve_estimate', $email->id) }}" method="POST" style="width: 100%;">
                                            {{ csrf_field() }}
                                            <button type="submit" style="width: 100%; padding: 0.75rem; background: #16a34a; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; box-shadow: 0 2px 4px rgba(22, 163, 74, 0.2);">
                                                <i class="fas fa-check-circle"></i> Approve Estimate
                                            </button>
                                        </form>
                                        <button type="button" onclick="document.getElementById('rejectEstimateForm').style.display='block'" style="width: 100%; padding: 0.75rem; background: white; color: #ef4444; border: 1px solid #ef4444; border-radius: 6px; font-weight: bold; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px;">
                                            <i class="fas fa-undo"></i> Request Changes
                                        </button>
                                    </div>
                                    <div id="rejectEstimateForm" style="display: none; margin-top: 10px; padding: 10px; border: 1px solid #fecaca; background: #fef2f2; border-radius: 6px;">
                                        <form action="{{ route('crm.emails.reject_estimate', $email->id) }}" method="POST">
                                            {{ csrf_field() }}
                                            <label style="display:block; font-size:0.75rem; font-weight:600; color:#991b1b; margin-bottom:4px;">Reason for Changes</label>
                                            <textarea name="rejection_reason" required rows="2" placeholder="e.g. Shipping cost is too high, please recalculate." style="width:100%; padding:0.5rem; border:1px solid #fca5a5; border-radius:4px; margin-bottom:8px; font-size:0.85rem;"></textarea>
                                            <div style="display: flex; gap: 8px;">
                                                <button type="button" onclick="document.getElementById('rejectEstimateForm').style.display='none'" style="padding: 0.4rem 0.8rem; background: white; color: #64748b; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 0.8rem; cursor: pointer;">Cancel</button>
                                                <button type="submit" style="padding: 0.4rem 0.8rem; background: #ef4444; color: white; border: none; border-radius: 4px; font-size: 0.8rem; cursor: pointer;">Send Back to Estimator</button>
                                            </div>
                                        </form>
                                    </div>
                                @endif
                                
                                @if($canCreateSalesOrder)
                                    <div style="margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                                        <button type="button" onclick="document.getElementById('createSalesOrderModal').style.display='flex'" style="width: 100%; padding: 0.75rem; background: var(--primary-purple); color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; box-shadow: 0 2px 8px var(--primary-shadow);">
                                            <i class="fas fa-file-invoice-dollar"></i> Create Sales Order
                                        </button>
                                    </div>

                                    <!-- Create Sales Order Modal -->
                                    <div id="createSalesOrderModal" class="sales-order-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 9999; align-items: center; justify-content: center;">
                                        <div class="sales-order-dialog" style="background: white; border-radius: 16px; width: 90%; max-width: 500px; padding: 2rem; box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
                                            <div class="sales-order-head" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                                                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 700;">Create Sales Order</h3>
                                                <i class="fas fa-times sales-order-close" style="cursor: pointer; color: #94a3b8; font-size: 1.2rem;" onclick="document.getElementById('createSalesOrderModal').style.display='none'"></i>
                                            </div>
                                            <form class="sales-order-form" action="{{ route('crm.sales_orders.store') }}" method="POST">
                                                {{ csrf_field() }}
                                                <input type="hidden" name="crm_email_id" value="{{ $email->id }}">
                                                @if(count($salesOrderQuantityOptions) > 0)
                                                <div class="sales-order-field" style="margin-bottom: 1rem;">
                                                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 0.5rem;">Selected Quantity Option</label>
                                                    <select class="sales-order-select" id="sales_order_option" name="estimate_option_index" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; outline: none;">
                                                        @foreach($salesOrderQuantityOptions as $idx => $option)
                                                            <option value="{{ $idx }}" data-floor="{{ (float)($option['team_lead_price'] ?? $option['price'] ?? 0) }}" data-offer="{{ (float)($option['price'] ?? 0) }}" data-qty="{{ (int)($option['quantity'] ?? 0) }}">{{ number_format((int)($option['quantity'] ?? 0)) }} pcs — {{ $salesOrderCurrency }} {{ number_format((float)($option['price'] ?? 0), 2) }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @endif
                                                <div class="sales-order-field" style="margin-bottom:1rem;padding:1rem;border:1px solid var(--primary-shadow);border-radius:12px;background:var(--primary-soft);">
                                                    <label style="display:block;font-size:.85rem;font-weight:700;color:#1e293b;margin-bottom:.5rem;">Sales Offer Price</label>
                                                    <div style="display:flex;align-items:center;gap:.5rem;background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:0 .75rem;"><strong>{{ $salesOrderCurrency }}</strong><input id="sales_offer_price" name="sales_offer_price" type="number" step="0.01" min="0" required style="width:100%;padding:.75rem 0;border:0;outline:0;font-size:.95rem;font-weight:750;"></div>
                                                    <div id="sales_offer_help" style="margin-top:.45rem;color:#64748b;font-size:.72rem;"></div>
                                                </div>
                                                
                                                <div class="sales-order-field" style="margin-bottom: 1rem;">
                                                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 0.5rem;">Payment Terms</label>
                                                    <select class="sales-order-select" name="payment_term" id="payment_term_select" required style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; outline: none;" onchange="toggleCreditDays()">
                                                        <option value="50_advance" selected>50% Advance Required (Default)</option>
                                                        <option value="100_deposit">100% Deposit Required</option>
                                                        <option value="credit">Credit Customer (Net Terms)</option>
                                                    </select>
                                                </div>

                                                <div id="credit_days_container" class="sales-order-field" style="display: none; margin-bottom: 1rem;">
                                                    <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #1e293b; margin-bottom: 0.5rem;">Credit Terms</label>
                                                    <select class="sales-order-select" name="credit_days" style="width: 100%; padding: 0.75rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; outline: none;">
                                                        <option value="15">Net 15</option>
                                                        <option value="30" selected>Net 30</option>
                                                        <option value="45">Net 45</option>
                                                        <option value="60">Net 60</option>
                                                    </select>
                                                </div>

                                                <button class="sales-order-submit" type="submit" style="width: 100%; padding: 0.85rem; background: #16a34a; color: white; border: none; border-radius: 8px; font-weight: bold; font-size: 1rem; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px;">
                                                    <i class="fas fa-check"></i> Generate Order
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <script>
                                        function toggleCreditDays() {
                                            const term = document.getElementById('payment_term_select').value;
                                            document.getElementById('credit_days_container').style.display = (term === 'credit') ? 'block' : 'none';
                                        }
                                        (function () {
                                            const optionSelect = document.getElementById('sales_order_option');
                                            const offerInput = document.getElementById('sales_offer_price');
                                            const offerHelp = document.getElementById('sales_offer_help');
                                            const currency = @json($salesOrderCurrency);
                                            const syncOfferFloor = function () {
                                                const option = optionSelect ? optionSelect.options[optionSelect.selectedIndex] : null;
                                                const floor = option ? Number(option.dataset.floor || 0) : Number(@json((float)($email->estimated_price ?? 0)));
                                                const qty = option ? Number(option.dataset.qty || 0) : Number(@json((int)($email->quantity ?? 0)));
                                                offerInput.min = floor.toFixed(2);
                                                offerInput.value = Number(option ? option.dataset.offer || floor : floor).toFixed(2);
                                                offerHelp.textContent = 'Minimum approved: '+currency+' '+floor.toFixed(2)+(qty > 0 ? ' · '+currency+' '+(floor/qty).toFixed(4)+' per unit' : '')+'. You may increase this price, but cannot reduce it.';
                                            };
                                            if (optionSelect) optionSelect.addEventListener('change', syncOfferFloor);
                                            syncOfferFloor();
                                        })();
                                    </script>

                                @endif

                                @php
                                    $rejectionLog = $email->status === 'Rejected' ? \App\CrmRejectionLog::where('crm_email_id', $email->id)->first() : null;
                                @endphp
                                @if($rejectionLog)
                                    <div style="margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                                        <div style="background: linear-gradient(135deg,#fff7ed,#ffedd5); border:1px solid #fdba74; border-radius: 12px; padding: 1rem;">
                                            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; margin-bottom:0.75rem;">
                                                <div style="font-weight:800; color:#9a3412;"><i class="fas fa-handshake"></i> Retention Follow-up</div>
                                                <span style="font-size:0.7rem; font-weight:700; color:#9a3412; background:#ffedd5; padding:0.2rem 0.55rem; border-radius:999px;">{{ strtoupper(str_replace('_', ' ', $rejectionLog->status)) }}</span>
                                            </div>
                                            <div style="color:#7c2d12; font-size:0.85rem; margin-bottom:0.6rem;"><strong>Reason:</strong> {{ $rejectionLog->rejection_reason }}</div>
                                            @if($rejectionLog->follow_up_notes)
                                                <div style="color:#7c2d12; font-size:0.85rem; margin-bottom:0.75rem;"><strong>Latest Notes:</strong> {{ $rejectionLog->follow_up_notes }}</div>
                                            @endif
                                            @if($rejectionLog->offered_options)
                                                <div style="color:#7c2d12; font-size:0.85rem; margin-bottom:0.75rem;"><strong>Offered Options:</strong> {{ is_array($rejectionLog->offered_options) ? implode(', ', $rejectionLog->offered_options) : $rejectionLog->offered_options }}</div>
                                            @endif
                                            @if(Auth::guard('crm')->user()->isAdmin() || Auth::guard('crm')->user()->isSalesManager() || Auth::guard('crm')->user()->isRetention())
                                                <form action="{{ route('crm.emails.retention_update', $email->id) }}" method="POST">
                                                    {{ csrf_field() }}
                                                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem; margin-bottom:0.75rem;">
                                                        <div>
                                                            <label style="display:block; font-size:0.75rem; font-weight:700; color:#7c2d12; margin-bottom:4px;">Retention Status</label>
                                                            <select name="status" style="width:100%; padding:0.6rem; border:1px solid #fdba74; border-radius:6px;">
                                                                <option value="pending" {{ $rejectionLog->status === 'pending' ? 'selected' : '' }}>Pending</option>
                                                                <option value="offered_options" {{ $rejectionLog->status === 'offered_options' ? 'selected' : '' }}>Offer Options Sent</option>
                                                                <option value="resolved_interested" {{ $rejectionLog->status === 'resolved_interested' ? 'selected' : '' }}>Customer Interested Again</option>
                                                                <option value="lost_quote" {{ $rejectionLog->status === 'lost_quote' ? 'selected' : '' }}>Lost Quote</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label style="display:block; font-size:0.75rem; font-weight:700; color:#7c2d12; margin-bottom:4px;">Offer Options</label>
                                                            <input type="text" name="offered_options[]" value="{{ old('offered_options.0', is_array($rejectionLog->offered_options) ? ($rejectionLog->offered_options[0] ?? '') : '') }}" placeholder="e.g. Discount" style="width:100%; padding:0.6rem; border:1px solid #fdba74; border-radius:6px;">
                                                        </div>
                                                    </div>
                                                    <div style="margin-bottom:0.75rem;">
                                                        <label style="display:block; font-size:0.75rem; font-weight:700; color:#7c2d12; margin-bottom:4px;">Follow-up Notes</label>
                                                        <textarea name="follow_up_notes" rows="3" style="width:100%; padding:0.6rem; border:1px solid #fdba74; border-radius:6px;" placeholder="Call outcome, revised price, next follow-up">{{ $rejectionLog->follow_up_notes }}</textarea>
                                                    </div>
                                                    <button type="submit" style="width:100%; padding:0.75rem; background:#ea580c; color:white; border:none; border-radius:6px; font-weight:700; cursor:pointer;">Update Retention Log</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @elseif($email->salesOrder)
                                    <div style="margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                                        <a href="{{ route('crm.sales_orders.index') }}" style="text-decoration:none; width: 100%; padding: 0.75rem; background: #f8fafc; color: #1e293b; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: bold; display: flex; justify-content: center; align-items: center; gap: 8px;">
                                            <i class="fas fa-eye"></i> View Sales Order
                                        </a>
                                    </div>
                                @endif
                            @endif
                        </div>
                    @else
                        <form action="{{ route('crm.emails.request_estimate', $email->id) }}" method="POST">
                            {{ csrf_field() }}
                            <div style="margin-bottom: 0.75rem;">
                                <label style="display:block; font-size:0.75rem; font-weight:600; margin-bottom:4px;">Select Estimator</label>
                                <select name="estimator_id" required style="width:100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                                    <option value="">-- Choose Estimator --</option>
                                    @foreach($estimators as $estimator)
                                        <option value="{{ $estimator->id }}">{{ $estimator->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div style="margin-bottom: 0.75rem;">
                                <label style="display:block; font-size:0.75rem; font-weight:600; margin-bottom:4px;">Notes</label>
                                <input type="text" name="notes" placeholder="e.g. Please quote ASAP" style="width:100%; padding: 0.6rem; border: 1px solid #cbd5e1; border-radius: 6px;">
                            </div>
                            <button type="submit" style="width:100%; padding: 0.6rem; background: var(--primary-color); color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">Request Estimate</button>
                        </form>
                    @endif
                </div>
            @endif
                    @endif
                </div>
            </div>
            <div id="emailMetaModal" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,.55); z-index:99999; align-items:center; justify-content:center; padding:20px;">
                <div style="width:100%; max-width:560px; background:#fff; border-radius:18px; box-shadow:0 24px 60px rgba(15,23,42,.25); overflow:hidden;">
                    <div style="padding:18px 22px; border-bottom:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between;">
                        <div style="font-size:1rem; font-weight:800; color:#0f172a;">Email Details</div>
                        <button type="button" onclick="closeEmailMetaModal()" style="border:none; background:none; font-size:1.2rem; color:#94a3b8; cursor:pointer;"><i class="fas fa-times"></i></button>
                    </div>
                    <div style="padding:20px 22px;">
                        <div style="display:grid; gap:12px;">
                            <div>
                                <label style="display:block; font-size:.82rem; font-weight:700; color:#475569; margin-bottom:6px;">Subject</label>
                                <input type="text" id="modalSubject" style="width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:10px 12px; font-size:.95rem; outline:none;">
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                                <div>
                                    <label style="display:block; font-size:.82rem; font-weight:700; color:#475569; margin-bottom:6px;">CC</label>
                                    <input type="text" id="modalCc" placeholder="cc@example.com, cc2@example.com" style="width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:10px 12px; font-size:.9rem; outline:none;">
                                </div>
                                <div>
                                    <label style="display:block; font-size:.82rem; font-weight:700; color:#475569; margin-bottom:6px;">BCC</label>
                                    <input type="text" id="modalBcc" placeholder="bcc@example.com" style="width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:10px 12px; font-size:.9rem; outline:none;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div style="padding:16px 22px 22px; display:flex; gap:10px; justify-content:flex-end; border-top:1px solid #e2e8f0; background:#f8fafc;">
                        <button type="button" onclick="closeEmailMetaModal()" style="border:1px solid #cbd5e1; background:#fff; color:#475569; border-radius:10px; padding:10px 16px; font-weight:700; cursor:pointer;">Cancel</button>
                        <button type="button" onclick="submitEmailMeta()" style="border:none; background:var(--primary-purple); color:#fff; border-radius:10px; padding:10px 16px; font-weight:800; cursor:pointer;">Send Reply</button>
                    </div>
                </div>
            </div>
            <script>
                let lastMessageId = {{ $email->messages->last() ? $email->messages->last()->id : 0 }};
                let lastDisplayedDateStr = "{{ $email->messages->last() ? $email->messages->last()->created_at->format('M j, Y') : '' }}";
                const chatHistory = document.getElementById('chat-history');
                let pendingEmailForm = null;

                function scrollToBottom() {
                    chatHistory.scrollTop = chatHistory.scrollHeight;
                }

                function appendMessage(msg) {
                    const isMsgExist = document.getElementById(`msg-${msg.id}`);
                    if (isMsgExist) return;

                    const date = new Date(msg.created_at);
                    const msgDateStr = date.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

                    if (msgDateStr !== lastDisplayedDateStr) {
                        lastDisplayedDateStr = msgDateStr;
                        const todayDate = new Date();
                        const yesterdayDate = new Date(todayDate);
                        yesterdayDate.setDate(yesterdayDate.getDate() - 1);

                        const todayStr = todayDate.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                        const yesterdayStr = yesterdayDate.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

                        let displayDate = msgDateStr;
                        if (msgDateStr === todayStr) displayDate = 'Today';
                        else if (msgDateStr === yesterdayStr) displayDate = 'Yesterday';

                        const dateHeader = document.createElement('div');
                        dateHeader.style.cssText = "text-align:center; margin: 1.5rem 0 1rem; position: relative; width: 100%; align-self: center; clear: both;";
                        dateHeader.innerHTML = `<span style="background: #eef2ff; color: var(--primary-purple); padding: 4px 12px; border-radius: 99px; font-size: 0.75rem; font-weight: 600;">${displayDate}</span>`;
                        chatHistory.appendChild(dateHeader);
                    }

                    const msgDiv = document.createElement('div');
                    msgDiv.id = `msg-${msg.id}`;
                    const isSelf = msg.sender_type === 'admin';

                    msgDiv.style.display = 'flex';
                    msgDiv.style.flexDirection = 'column';
                    msgDiv.style.alignItems = isSelf ? 'flex-end' : 'flex-start';
                    msgDiv.style.marginBottom = '1.5rem';

                    const hasText = msg.message_body && msg.message_body.trim() !== '';
                    const hasAttachments = msg.attachments && msg.attachments.length > 0;
                    const isMsgOnlyImage = !hasText && hasAttachments && msg.attachments.every(p => /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(p));
                    const isTemplate = hasText && msg.message_body.includes('Custom Packaging Quote');
                    const bubbleStyle = isTemplate ? 'color: inherit;' : '';

                    const innerDiv = document.createElement('div');
                    innerDiv.className = isTemplate ? '' : (isSelf ? 'msg-bubble-admin' : 'msg-bubble-client');
                    innerDiv.style.cssText = `position: relative; max-width: ${isTemplate ? '100%' : '80%'}; padding: ${isMsgOnlyImage || isTemplate ? '0' : '0.85rem 1.1rem'}; ${bubbleStyle}`;
                    let attachmentsHtml = '';
                    if (hasAttachments) {
                        attachmentsHtml = `<div style="margin-top: ${hasText ? '5px' : '0'}; display: flex; flex-wrap: wrap; gap: 8px; justify-content: ${isSelf ? 'flex-end' : 'flex-start'}; max-width: 80%;">`;
                        msg.attachments.forEach(path => {
                            const isImg = /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(path);
                            const baseUrl = window.location.origin;
                            if (isImg) {
                                attachmentsHtml += `
                                                    <a href="${baseUrl}/${path}" target="_blank" style="display: block; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                                        <img src="${baseUrl}/${path}" style="max-width: 280px; max-height: 350px; display: block;">
                                                    </a>`;
                            } else {
                                const filename = path.split('/').pop();
                                attachmentsHtml += `
                                                    <a href="${baseUrl}/${path}" target="_blank" style="display: flex; align-items: center; gap: 10px; padding: 10px 14px; background: ${isSelf ? 'var(--primary-purple)' : '#ffffff'}; border: 1px solid ${isSelf ? 'var(--primary-purple)' : '#e2e8f0'}; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); text-decoration: none; color: ${isSelf ? '#ffffff' : '#1e293b'}; font-size: 0.85rem; font-weight: 500;">
                                                        <div style="width: 32px; height: 32px; background: ${isSelf ? 'rgba(255,255,255,0.2)' : '#eef2ff'}; color: ${isSelf ? '#fff' : 'var(--primary-purple)'}; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                            <i class="fas fa-file-alt"></i>
                                                        </div>
                                                        ${filename}
                                                    </a>`;
                            }
                        });
                        attachmentsHtml += '</div>';
                    }

                    if (hasText) {
                        innerDiv.innerHTML = `<div style="font-size: 0.95rem; line-height: 1.55;">${isSelf ? msg.message_body : msg.message_body.replace(/\n/g, '<br>')}</div>`;
                        msgDiv.appendChild(innerDiv);
                    }
                    if (hasAttachments) {
                        const attachContainer = document.createElement('div');
                        attachContainer.innerHTML = attachmentsHtml;
                        msgDiv.appendChild(attachContainer);
                    }

                    const metaDiv = document.createElement('div');
                    metaDiv.style.fontSize = '0.75rem';
                    metaDiv.style.color = '#94a3b8';
                    metaDiv.style.marginTop = '6px';
                    metaDiv.style.display = 'flex';
                    metaDiv.style.alignItems = 'center';
                    metaDiv.style.gap = '4px';

                    const timeStr = date.toLocaleString('en-US', { hour: 'numeric', minute: 'numeric', hour12: true });
                    const senderNameText = isSelf ? (msg.user ? msg.user.name : 'Admin') : "";
                    const cleanTimeStr = timeStr.replace(/^0/, '');
                    metaDiv.innerText = isSelf ? `${cleanTimeStr} • ${senderNameText.toLowerCase()}` : cleanTimeStr;
                    msgDiv.appendChild(metaDiv);
                    chatHistory.appendChild(msgDiv);

                    const placeholder = chatHistory.querySelector('div[style*="text-align: center"]');
                    if (placeholder) placeholder.remove();

                    lastMessageId = msg.id;
                    scrollToBottom();
                }

                let emailMessageFetchRunning = false;
                function fetchNewMessages() {
                    if (emailMessageFetchRunning || document.hidden) return;
                    emailMessageFetchRunning = true;
                    fetch(`{{ route('crm.messages.fetch', $email->id) }}?last_id=${lastMessageId}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        cache: 'no-store'
                    })
                        .then(response => response.json())
                        .then(messages => {
                            if (messages.length > 0) {
                                messages.forEach(msg => appendMessage(msg));
                            }
                        })
                        .catch(error => console.error('Error fetching messages:', error))
                        .finally(() => { emailMessageFetchRunning = false; });
                }

                function handleSendClick(e) {
                    if (e) e.preventDefault();
                    if (window.CKEDITOR && CKEDITOR.instances.message_body) {
                        CKEDITOR.instances.message_body.updateElement();
                    }
                    const form = document.getElementById('chat-form');
                    const textarea = document.getElementById('message_body');
                    const fileInput = document.getElementById('fileInput');

                    if (!textarea.value.trim() && fileInput.files.length === 0) return;

                    pendingEmailForm = form;
                    document.getElementById('modalSubject').value = "Re: " + {!! json_encode($email->subject ?: '') !!};
                    document.getElementById('modalCc').value = form.querySelector('[name="cc"]').value || '';
                    document.getElementById('modalBcc').value = form.querySelector('[name="bcc"]').value || '';
                    openEmailMetaModal();
                }

                function openEmailMetaModal() {
                    document.getElementById('emailMetaModal').style.display = 'flex';
                    document.getElementById('modalSubject').focus();
                }

                function closeEmailMetaModal() {
                    document.getElementById('emailMetaModal').style.display = 'none';
                    pendingEmailForm = null;
                }

                function submitEmailMeta() {
                    const subject = document.getElementById('modalSubject').value.trim();
                    if (!subject) {
                        alert('Email subject is required.');
                        return;
                    }
                    if (!pendingEmailForm) return;

                    pendingEmailForm.querySelector('[name="email_subject"]').value = subject;
                    pendingEmailForm.querySelector('[name="cc"]').value = document.getElementById('modalCc').value.trim();
                    pendingEmailForm.querySelector('[name="bcc"]').value = document.getElementById('modalBcc').value.trim();

                    const form = pendingEmailForm;
                    const btn = document.getElementById('send-btn');
                    const textarea = document.getElementById('message_body');

                    btn.disabled = true;
                    btn.style.opacity = '0.7';
                    document.getElementById('btn-icon').className = 'fas fa-spinner fa-spin';

                    const formData = new FormData(form);

                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(response => response.json())
                        .then(result => {
                            if (result.success) {
                                appendMessage(result.data);
                                form.reset();
                                
                                if (window.CKEDITOR && CKEDITOR.instances.message_body) {
                                    CKEDITOR.instances.message_body.setData('');
                                } else {
                                    textarea.style.height = '48px';
                                }
                                document.getElementById('attachment-tray').style.display = 'none';
                                document.getElementById('attachment-tray').innerHTML = '';
                                window.replyAttachmentFiles = [];
                                closeEmailMetaModal();
                            } else {
                                alert('Error: ' + result.message);
                            }
                        })
                        .catch(error => {
                            console.error('Submission error:', error);
                            alert('Failed to send message. Please try again.');
                        })
                        .finally(() => {
                            btn.disabled = false;
                            btn.style.opacity = '1';
                            document.getElementById('btn-icon').className = 'fas fa-paper-plane';
                        });
                }

                window.replyAttachmentFiles = [];

                function syncReplyFileInput() {
                    const input = document.getElementById('fileInput');
                    const transfer = new DataTransfer();
                    window.replyAttachmentFiles.forEach(file => transfer.items.add(file));
                    input.files = transfer.files;
                }

                function renderReplyAttachments() {
                    const tray = document.getElementById('attachment-tray');
                    (window.replyAttachmentObjectUrls || []).forEach(url => URL.revokeObjectURL(url));
                    window.replyAttachmentObjectUrls = [];
                    tray.innerHTML = '';
                    if (window.replyAttachmentFiles.length > 0) {
                        tray.style.setProperty('display', 'flex', 'important');
                        window.replyAttachmentFiles.forEach((file, index) => {
                            const item = document.createElement('div');
                            item.className = 'attachment-chip';
                            const fileUrl = URL.createObjectURL(file);
                            window.replyAttachmentObjectUrls.push(fileUrl);
                            const open = document.createElement('a');
                            open.className = 'attachment-chip-open';
                            open.href = fileUrl;
                            open.target = '_blank';
                            open.rel = 'noopener';
                            open.title = 'Open ' + file.name;
                            // The `download` attribute tells the browser the intended filename
                            // (Save As uses it) AND many browsers show it in the preview tab title.
                            open.download = file.name;
                            let preview;
                            if ((file.type && file.type.startsWith('image/')) || /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(file.name)) {
                                preview = document.createElement('img');
                                preview.className = 'attachment-chip-preview';
                                preview.alt = '';
                                preview.src = fileUrl;
                            } else {
                                preview = document.createElement('i');
                                preview.className = 'fas fa-file-alt';
                                preview.style.color = 'var(--primary-purple)';
                            }
                            const name = document.createElement('span');
                            name.className = 'attachment-chip-name';
                            name.title = file.name;
                            name.textContent = file.name;
                            const remove = document.createElement('button');
                            remove.type = 'button';
                            remove.className = 'attachment-chip-remove';
                            remove.title = 'Remove file';
                            remove.innerHTML = '<i class="fas fa-times"></i>';
                            remove.onclick = () => window.removeReplyAttachment(index);
                            open.append(preview, name);
                            item.append(open, remove);
                            tray.appendChild(item);
                        });
                    } else {
                        tray.style.setProperty('display', 'none', 'important');
                    }
                }

                window.addEventListener('beforeunload', function () {
                    (window.replyAttachmentObjectUrls || []).forEach(url => URL.revokeObjectURL(url));
                });

                window.addReplyAttachments = function (files, replace = false) {
                    const incoming = Array.from(files || []);
                    if (replace) window.replyAttachmentFiles = [];
                    incoming.forEach(file => {
                        const duplicate = window.replyAttachmentFiles.some(existing => existing.name === file.name && existing.size === file.size && existing.lastModified === file.lastModified);
                        if (!duplicate) window.replyAttachmentFiles.push(file);
                    });
                    syncReplyFileInput();
                    renderReplyAttachments();
                };

                window.removeReplyAttachment = function (index) {
                    window.replyAttachmentFiles.splice(index, 1);
                    syncReplyFileInput();
                    renderReplyAttachments();
                };

                window.handleFileSelect = function (input) {
                    window.addReplyAttachments(input.files, true);
                };

                window.setupReplyDropZone = function (target) {
                    if (!target || target.dataset.replyDropReady) return;
                    target.dataset.replyDropReady = '1';
                    ['dragenter', 'dragover'].forEach(type => target.addEventListener(type, event => {
                        event.preventDefault();
                        event.stopPropagation();
                        document.getElementById('replyDropZone')?.classList.add('reply-drag-active');
                    }, true));
                    target.addEventListener('dragleave', event => {
                        event.preventDefault();
                        event.stopPropagation();
                        document.getElementById('replyDropZone')?.classList.remove('reply-drag-active');
                    }, true);
                    target.addEventListener('drop', event => {
                        event.preventDefault();
                        event.stopImmediatePropagation();
                        document.getElementById('replyDropZone')?.classList.remove('reply-drag-active');
                        if (event.dataTransfer && event.dataTransfer.files.length) {
                            window.addReplyAttachments(event.dataTransfer.files);
                        }
                    }, true);
                };

                window.setupReplyDropOverlay = function () {
                    const overlay = document.getElementById('replyDropOverlay');
                    if (!overlay || overlay.dataset.ready) return;
                    overlay.dataset.ready = '1';

                    const hasFiles = event => Array.from(event.dataTransfer?.types || []).includes('Files');
                    document.addEventListener('dragenter', event => {
                        if (!hasFiles(event)) return;
                        event.preventDefault();
                        overlay.classList.add('active');
                    }, true);
                    document.addEventListener('dragover', event => {
                        if (!hasFiles(event)) return;
                        event.preventDefault();
                        if (event.dataTransfer) event.dataTransfer.dropEffect = 'copy';
                    }, true);
                    overlay.addEventListener('drop', event => {
                        event.preventDefault();
                        event.stopImmediatePropagation();
                        overlay.classList.remove('active');
                        window.addReplyAttachments(event.dataTransfer.files);
                    }, true);
                    overlay.addEventListener('dragleave', event => {
                        if (!overlay.contains(event.relatedTarget)) overlay.classList.remove('active');
                    });
                    window.addEventListener('drop', () => overlay.classList.remove('active'), true);
                    window.addEventListener('dragend', () => overlay.classList.remove('active'), true);
                };

                // Initial Scroll
                document.addEventListener('DOMContentLoaded', function () {
                    scrollToBottom();
                    window.setupReplyDropZone(document.getElementById('replyDropZone'));
                    window.setupReplyDropOverlay();
                    fetchNewMessages();
                    setInterval(fetchNewMessages, 3000);
                    document.addEventListener('visibilitychange', function () {
                        if (!document.hidden) fetchNewMessages();
                    });
                });
            </script>

            {{-- ===== MESSAGE TO AGENT (internal notes) — themed to the workspace primary color ===== --}}
            @php
                $__me = Auth::guard('crm')->user();
                $__initials = function ($name) {
                    $name = trim((string) $name);
                    if ($name === '') return '?';
                    $parts = preg_split('/\s+/', $name);
                    $a = mb_substr($parts[0], 0, 1);
                    $b = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
                    return mb_strtoupper($a . $b);
                };
            @endphp
            <style>
                .mta-card { margin-top: 2rem; background: #fff; border: 1px solid #e6ebf5; border-radius: 18px; overflow: hidden;
                    box-shadow: 0 1px 3px rgba(15,23,42,.04), 0 18px 40px -30px rgba(15,23,42,.25); }
                .mta-head { display: flex; align-items: center; gap: .8rem; padding: 1.1rem 1.35rem;
                    background: linear-gradient(135deg, var(--primary-soft) 0%, #fff 70%);
                    border-bottom: 1px solid #eef1f7; }
                .mta-icon { width: 40px; height: 40px; border-radius: 12px; display: grid; place-items: center;
                    background: var(--primary-purple); color: #fff; font-size: 1rem;
                    box-shadow: 0 6px 16px var(--primary-shadow); }
                .mta-head h3 { margin: 0; font-size: 1rem; font-weight: 800; color: #0f172a; letter-spacing: -.01em; }
                .mta-sub { margin-top: 2px; font-size: .72rem; color: #94a3b8; font-weight: 500; }
                .mta-thread { display: flex; flex-direction: column; gap: .85rem; padding: 1.3rem 1.35rem .5rem;
                    max-height: 460px; overflow-y: auto; background: #fbfcfe; }
                .mta-thread::-webkit-scrollbar { width: 6px; }
                .mta-thread::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 3px; }
                .mta-row { display: flex; gap: .6rem; align-items: flex-end; max-width: 78%; }
                .mta-row.mine { align-self: flex-end; flex-direction: row-reverse; }
                .mta-avatar { width: 34px; height: 34px; border-radius: 50%; display: grid; place-items: center;
                    font-size: .72rem; font-weight: 800; letter-spacing: .02em; flex: 0 0 34px;
                    background: #eef2f9; color: #475569; border: 1.5px solid #e6ebf5; }
                .mta-row.mine .mta-avatar { background: var(--primary-purple); color: #fff; border-color: var(--primary-purple); }
                .mta-bubble { position: relative; padding: .6rem .85rem .55rem; border-radius: 14px;
                    background: #fff; border: 1px solid #e6ebf5; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
                .mta-row.mine .mta-bubble { background: var(--primary-purple); color: #fff; border-color: var(--primary-purple);
                    box-shadow: 0 4px 14px var(--primary-shadow); }
                .mta-meta { font-size: .68rem; font-weight: 700; color: #475569; display: flex; gap: .35rem; align-items: baseline; }
                .mta-meta .role { color: var(--primary-purple); font-weight: 700; }
                .mta-row.mine .mta-meta, .mta-row.mine .mta-meta .role { color: rgba(255,255,255,.9); }
                .mta-body { font-size: .88rem; color: #1e293b; margin-top: .25rem; line-height: 1.45; white-space: pre-wrap; word-break: break-word; }
                .mta-row.mine .mta-body { color: #fff; }
                .mta-time { font-size: .64rem; color: #94a3b8; margin-top: .3rem; }
                .mta-row.mine .mta-time { color: rgba(255,255,255,.75); }
                .mta-empty { text-align: center; padding: 2.5rem 1rem; color: #94a3b8; font-size: .85rem; }
                .mta-empty i { display: block; font-size: 1.8rem; margin-bottom: .6rem; color: #cbd5e1; }
                .mta-composer { display: flex; gap: .6rem; align-items: flex-end; padding: 1rem 1.35rem 1.15rem;
                    background: #fff; border-top: 1px solid #eef1f7; }
                .mta-composer textarea { flex: 1; border: 1.5px solid #e6ebf5; border-radius: 12px; padding: .7rem .9rem;
                    font-size: .87rem; font-family: inherit; outline: none; resize: none; min-height: 44px; max-height: 140px;
                    background: #fafbfd; transition: all .15s; }
                .mta-composer textarea:focus { border-color: var(--primary-purple); background: #fff;
                    box-shadow: 0 0 0 3px var(--primary-shadow); }
                .mta-send { padding: .7rem 1.1rem; background: var(--primary-purple); color: #fff; border: none;
                    border-radius: 12px; font-weight: 700; font-size: .85rem; cursor: pointer;
                    display: flex; align-items: center; gap: 6px; white-space: nowrap; transition: all .15s;
                    box-shadow: 0 6px 16px var(--primary-shadow); }
                .mta-send:hover { background: var(--primary-hover); transform: translateY(-1px); }
                .mta-send:active { transform: translateY(0); }
            </style>

            <div class="mta-card" id="inquiryNotes">
                <div class="mta-head">
                    <div class="mta-icon"><i class="fas fa-comments"></i></div>
                    <div>
                        @php
                            $__assignedAgent = $email->assigned_to ? \App\CrmUser::find($email->assigned_to) : null;
                            $__agentName = $__assignedAgent ? $__assignedAgent->name : null;
                            $__agentRole = $__assignedAgent && method_exists($__assignedAgent, 'getRoleLabel') ? $__assignedAgent->getRoleLabel() : null;
                        @endphp
                        <h3>
                            Message to Agent
                            @if($__agentName)
                                <span style="color:var(--primary-purple); font-weight:800;">— {{ $__agentName }}</span>
                                @if($__agentRole)<span style="font-size:.7rem; color:var(--primary-purple); font-weight:600; opacity:.75; margin-left:.35rem;">({{ $__agentRole }})</span>@endif
                            @else
                                <span style="color:#94a3b8; font-weight:500; font-size:.85rem;">— not assigned yet</span>
                            @endif
                        </h3>
                        <div class="mta-sub"><i class="fas fa-lock" style="font-size:.62rem; opacity:.7;"></i> Private team notes for this inquiry · syncs with agent's Team Chat</div>
                    </div>
                </div>

                <div class="mta-thread" id="mtaThread">
                    @forelse(($inquiryNotes ?? collect()) as $__note)
                        @php $__mine = (int) $__note->sender_id === (int) $__me->id; @endphp
                        <div class="mta-row {{ $__mine ? 'mine' : '' }}">
                            <div class="mta-avatar">{{ $__initials($__note->sender_name) }}</div>
                            <div class="mta-bubble">
                                <div class="mta-meta">
                                    <span>{{ $__note->sender_name ?: 'Unknown' }}</span>
                                    @if($__note->sender_role)<span class="role">· {{ $__note->sender_role }}</span>@endif
                                </div>
                                <div class="mta-body">{{ $__note->body }}</div>
                                <div class="mta-time">{{ \Carbon\Carbon::parse($__note->created_at)->format('d M, h:i A') }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="mta-empty">
                            <i class="fas fa-comment-slash"></i>
                            No messages yet. Start a private thread with the assigned agent below.
                        </div>
                    @endforelse
                </div>

                <form action="{{ route('crm.emails.store_note', $email->id) }}" method="POST" class="mta-composer">
                    {{ csrf_field() }}
                    <textarea name="body" required maxlength="5000" rows="1"
                        placeholder="Write a message to the agent — press Enter to send"
                        onkeydown="if(event.key==='Enter' && !event.shiftKey){event.preventDefault(); this.form.requestSubmit();}"
                        oninput="this.style.height='auto'; this.style.height=Math.min(this.scrollHeight,140)+'px';"></textarea>
                    <button type="submit" class="mta-send">
                        <i class="fas fa-paper-plane"></i> Send
                    </button>
                </form>
            </div>
            <script>(function(){var t=document.getElementById('mtaThread'); if(t) t.scrollTop=t.scrollHeight;})();</script>
        </div>
        @endif

    </div>

    {{-- ===== ASSIGN MODAL ===== --}}
    @if(Auth::guard('crm')->user()->canAssign())
        <div id="assignModal"
            style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
            <div
                style="background:white; border-radius:16px; padding:2rem; width:420px; max-width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.2); position:relative;">
                <button onclick="closeAssignModal()"
                    style="position:absolute; top:1rem; right:1rem; background:none; border:none; font-size:1.2rem; color:#94a3b8; cursor:pointer;">
                    <i class="fas fa-times"></i>
                </button>
                <div style="margin-bottom:1.5rem;">
                    <h3 style="margin:0 0 0.25rem; font-size:1.1rem; font-weight:700; color:#1e293b;"><i
                            class="fas fa-user-plus" style="color:var(--primary-purple);"></i> Assign Email</h3>
                    <p style="margin:0; font-size:0.85rem; color:#64748b;">Select a team member to assign this inquiry to.</p>
                </div>

                <div style="margin-bottom:1.25rem;">
                    <label
                        style="display:block; font-size:0.8rem; font-weight:700; color:#475569; margin-bottom:0.5rem; text-transform:uppercase; letter-spacing:0.04em;">Assign
                        To</label>
                    <div class="custom-dropdown" id="agentDropdown">
                        <div class="dropdown-trigger" onclick="toggleAgentDropdown(event)">
                            <span id="selectedAgentText" style="color: #94a3b8; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-users" style="font-size: 0.9rem; opacity: 0.7;"></i> Choose a team member...
                            </span>
                            <i class="fas fa-chevron-down" style="font-size: 0.8rem; color: #94a3b8;"></i>
                        </div>
                        <div class="dropdown-options" id="agentOptionsList">
                            <div class="dropdown-search-container"
                                style="padding: 10px; border-bottom: 1px solid #f1f5f9; position: sticky; top: 0; background: white; z-index: 10;">
                                <div style="position: relative;">
                                    <i class="fas fa-search"
                                        style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 0.8rem;"></i>
                                    <input type="text" id="agentSearchInput" placeholder="Search agent name..."
                                        oninput="filterAgents(this.value)" onclick="event.stopPropagation()"
                                        style="width: 100%; padding: 0.6rem 0.6rem 0.6rem 2.2rem; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.85rem; outline: none; transition: border-color 0.2s;"
                                        onfocus="this.style.borderColor='var(--primary-purple)'" onblur="this.style.borderColor='#e2e8f0'">
                                </div>
                            </div>
                            <div id="agentsListContent">
                                <div style="padding: 1rem; text-align: center; color: #94a3b8; font-size: 0.85rem;"><i
                                        class="fas fa-spinner fa-spin"></i> Loading team...</div>
                            </div>
                        </div>
                        <input type="hidden" id="assignToSelect" value="">
                    </div>
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label
                        style="display:block; font-size:0.8rem; font-weight:700; color:#475569; margin-bottom:0.5rem; text-transform:uppercase; letter-spacing:0.04em;">Note
                        (Optional)</label>
                    <textarea id="assignNote" rows="2" placeholder="e.g. Follow up on packaging specs..."
                        style="width:100%; padding:0.85rem 1rem; border:1px solid #e2e8f0; border-radius:12px; font-size:0.9rem; color:#1e293b; outline:none; resize:none; box-sizing:border-box; background: #f8fafc; transition: border-color 0.2s;"
                        onfocus="this.style.borderColor='var(--primary-purple)'; this.style.background='white'"
                        onblur="this.style.borderColor='#e2e8f0'; this.style.background='#f8fafc'"></textarea>
                </div>

                <div id="assignError"
                    style="display:none; background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:0.75rem; color:#ef4444; font-size:0.85rem; margin-bottom:1rem;">
                </div>

                <div
                    style="display:flex; gap:0.75rem; justify-content:flex-end; align-items: center; margin-top: 2rem; border-top: 1px solid #f1f5f9; padding-top: 1.5rem;">
                    <button onclick="closeAssignModal()"
                        style="height: 42px; padding:0 1.25rem; background:white; border:1px solid #e2e8f0; border-radius:10px; color:#64748b; font-weight:700; cursor:pointer; font-size:0.85rem; transition: all 0.2s;"
                        onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">Cancel</button>
                    @if(Auth::guard('crm')->user()->isAdmin())
                        <a href="{{ route('crm.users.create') }}"
                            style="height: 42px; padding:0 1rem; background:var(--primary-soft); border:1px solid var(--primary-soft); border-radius:10px; color:var(--primary-purple); font-weight:700; text-decoration:none; font-size:0.85rem; display:flex; align-items:center; gap:6px; transition: all 0.2s;"
                            onmouseover="this.style.background='var(--primary-soft)'" onmouseout="this.style.background='var(--primary-soft)'">
                            <i class="fas fa-plus-circle"></i> Add Agent
                        </a>
                    @endif
                    <button onclick="submitAssign()" id="assignSubmitBtn"
                        style="height: 42px; padding:0 1.5rem; background:var(--primary-purple); color:white; border:none; border-radius:10px; font-weight:700; cursor:pointer; font-size:0.85rem; display:flex; align-items:center; gap:8px; transition: all 0.2s; box-shadow: 0 4px 10px rgba(var(--primary-rgb), 0.15);"
                        onmouseover="this.style.background='var(--primary-purple)'; this.style.transform='translateY(-1px)'"
                        onmouseout="this.style.background='var(--primary-purple)'; this.style.transform='translateY(0)'">
                        <i class="fas fa-paper-plane"></i> Assign
                    </button>
                </div>
            </div>
        </div>

        <script>
            const emailId = {{ $email->id }};
            const assignableUsersUrl = '{{ route("crm.emails.assignable_users") }}';
            const assignUrl = '{{ route("crm.emails.assign", $email->id) }}';
            const logsUrl = '{{ route("crm.emails.assignment_logs", $email->id) }}';
            const csrfTokenAssign = '{{ csrf_token() }}';
            let logsLoaded = false;

            let allAssignableUsers = @json($assignableUsers ?? []);

            function openAssignModal() {
                document.getElementById('assignModal').style.display = 'flex';
                renderAgents(allAssignableUsers);
                loadAssignableUsers();
            }

            function closeAssignModal() {
                document.getElementById('assignModal').style.display = 'none';
                document.getElementById('assignError').style.display = 'none';
                const trigger = document.querySelector('.dropdown-trigger');
                if (trigger) trigger.classList.remove('active');
                const options = document.getElementById('agentOptionsList');
                if (options) options.style.display = 'none';
                const searchInput = document.getElementById('agentSearchInput');
                if (searchInput) searchInput.value = '';
            }

            function toggleAgentDropdown(e) {
                e.stopPropagation();
                const options = document.getElementById('agentOptionsList');
                const trigger = document.querySelector('.dropdown-trigger');
                const searchInput = document.getElementById('agentSearchInput');
                const isActive = options.style.display === 'block';

                // Close others
                options.style.display = isActive ? 'none' : 'block';
                trigger.classList.toggle('active', !isActive);

                if (!isActive && searchInput) {
                    setTimeout(() => searchInput.focus(), 50);
                }
            }

            // Close dropdown on outside click
            window.addEventListener('click', () => {
                const options = document.getElementById('agentOptionsList');
                const trigger = document.querySelector('.dropdown-trigger');
                if (options) options.style.display = 'none';
                if (trigger) trigger.classList.remove('active');
            });

            function selectAgent(id, name, role) {
                const input = document.getElementById('assignToSelect');
                const text = document.getElementById('selectedAgentText');
                const roleLabels = { admin: 'Admin', sales_manager: 'Manager', sales: 'Agent' };
                const badgeClasses = { admin: 'badge-admin-mini', sales_manager: 'badge-manager-mini', sales: 'badge-sales-mini' };

                input.value = id;
                text.style.color = '#1e293b'; // Change to dark color when selected
                text.innerHTML = `<span class="role-badge-mini ${badgeClasses[role] || ''}">${roleLabels[role] || role}</span> <span class="agent-name-mini">${name}</span>`;

                document.getElementById('agentOptionsList').style.display = 'none';
                document.querySelector('.dropdown-trigger').classList.remove('active');
            }

            function loadAssignableUsers() {
                fetch(assignableUsersUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                    .then(r => {
                        if (!r.ok) throw new Error('Unable to load team members.');
                        return r.json();
                    })
                    .then(users => {
                        allAssignableUsers = users;
                        renderAgents(users);
                    })
                    .catch(() => {
                        if (allAssignableUsers.length > 0) {
                            renderAgents(allAssignableUsers);
                            return;
                        }
                        document.getElementById('agentsListContent').innerHTML = '<div style="padding:1rem;text-align:center;color:#ef4444;font-size:0.85rem;">Unable to load team. Please refresh and try again.</div>';
                    });
            }

            function filterAgents(query) {
                const filtered = allAssignableUsers.filter(u =>
                    u.name.toLowerCase().includes(query.toLowerCase())
                );
                renderAgents(filtered);
            }

            function renderAgents(users) {
                const list = document.getElementById('agentsListContent');
                const roleLabels = { admin: 'Admin', sales_manager: 'Manager', sales: 'Agent' };
                const badgeClasses = { admin: 'badge-admin-mini', sales_manager: 'badge-manager-mini', sales: 'badge-sales-mini' };

                if (users.length === 0) {
                    list.innerHTML = '<div style="padding: 1rem; text-align: center; color: #94a3b8;">No matching agents</div>';
                    return;
                }

                list.innerHTML = users.map(u => `
                                                <div class="dropdown-option" onclick="selectAgent('${u.id}', '${u.name}', '${u.role}')">
                                                    <span class="role-badge-mini ${badgeClasses[u.role] || ''}">${roleLabels[u.role] || u.role}</span>
                                                    <span class="agent-name-mini">${u.name}</span>
                                                </div>
                                            `).join('');
            }

            function submitAssign() {
                const assignTo = document.getElementById('assignToSelect').value;
                const note = document.getElementById('assignNote').value;
                const btn = document.getElementById('assignSubmitBtn');
                const errDiv = document.getElementById('assignError');

                if (!assignTo) {
                    errDiv.style.display = 'block';
                    errDiv.innerText = 'Please select a team member.';
                    return;
                }

                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Assigning...';
                errDiv.style.display = 'none';

                const fd = new FormData();
                fd.append('assigned_to', assignTo);
                fd.append('note', note);
                fd.append('_token', csrfTokenAssign);

                fetch(assignUrl, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            closeAssignModal();
                            // Reload page to reflect new assignment
                            window.location.reload();
                        } else {
                            errDiv.style.display = 'block';
                            errDiv.innerText = res.message || 'Assignment failed.';
                            btn.disabled = false;
                            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Assign';
                        }
                    })
                    .catch(() => {
                        errDiv.style.display = 'block';
                        errDiv.innerText = 'Network error. Please try again.';
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Assign';
                    });
            }

            function toggleAssignLogs() {
                const container = document.getElementById('assignLogsContainer');
                if (container.style.display === 'none') {
                    container.style.display = 'block';
                    if (!logsLoaded) loadAssignLogs();
                } else {
                    container.style.display = 'none';
                }
            }

            function loadAssignLogs() {
                logsLoaded = true;
                fetch(logsUrl)
                    .then(r => r.json())
                    .then(logs => {
                        const container = document.getElementById('assignLogsContent');
                        if (!logs.length) {
                            container.innerHTML = '<div style="text-align:center; color:#94a3b8; padding:0.5rem;">No assignment history yet.</div>';
                            return;
                        }
                        const roleColors = { admin: 'var(--primary-purple)', sales_manager: 'var(--primary-purple)', sales: '#10b981' };
                        container.innerHTML = logs.map(l => {
                            const date = new Date(l.created_at).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
                            const byColor = roleColors[l.assigned_by_role] || '#64748b';
                            const toColor = roleColors[l.assigned_to_role] || '#64748b';
                            return `
                                                        <div style="padding:0.75rem 0; border-bottom:1px solid #f1f5f9;">
                                                            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:4px;">
                                                                <span style="color:${byColor}; font-weight:700;">${l.assigned_by_name}</span>
                                                                <i class="fas fa-arrow-right" style="color:#94a3b8; font-size:0.65rem;"></i>
                                                                <span style="color:${toColor}; font-weight:700;">${l.assigned_to_name}</span>
                                                            </div>
                                                            ${l.note ? `<div style="color:#475569; font-style:italic; margin-bottom:4px;">"${l.note}"</div>` : ''}
                                                            <div style="color:#94a3b8; font-size:0.72rem;"><i class="far fa-clock"></i> ${date}</div>
                                                        </div>`;
                        }).join('');
                    });
            }

            // Close modal on outside click
            document.getElementById('assignModal').addEventListener('click', function (e) {
                if (e.target === this) closeAssignModal();
            });
        </script>
    @endif
@endsection

@section('scripts')
    <script src="{{URL::asset('ckeditor/ckeditor.js')}}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (document.getElementById('message_body')) {
                const replyEditor = CKEDITOR.replace('message_body', {
                    filebrowserUploadUrl: "{{URL::asset('ckeditor/ck_upload.php')}}",
                    filebrowserUploadMethod: 'form',
                    height: 250,
                    allowedContent: true,
                    removePlugins: 'uploadimage,uploadfile'
                });
                replyEditor.on('drop', function (event) {
                    const transfer = event.data && event.data.dataTransfer;
                    const files = [];
                    if (transfer && typeof transfer.getFilesCount === 'function') {
                        for (let index = 0; index < transfer.getFilesCount(); index++) {
                            const file = transfer.getFile(index);
                            if (file) files.push(file);
                        }
                    }
                    if (files.length && window.addReplyAttachments) {
                        event.cancel();
                        window.addReplyAttachments(files);
                    }
                }, null, null, 1);
                replyEditor.on('contentDom', function () {
                    if (window.setupReplyDropZone) {
                        window.setupReplyDropZone(replyEditor.editable().$);
                    }
                });
                @if(session('estimate_draft'))
                    const estimateDraft = @json(session('estimate_draft'));
                    replyEditor.on('instanceReady', function () {
                        replyEditor.setData(estimateDraft.body || '');
                        const pdfBlobPromise = estimateDraft.attachment_base64
                            ? Promise.resolve((function () {
                                const binary = atob(estimateDraft.attachment_base64);
                                const bytes = new Uint8Array(binary.length);
                                for (let index = 0; index < binary.length; index++) {
                                    bytes[index] = binary.charCodeAt(index);
                                }
                                return new Blob([bytes], { type: 'application/pdf' });
                            })())
                            : fetch(estimateDraft.attachment_url, { credentials: 'same-origin' })
                                .then(response => {
                                    if (!response.ok) throw new Error('Unable to load estimate PDF (' + response.status + ')');
                                    return response.blob();
                                });
                        pdfBlobPromise
                            .then(blob => {
                                if (!blob || blob.size === 0) throw new Error('Generated estimate PDF is empty');
                                const file = new File(
                                    [blob],
                                    estimateDraft.attachment_name || 'estimate.pdf',
                                    { type: 'application/pdf', lastModified: Date.now() }
                                );
                                window.addReplyAttachments([file]);
                                document.getElementById('chat-form').scrollIntoView({ behavior: 'smooth', block: 'center' });
                                replyEditor.focus();
                            })
                            .catch(error => {
                                console.error('Estimate draft attachment error:', error);
                                alert('The estimate text was prepared, but the PDF could not be attached. Please reload and try again.');
                            });
                    });
                @endif
            }
        });
    </script>
    
    @if(request('action') == 'send_proof')
        @php
            $latestProof = \App\ProofRevision::where('crm_email_id', $email->id)->orderBy('version_number', 'desc')->first();
        @endphp
        @if($latestProof)
            <script>
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    let proofHtml = `
                        <div style="padding: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 15px;">
                            <h3 style="margin-top: 0; color: #1e293b;">Final Proof for Approval</h3>
                            <p style="color: #475569;">Please review the attached proof for your order. Let us know if you approve it or if you need any changes.</p>
                            <a href="{{ asset($latestProof->file_path) }}" target="_blank" style="display: inline-block; padding: 10px 15px; background: var(--primary-purple); color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;">View Design PDF</a>
                        </div>
                        <p><br></p>
                    `;
                    if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.message_body) {
                        CKEDITOR.instances.message_body.insertHtml(proofHtml);
                        document.getElementById('message_body').scrollIntoView({behavior: 'smooth', block: 'center'});
                    }
                }, 1000); // Wait a bit for CKEditor to fully initialize
            });
            </script>
        @endif
    @endif
@endsection
