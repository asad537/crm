@extends('crm.layout')

@section('title', 'Inbox')

@section('header_actions')
    <button type="button" onclick="openCreateModal()" class="btn btn-primary"
        style="padding: 0.5rem 1rem; font-size: 0.85rem; background:#10b981; border-color:#10b981; margin-right: 8px;">
        <i class="fas fa-plus" style="margin-right: 8px;"></i> Create Inquiry
    </button>
    <button type="button" onclick="refreshInbox()" class="btn btn-primary"
        style="padding: 0.5rem 1rem; font-size: 0.85rem;">
        <i class="fas fa-sync-alt" style="margin-right: 8px;"></i> Refresh
    </button>
@endsection

@section('content')
    <style>
        /* Consistent Theme Styles - Reused from Leads/Dashboard */
        .content-card {
            background: var(--card-bg);
            border-radius: var(--border-radius-base);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: none;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .filter-card {
            background: var(--card-bg);
            border-radius: var(--border-radius-base);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            border: none;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 0.82rem;
        }

        .table th {
            padding: 0.5rem 0.75rem;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.04em;
            background: #f8fafc;
            text-align: left;
            font-weight: 600;
        }

        .table td {
            padding: 0.4rem 0.6rem;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            vertical-align: middle;
            font-size: 0.8rem;
        }

        .table tr:hover td {
            background: #f8fafc;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary-purple);
            color: white;
            border-color: var(--primary-purple);
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .btn-danger {
            background: #fee2e2;
            color: #ef4444;
            border: 1px solid #fecaca;
        }

        .btn-danger:hover {
            background: #fecaca;
        }

        .btn-light {
            background: white;
            border: 1px solid #cbd5e1;
            color: #475569;
        }

        .btn-light:hover {
            background: #f1f5f9;
        }

        .btn-active {
            background: #e0e7ff;
            color: var(--primary-purple);
            border: 1px solid #c7d2fe;
        }

        input,
        select {
            font-size: 0.9rem !important;
            padding: 0.6rem !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 10px !important;
            outline: none;
            background: white;
        }

        input:focus,
        select:focus {
            border-color: var(--primary-purple) !important;
            ring: 2px solid var(--primary-purple);
        }

        .inquiry-modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 10000;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
            background: rgba(15, 23, 42, 0.62);
            backdrop-filter: blur(7px);
            -webkit-backdrop-filter: blur(7px);
        }

        .inquiry-modal {
            width: 100%;
            max-width: 720px;
            max-height: calc(100vh - 2.5rem);
            overflow-y: auto;
            background: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 18px;
            box-shadow: 0 30px 90px rgba(15, 23, 42, 0.34), 0 2px 8px rgba(15, 23, 42, 0.08);
            animation: inquiryModalIn 0.22s ease-out;
        }

        .inquiry-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1.15rem 1.35rem;
            border-bottom: 1px solid #e8eef5;
            background: linear-gradient(135deg, #f1fdf8 0%, #ffffff 72%);
        }

        .inquiry-modal-title-wrap {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .inquiry-modal-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            flex: 0 0 40px;
            border-radius: 12px;
            color: #ffffff;
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 8px 18px rgba(16, 185, 129, 0.24);
        }

        .inquiry-modal-title {
            margin: 0;
            color: #172033;
            font-size: 1.12rem;
            font-weight: 800;
            letter-spacing: -0.015em;
        }

        .inquiry-modal-subtitle {
            margin: 0.2rem 0 0;
            color: #7a899d;
            font-size: 0.75rem;
        }

        .inquiry-modal-close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            padding: 0 !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 11px !important;
            color: #64748b;
            background: #ffffff;
            cursor: pointer;
            transition: all 0.18s ease;
        }

        .inquiry-modal-close:hover {
            color: #dc2626;
            border-color: #fecaca !important;
            background: #fff1f2;
            transform: rotate(4deg);
        }

        .inquiry-modal-body {
            padding: 1.2rem 1.35rem 0;
        }

        .inquiry-section-label {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            margin: 0 0 0.72rem;
            color: #94a3b8;
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.09em;
            text-transform: uppercase;
        }

        .inquiry-section-label::after {
            content: '';
            height: 1px;
            flex: 1;
            background: #edf1f5;
        }

        .inquiry-form-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 0.78rem;
            margin-bottom: 1rem;
        }

        .inquiry-field { grid-column: span 3; }
        .inquiry-field-wide { grid-column: span 4; }
        .inquiry-field-small { grid-column: span 2; }
        .inquiry-field-half { grid-column: span 3; }
        .inquiry-field-narrow { grid-column: span 1; }
        .inquiry-field-full { grid-column: 1 / -1; }

        .inquiry-field label {
            display: block;
            margin-bottom: 0.42rem;
            color: #3d4b60;
            font-size: 0.77rem;
            font-weight: 700;
        }

        .inquiry-required { color: #ef4444; }

        .inquiry-field .inq-input {
            width: 100%;
            min-height: 41px;
            box-sizing: border-box;
            padding: 0.66rem 0.8rem !important;
            border: 1.5px solid #dce4ed !important;
            border-radius: 10px !important;
            outline: none;
            color: #1e293b;
            background: #ffffff;
            font-family: inherit;
            font-size: 0.84rem !important;
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        .inquiry-field .inq-input::placeholder { color: #a5b0bf; }

        .inquiry-field .inq-input:focus {
            border-color: #10b981 !important;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.12);
        }

        .inquiry-field textarea.inq-input {
            min-height: 88px;
            line-height: 1.55;
            resize: vertical;
        }

        .inquiry-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin: 1.15rem -1.35rem 0;
            padding: 1rem 1.35rem;
            border-top: 1px solid #edf1f5;
            border-radius: 0 0 18px 18px;
            background: #f8fafc;
        }

        .inquiry-action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 40px;
            padding: 0.7rem 1.15rem !important;
            border-radius: 10px !important;
            font-size: 0.82rem;
            font-weight: 750;
            cursor: pointer;
            transition: all 0.18s ease;
        }

        .inquiry-action-cancel {
            min-width: 105px;
            border: 1px solid #dce4ed !important;
            color: #526174;
            background: #ffffff;
        }

        .inquiry-action-cancel:hover { background: #f8fafc; }

        .inquiry-action-submit {
            min-width: 215px;
            border: 1px solid #059669 !important;
            color: #ffffff;
            background: linear-gradient(135deg, #10b981, #059669);
            box-shadow: 0 8px 18px rgba(5, 150, 105, 0.2);
        }

        .inquiry-action-submit:hover {
            box-shadow: 0 10px 24px rgba(5, 150, 105, 0.28);
            transform: translateY(-1px);
        }

        @keyframes inquiryModalIn {
            from { opacity: 0; transform: translateY(12px) scale(0.985); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @media (max-width: 680px) {
            .inquiry-modal-backdrop { padding: 0; align-items: flex-end; }
            .inquiry-modal { max-height: 94vh; border-radius: 20px 20px 0 0; }
            .inquiry-modal-header, .inquiry-modal-body { padding: 1.15rem; }
            .inquiry-field, .inquiry-field-wide, .inquiry-field-small,
            .inquiry-field-half, .inquiry-field-narrow { grid-column: 1 / -1; }
            .inquiry-form-grid { gap: 0.85rem; }
            .inquiry-modal-actions { flex-direction: column-reverse; margin: 1.1rem -1.15rem 0; padding: 1rem 1.15rem; }
            .inquiry-action-btn { width: 100%; }
        }
    </style>

    <!-- Filters -->
    <div class="filter-card" style="padding: 0.85rem 1.25rem;">
        <form action="{{ route('crm.emails.index') }}" method="GET" id="inboxFilterForm" onsubmit="event.preventDefault(); applyFilters();">
            <div style="display:flex; gap:0.6rem; align-items:center; flex-wrap:wrap;">

                {{-- Global Search --}}
                <div style="position:relative; flex:1; min-width:180px;">
                    <i class="fas fa-search"
                        style="position:absolute; left:0.75rem; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:0.8rem;"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search client, email, subject, source…" class="ajax-trigger" id="searchInput"
                        style="width:100%; padding:0.5rem 0.75rem 0.5rem 2.2rem !important; border:1px solid #e2e8f0; border-radius:8px; font-size:0.85rem; outline:none; box-sizing:border-box;"
                        onfocus="this.style.borderColor='var(--primary-purple)'" onblur="this.style.borderColor='#e2e8f0'">
                </div>

                {{-- Date Range Picker (same as Orders page) --}}
                <div style="position:relative;">
                    <i class="fas fa-calendar-alt"
                        style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:0.8rem;pointer-events:none;"></i>
                    <input type="text" id="inboxDateRangePicker" name="date_range_display"
                        placeholder="Select date range..."
                        value="{{ request('start_date') && request('end_date') ? request('start_date') . ' — ' . request('end_date') : '' }}"
                        readonly
                        style="cursor:pointer; padding:0.5rem 0.85rem 0.5rem 2.2rem !important; border:1.5px solid {{ request('start_date') ? 'var(--primary-purple)' : '#e2e8f0' }}; border-radius:8px; font-size:0.82rem; color:#374151; background:{{ request('start_date') ? 'var(--primary-soft)' : 'white' }}; min-width:190px;">
                    <input type="hidden" name="start_date" id="inboxStartDate" value="{{ request('start_date') }}">
                    <input type="hidden" name="end_date" id="inboxEndDate" value="{{ request('end_date') }}">
                </div>

                {{-- Status --}}
                <select name="status" class="ajax-trigger"
                    style="padding:0.5rem 0.75rem !important;border:1px solid #e2e8f0;border-radius:8px;font-size:0.82rem;color:#475569;outline:none;background:white;">
                    <option value="">All Statuses</option>
                    <option value="New" {{ request('status') == 'New' ? 'selected' : '' }}>Unread</option>
                    <option value="Viewed" {{ request('status') == 'Viewed' ? 'selected' : '' }}>Read</option>
                    <option value="Responded" {{ request('status') == 'Responded' ? 'selected' : '' }}>Replied</option>
                    <option value="Order Done" {{ request('status') == 'Order Done' ? 'selected' : '' }}>Completed</option>
                    <option value="Closed" {{ request('status') == 'Closed' ? 'selected' : '' }}>Closed</option>
                </select>

                {{-- Source --}}
                <select name="source" class="ajax-trigger"
                    style="padding:0.5rem 0.75rem !important;border:1px solid #e2e8f0;border-radius:8px;font-size:0.82rem;color:#475569;outline:none;background:white;">
                    <option value="">All Sources</option>
                    <option value="form" {{ request('source') == 'form' ? 'selected' : '' }}>Website Form</option>
                    <option value="call" {{ request('source') == 'call' ? 'selected' : '' }}>Call</option>
                    <option value="live_chat" {{ request('source') == 'live_chat' ? 'selected' : '' }}>Live Chat</option>
                    <option value="email" {{ request('source') == 'email' ? 'selected' : '' }}>Email</option>
                    <option value="whatsapp" {{ request('source') == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                    <option value="google_ads" {{ request('source') == 'google_ads' ? 'selected' : '' }}>Google Ads</option>
                    <option value="social" {{ request('source') == 'social' ? 'selected' : '' }}>Social</option>
                    <option value="walk_in" {{ request('source') == 'walk_in' ? 'selected' : '' }}>Walk-In</option>
                </select>

                {{-- Per Page --}}
                <select name="per_page" class="ajax-trigger"
                    style="padding:0.5rem 0.75rem !important;border:1px solid #e2e8f0;border-radius:8px;font-size:0.82rem;color:#475569;outline:none;background:white;">
                    <option value="20" {{ request('per_page') == '20' || !request('per_page') ? 'selected' : '' }}>20</option>
                    <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
                    <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
                    <option value="all" {{ request('per_page') == 'all' ? 'selected' : '' }}>All</option>
                </select>

                {{-- Clear --}}
                <a href="{{ route('crm.emails.index') }}"
                    style="padding:0.5rem 0.85rem;font-size:0.8rem;border-radius:8px;border:1px solid #e2e8f0;background:white;color:#64748b;text-decoration:none;font-weight:600;">
                    <i class="fas fa-times"></i>
                </a>
            </div>

            {{-- Active filter chips container for AJAX updates --}}
            <div id="activeFiltersContainer">
                @if(request()->hasAny(['start_date', 'end_date', 'search', 'status', 'source']))
                    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.6rem;">
                        @if(request('start_date') && request('end_date'))
                            <span
                                style="display:inline-flex;align-items:center;gap:5px;background:var(--primary-soft);color:var(--primary-purple);border:1px solid var(--primary-soft);padding:3px 10px;border-radius:99px;font-size:0.72rem;font-weight:700;">
                                <i class="fas fa-calendar-alt"></i> {{ request('start_date') }} → {{ request('end_date') }}
                            </span>
                        @endif
                        @if(request('search'))
                            <span
                                style="display:inline-flex;align-items:center;gap:5px;background:var(--primary-soft);color:var(--primary-purple);border:1px solid var(--primary-soft);padding:3px 10px;border-radius:99px;font-size:0.72rem;font-weight:700;">
                                <i class="fas fa-search"></i> "{{ request('search') }}"
                            </span>
                        @endif
                        @if(request('status'))
                            <span
                                style="display:inline-flex;align-items:center;gap:5px;background:var(--primary-soft);color:var(--primary-purple);border:1px solid var(--primary-soft);padding:3px 10px;border-radius:99px;font-size:0.72rem;font-weight:700;">
                                <i class="fas fa-tag"></i> {{ request('status') }}
                            </span>
                        @endif
                        @if(request('source'))
                            <span
                                style="display:inline-flex;align-items:center;gap:5px;background:var(--primary-soft);color:var(--primary-purple);border:1px solid var(--primary-soft);padding:3px 10px;border-radius:99px;font-size:0.72rem;font-weight:700;">
                                <i class="fas fa-bullseye"></i> {{ ucfirst(str_replace('_', ' ', request('source'))) }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="content-card">
        <div class="table-responsive" style="overflow-x: auto; scrollbar-width: thin;">
            <table class="table" style="min-width: 850px;">
                <thead>
                    <tr>
                        <th style="width:40px;">
                            <input type="checkbox" id="selectAllEmails" style="width:16px; height:16px; cursor:pointer;">
                        </th>
                        <th style="width:40px;">#</th>
                        <th style="width:250px;">Subject</th>
                        <th>Client</th>
                        <th style="width:130px;">Product</th>
                        <th style="width:50px;">Qty</th>
                        <th style="width:100px;">Date</th>
                        <th style="width:120px;">Assigned To</th>
                        <th style="width:110px;">Status</th>
                        <th style="width:90px; text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($emails as $index => $email)
                        <tr onclick="window.location='{{ route('crm.emails.show', $email->id) }}'"
                            style="cursor: pointer; transition: background 0.1s;" onmouseover="this.style.background='#f1f5f9'"
                            onmouseout="this.style.background='transparent'">
                            <td onclick="event.stopPropagation()">
                                <input type="checkbox" class="email-checkbox" value="{{ $email->id }}" style="width:16px; height:16px; cursor:pointer;">
                            </td>
                            <td style="color:#94a3b8; font-weight:500;">
                                {{ $emails->firstItem() + $index }}
                            </td>
                            <td style="font-weight: 600; color: #1e293b;">
                                <div style="display: flex; align-items: center; gap: 6px; max-width: 240px;">
                                    @if($email->status == 'New')
                                        <span style="font-size:7px; color:#3b82f6; flex-shrink: 0;"><i
                                                class="fas fa-circle"></i></span>
                                    @endif
                                    @if($email->unread_incoming > 0)
                                        <span title="{{ $email->unread_incoming }} New Messages"
                                            style="background:#ef4444; color:white; font-size: 0.6rem; padding: 2px 6px; border-radius: 99px; flex-shrink: 0;">{{ $email->unread_incoming }}</span>
                                    @endif
                                    <span title="{{ $email->subject }}"
                                        style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;">
                                        {{ Str::limit($email->subject, 20) ?: 'No Subject' }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div
                                    style="font-weight: 500; display: flex; align-items: center; gap: 5px; white-space: nowrap;">
                                    {{ $email->client_name }}
                                    @if($email->customer_type == 'RC')
                                        <span title="Returning Customer"
                                            style="background:#eff6ff; color:#3b82f6; font-size: 0.65rem; padding: 1px 5px; border-radius: 4px; border: 1px solid #dbeafe;">R</span>
                                    @else
                                        <span title="New Customer"
                                            style="background:#f0fdf4; color:#16a34a; font-size: 0.65rem; padding: 1px 5px; border-radius: 4px; border: 1px solid #dcfce7;">N</span>
                                    @endif
                                </div>
                                <div style="font-size: 0.8rem; color: #64748b;">{{ $email->client_email }}</div>
                                <div style="font-size: 0.7rem; color: #94a3b8; text-transform: capitalize; margin-top: 2px;"><i class="fas fa-bullseye" style="font-size: 0.65rem;"></i> {{ str_replace('_', ' ', strtolower($email->source) === 'form' ? 'website' : ($email->source ?: 'website')) }}</div>
                            </td>
                            <td>
                                <span
                                    style="background:#f1f5f9; padding:4px 8px; border-radius:6px; font-size:0.8rem; color:#475569; white-space: nowrap;">
                                    {{ Str::limit($email->product_name, 20) ?: 'General' }}
                                </span>
                            </td>
                            <td style="font-size: 0.9rem; color: #1e293b; font-weight: 600;">{{ $email->quantity ?: '-' }}</td>
                            <td style="font-size: 0.8rem; color: #64748b; white-space: nowrap;">
                                {{ $email->created_at->format('M d, H:i') }}</td>
                            <td>
                                @if($email->assigned_to)
                                    @php
                                        $aUser = \App\CrmUser::find($email->assigned_to);
                                        $aRole = $aUser ? $aUser->role : '';
                                        $aName = $aUser ? $aUser->name : 'Unknown';
                                        $aNameShort = strlen($aName) > 12 ? substr($aName, 0, 12) . '…' : $aName;
                                        $roleColorMap = [
                                            'admin'         => ['bg' => 'var(--primary-soft)', 'text' => 'var(--primary-purple)'],
                                            'sales_manager' => ['bg' => '#fce7f3', 'text' => '#9d174d'],
                                            'sales'         => ['bg' => '#dcfce7', 'text' => '#166534'],
                                            'designer'      => ['bg' => '#fff7ed', 'text' => '#c2410c'],
                                            'prepress'      => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
                                            'retention'     => ['bg' => '#eff6ff', 'text' => '#1d4ed8'],
                                            'qc'            => ['bg' => '#f0fdf4', 'text' => '#15803d'],
                                            'shipping'      => ['bg' => '#fdf4ff', 'text' => '#7e22ce'],
                                        ];
                                        $roleColor = isset($roleColorMap[$aRole]) ? $roleColorMap[$aRole] : ['bg' => '#f1f5f9', 'text' => '#475569'];
                                    @endphp
                                    <span title="{{ $aName }}"
                                        style="background:{{ $roleColor['bg'] }}; color:{{ $roleColor['text'] }}; padding:3px 8px; border-radius:99px; font-size:0.72rem; font-weight:700; white-space:nowrap; display:inline-block; max-width:120px;">
                                        <i class="fas fa-user-check" style="font-size:0.6rem;"></i> {{ $aNameShort }}
                                    </span>
                                @else
                                    <span
                                        style="background:#f1f5f9; color:#94a3b8; padding:3px 10px; border-radius:99px; font-size:0.75rem; font-weight:600;">
                                        Unassigned
                                    </span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $__inboxUser = Auth::guard('crm')->user();
                                    $__canChangeStatus = $__inboxUser->isAdmin() || $__inboxUser->isSalesManager() || $__inboxUser->isSales();
                                    $__statusColors = [
                                        'New'        => ['bg'=>'#eff6ff','color'=>'#1d4ed8'],
                                        'Order Done' => ['bg'=>'#dcfce7','color'=>'#166534'],
                                        'Responded'  => ['bg'=>'#f3e8ff','color'=>'#6b21a8'],
                                        'Viewed'     => ['bg'=>'#fef9c3','color'=>'#854d0e'],
                                        'Closed'     => ['bg'=>'#f1f5f9','color'=>'#475569'],
                                    ];
                                    $__sc = $__statusColors[$email->status] ?? ['bg'=>'#f1f5f9','color'=>'#475569'];
                                    $__statusLabel = ['New'=>'Unread','Viewed'=>'Read','Responded'=>'Replied','Order Done'=>'Completed','Closed'=>'Closed'][$email->status] ?? $email->status;
                                @endphp
                                @if($__canChangeStatus)
                                    <form action="{{ route('crm.emails.status', $email->id) }}" method="POST"
                                        onclick="event.stopPropagation()">
                                        {{ csrf_field() }}
                                        <select name="status" onchange="this.form.submit()" onclick="event.stopPropagation()"
                                            style="padding: 0.35rem 0.5rem; font-size: 0.8rem; border-radius: 8px; border: 1px solid #e2e8f0; width: 110px;
                                            background: {{ $__sc['bg'] }}; color: {{ $__sc['color'] }};">
                                            @foreach(['New' => 'Unread', 'Viewed' => 'Read', 'Responded' => 'Replied', 'Order Done' => 'Completed', 'Closed' => 'Closed'] as $val => $label)
                                                <option value="{{ $val }}" {{ $email->status == $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </form>
                                @else
                                    {{-- Read-only badge for production roles --}}
                                    <span style="display:inline-block; padding:3px 10px; border-radius:6px; font-size:0.78rem; font-weight:700;
                                        background:{{ $__sc['bg'] }}; color:{{ $__sc['color'] }};">
                                        {{ $__statusLabel }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; justify-content: flex-end;">
                                    <a href="{{ route('crm.emails.show', $email->id) }}" class="btn btn-primary"
                                        style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">View</a>

                                    @if(Auth::guard('crm')->user()->isAdmin())
                                        <div style="margin:0;">
                                            <button type="button" class="btn btn-danger" 
                                                onclick="event.stopPropagation(); deleteEmailRecord({{ $email->id }}, this)" 
                                                style="padding: 0.4rem 0.8rem; font-size: 0.8rem;">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                            <form id="delete-form-{{ $email->id }}" action="{{ route('crm.emails.destroy', $email->id) }}" method="POST" style="display:none;">
                                                {{ csrf_field() }}
                                                <input type="hidden" name="_method" value="DELETE">
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 4rem; color: #94a3b8;">
                                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.2;"></i>
                                <p>No emails found.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div style="padding: 0.75rem 1.25rem; border-top: 1px solid #f1f5f9;">
            {{ $emails->links() }}
        </div>
    </div>

    <!-- Bulk Action Floating Bar -->
    <div id="bulkActionBar"
        style="position: fixed; bottom: 2rem; left: 50%; transform: translateX(-50%); background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); display: none; align-items: center; gap: 1.5rem; padding: 0.75rem 1.5rem; border: 1px solid #e2e8f0; z-index: 1000; animation: slideUp 0.3s ease;">
        <div style="font-weight: 700; color: #1e293b; font-size: 0.9rem;">
            <span id="selectedCount">0</span> Items Selected
        </div>
        <div style="height: 24px; width: 1px; background: #e2e8f0;"></div>
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <span style="font-size: 0.85rem; font-weight: 600; color: #64748b;">Assign to:</span>
            <select id="bulkAssignUser"
                style="padding: 0.4rem 0.75rem; border-radius: 8px; border: 1px solid #d1d5db; font-size: 0.85rem; outline: none; background: #f9fafb;">
                <option value="">Select User...</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ strtoupper($u->role) }})</option>
                @endforeach
            </select>
            <button onclick="applyBulkAssign()" class="btn btn-primary"
                style="padding: 0.4rem 1.2rem; font-size: 0.85rem; font-weight: 700;">
                Apply
            </button>
        </div>
        <button onclick="clearSelection()"
            style="background: none; border: none; color: #ef4444; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
            Cancel
        </button>
    </div>

    <style>
        @keyframes slideUp {
            from {
                transform: translate(-50%, 100%);
                opacity: 0;
            }

            to {
                transform: translate(-50%, 0);
                opacity: 1;
            }
        }
    </style>

    <!-- Refresh Overlay -->
    <!-- Refresh Overlay -->
    <div id="refresh-overlay" class="refresh-overlay">
        <div id="refresh-content" class="refresh-content">
            <div class="refresh-spinner">
                <i class="fas fa-circle-notch"></i>
            </div>
            <div class="refresh-text">Refreshing...</div>
        </div>
    </div>

    <style>
        .refresh-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            pointer-events: none;
            opacity: 0;
            visibility: hidden;
            background: rgba(255, 255, 255, 0);
            transition: all 0.2s ease;
        }

        .refresh-overlay.active {
            visibility: visible;
            opacity: 1;
            pointer-events: all;
            background: rgba(255, 255, 255, 0.5);
        }

        .refresh-content {
            text-align: center;
            transform: scale(0.95);
            opacity: 0;
            transition: all 0.2s ease;
        }

        .refresh-overlay.active .refresh-content {
            transform: scale(1);
            opacity: 1;
        }

        .refresh-spinner {
            font-size: 2.8rem;
            color: var(--primary-purple);
            animation: spin 0.8s linear infinite;
        }

        .refresh-text {
            margin-top: 1.5rem;
            font-weight: 600;
            color: #334155;
            font-size: 1.1rem;
            letter-spacing: 0.03em;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>

    <script>
        function refreshInbox() {
            const overlay = document.getElementById('refresh-overlay');

            // 1. Activate Overlay immediately
            requestAnimationFrame(() => {
                overlay.classList.add('active');
            });

            // 2. Minimum natural delay (3 seconds)
            const minDelay = new Promise(resolve => setTimeout(resolve, 3000));

            // 3. Fetch Data
            const fetchData = fetch(window.location.href, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(res => {
                    if (!res.ok) throw new Error('Network response was not ok');
                    return res.text();
                });

            Promise.all([minDelay, fetchData])
                .then(([_, html]) => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    // Surgical Update
                    const newCard = doc.querySelector('.content-card');
                    const oldCard = document.querySelector('.content-card');

                    if (newCard && oldCard) {
                        oldCard.innerHTML = newCard.innerHTML;
                    }

                    overlay.classList.remove('active');

                    showToast('Inquiries refreshed successfully', 'success');
                })
                .catch(err => {
                    console.error('Refresh failed:', err);
                    overlay.classList.remove('active');
                    showToast('Update Failed', 'error');
                });
        }

        function deleteEmailRecord(id, btn) {
            customConfirm(
                'Delete Inquiry?', 
                'Are you sure you want to remove this record? This action cannot be undone.', 
                () => {
                    document.getElementById(`delete-form-${id}`).submit();
                }
            );
        }

        // Bulk Selection Logic
        const bulkBar = document.getElementById('bulkActionBar');
        const countDisplay = document.getElementById('selectedCount');

        function updateBulkBar() {
            const selected = document.querySelectorAll('.email-checkbox:checked');
            if (selected.length > 0) {
                bulkBar.style.display = 'flex';
                countDisplay.innerText = selected.length;
            } else {
                bulkBar.style.display = 'none';
            }
            const selectAll = document.getElementById('selectAllEmails');
            const checkboxes = document.querySelectorAll('.email-checkbox');
            if (selectAll) {
                selectAll.checked = (selected.length === checkboxes.length && checkboxes.length > 0);
            }
        }

        function bindCheckboxEvents() {
            const selectAll = document.getElementById('selectAllEmails');
            const checkboxes = document.querySelectorAll('.email-checkbox');
            
            if (selectAll) {
                // Remove old listener if exists, add new one
                selectAll.replaceWith(selectAll.cloneNode(true));
                document.getElementById('selectAllEmails').addEventListener('change', function() {
                    document.querySelectorAll('.email-checkbox').forEach(cb => {
                        cb.checked = this.checked;
                    });
                    updateBulkBar();
                });
            }

            checkboxes.forEach(cb => {
                cb.replaceWith(cb.cloneNode(true));
            });
            document.querySelectorAll('.email-checkbox').forEach(cb => {
                cb.addEventListener('change', updateBulkBar);
            });
        }
        
        // Initial bind
        bindCheckboxEvents();

        function clearSelection() {
            document.querySelectorAll('.email-checkbox').forEach(cb => cb.checked = false);
            const selectAll = document.getElementById('selectAllEmails');
            if (selectAll) selectAll.checked = false;
            updateBulkBar();
        }

        // Live Search & Filter AJAX
        let filterTimeout = null;
        let inboxFilterController = null;
        let inboxFilterRequestId = 0;

        function applyFilters() {
            const form = document.getElementById('inboxFilterForm');
            const url = new URL(form.action);
            const formData = new FormData(form);
            
            for (const [key, value] of formData.entries()) {
                if (value) {
                    url.searchParams.set(key, value);
                }
            }

            window.history.replaceState({}, '', url);

            if (inboxFilterController) inboxFilterController.abort();
            inboxFilterController = new AbortController();
            const requestId = ++inboxFilterRequestId;

            const overlay = document.getElementById('refresh-overlay');
            requestAnimationFrame(() => overlay.classList.add('active'));

            fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: inboxFilterController.signal
            })
                .then(res => {
                    if (!res.ok) throw new Error(`Search failed (${res.status})`);
                    return res.text();
                })
                .then(html => {
                    if (requestId !== inboxFilterRequestId) return;
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    const newCard = doc.querySelector('.content-card');
                    const oldCard = document.querySelector('.content-card');
                    if (!newCard || !oldCard) throw new Error('Search results were not found in the response');
                    oldCard.innerHTML = newCard.innerHTML;

                    const newFilters = doc.getElementById('activeFiltersContainer');
                    const oldFilters = document.getElementById('activeFiltersContainer');
                    if (newFilters && oldFilters) oldFilters.innerHTML = newFilters.innerHTML;

                    bindCheckboxEvents();
                })
                .catch(err => {
                    if (err.name !== 'AbortError') {
                        console.error('Filter AJAX failed:', err);
                        showToast('Search could not be completed. Please try again.', 'error');
                    }
                })
                .finally(() => {
                    if (requestId === inboxFilterRequestId) overlay.classList.remove('active');
                });
        }

        window.applyFilters = applyFilters;

        // AJAX Triggers — bind immediately for SPA navigation and again on a normal page load.
        function bindInboxFilters() {
            document.querySelectorAll('.ajax-trigger').forEach(element => {
                if (element.dataset.inboxFilterBound === '1') return;
                element.dataset.inboxFilterBound = '1';

                if (element.id === 'searchInput') {
                    element.addEventListener('input', function() {
                        clearTimeout(filterTimeout);
                        filterTimeout = setTimeout(applyFilters, 350);
                    });
                } else {
                    element.addEventListener('change', applyFilters);
                }
            });
        }

        bindInboxFilters();
        document.addEventListener('DOMContentLoaded', bindInboxFilters);

        function applyBulkAssign() {
            const userSelect = document.getElementById('bulkAssignUser');
            const userId = userSelect.value;
            const userName = userSelect.options[userSelect.selectedIndex].text.split('(')[0].trim();
            const selectedIds = Array.from(document.querySelectorAll('.email-checkbox:checked')).map(cb => cb.value);

            if (!userId) {
                showToast('Please select a user to assign.', 'error');
                return;
            }

            customConfirm(
                'Bulk Assignment',
                `Assign ${selectedIds.length} emails to ${userName}?`,
                () => {
                    document.getElementById('refresh-overlay').style.display = 'flex';

                    fetch("{{ route('crm.emails.bulk_assign') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                email_ids: selectedIds,
                                assigned_to: userId
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                location.reload();
                            } else {
                                document.getElementById('refresh-overlay').style.display = 'none';
                                showToast(data.message || 'Error assigning emails', 'error');
                            }
                        })
                        .catch(err => {
                            document.getElementById('refresh-overlay').style.display = 'none';
                            console.error(err);
                            showToast('Connection error', 'error');
                        });
                },
                'Yes, Assign',
                'btn-primary-confirm'
            );
        }

        function openCreateModal() {
            document.getElementById('createInquiryModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeCreateModal() {
            document.getElementById('createInquiryModal').style.display = 'none';
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') closeCreateModal();
        });
    </script>

    {{-- Create Inquiry Modal --}}
    <div id="createInquiryModal" class="inquiry-modal-backdrop" role="dialog" aria-modal="true"
        aria-labelledby="createInquiryTitle" onclick="if (event.target === this) closeCreateModal()">
        <div class="inquiry-modal">
            <div class="inquiry-modal-header">
                <div class="inquiry-modal-title-wrap">
                    <span class="inquiry-modal-icon"><i class="fas fa-plus"></i></span>
                    <div>
                        <h3 class="inquiry-modal-title" id="createInquiryTitle">Create Manual Inquiry</h3>
                        <p class="inquiry-modal-subtitle">Add customer and inquiry details to your CRM.</p>
                    </div>
                </div>
                <button type="button" class="inquiry-modal-close" onclick="closeCreateModal()" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form action="{{ route('crm.emails.create_manual') }}" method="POST" class="inquiry-modal-body">
                {{ csrf_field() }}

                <p class="inquiry-section-label"><i class="fas fa-user"></i> Customer details</p>
                <div class="inquiry-form-grid">
                    <div class="inquiry-field">
                        <label>Client Name <span class="inquiry-required">*</span></label>
                        <input type="text" name="client_name" required placeholder="e.g. John Smith" class="inq-input">
                    </div>
                    <div class="inquiry-field">
                        <label>Client Email <span class="inquiry-required">*</span></label>
                        <input type="email" name="client_email" required placeholder="e.g. john@company.com" class="inq-input">
                    </div>
                    <div class="inquiry-field">
                        <label>Client Phone</label>
                        <input type="text" name="client_phone" placeholder="e.g. +1 (555) 123-4567" class="inq-input">
                    </div>
                    <div class="inquiry-field">
                        <label>Inquiry Date <span class="inquiry-required">*</span></label>
                        <input type="date" name="inquiry_date" required value="{{ date('Y-m-d') }}" class="inq-input">
                    </div>
                </div>

                <p class="inquiry-section-label"><i class="fas fa-clipboard-list"></i> Inquiry details</p>
                <div class="inquiry-form-grid">
                    <div class="inquiry-field inquiry-field-half">
                        <label>Product / Subject</label>
                        <input type="text" name="product_name" placeholder="e.g. Custom Cardboard Boxes" class="inq-input">
                    </div>
                    <div class="inquiry-field inquiry-field-narrow">
                        <label>Quantity</label>
                        <input type="number" name="quantity" min="1" placeholder="e.g. 1000" class="inq-input">
                    </div>
                    <div class="inquiry-field inquiry-field-small">
                        <label>Source <span class="inquiry-required">*</span></label>
                        <select name="source" required class="inq-input">
                            <option value="manual">Manual</option>
                            <option value="call">Call</option>
                            <option value="live_chat">Live Chat</option>
                            <option value="email">Email</option>
                            <option value="whatsapp">WhatsApp</option>
                            <option value="google_ads">Google Ads</option>
                            <option value="social">Social Media</option>
                            <option value="walk_in">Walk-In</option>
                        </select>
                    </div>
                    <div class="inquiry-field inquiry-field-full">
                        <label>Inquiry Message / Details</label>
                        <textarea name="message" rows="3" placeholder="Add sizes, material, printing, delivery or other customer requirements..." class="inq-input"></textarea>
                    </div>
                </div>

                <div class="inquiry-modal-actions">
                    <button type="button" onclick="closeCreateModal()" class="inquiry-action-btn inquiry-action-cancel">Cancel</button>
                    <button type="submit" class="inquiry-action-btn inquiry-action-submit">
                        <i class="fas fa-check-circle"></i> Create &amp; Open Inquiry
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function () {
            // "All Dates" uses a very wide sentinel range — the apply handler detects it and clears the filter instead.
            var __ALL_START = moment('1970-01-01');
            var __ALL_END   = moment().add(50, 'years');
            $('#inboxDateRangePicker').daterangepicker({
                autoUpdateInput: false,
                startDate: $('#inboxStartDate').val() ? moment($('#inboxStartDate').val()) : moment().subtract(29, 'days'),
                endDate: $('#inboxEndDate').val() ? moment($('#inboxEndDate').val()) : moment(),
                locale: { cancelLabel: 'Clear', format: 'YYYY-MM-DD' },
                ranges: {
                    'All Dates': [__ALL_START, __ALL_END],
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                }
            });

            if ($('#inboxStartDate').val() && $('#inboxEndDate').val()) {
                $('#inboxDateRangePicker').val($('#inboxStartDate').val() + ' — ' + $('#inboxEndDate').val());
            }

            $('#inboxDateRangePicker').on('apply.daterangepicker', function (ev, picker) {
                // If the "All Dates" sentinel was picked, treat it as clear.
                if (picker.startDate.isSame(__ALL_START, 'day') && picker.endDate.isSame(__ALL_END, 'day')) {
                    $(this).val('');
                    $('#inboxStartDate').val('');
                    $('#inboxEndDate').val('');
                } else {
                    $(this).val(picker.startDate.format('YYYY-MM-DD') + ' — ' + picker.endDate.format('YYYY-MM-DD'));
                    $('#inboxStartDate').val(picker.startDate.format('YYYY-MM-DD'));
                    $('#inboxEndDate').val(picker.endDate.format('YYYY-MM-DD'));
                }
                if (window.applyFilters) applyFilters(); // live-apply the date range
            });

            $('#inboxDateRangePicker').on('cancel.daterangepicker', function () {
                $(this).val('');
                $('#inboxStartDate').val('');
                $('#inboxEndDate').val('');
                if (window.applyFilters) applyFilters(); // live-clear the date range
            });
        });
    </script>
@endsection
