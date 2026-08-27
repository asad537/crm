@extends('crm.layout')

@section('title', ' Dashboard')

@section('header_actions')
    <div
        style="display: flex; gap: 0.5rem; background: #fff; padding: 4px; border-radius: 12px; box-shadow: 0 2px 5px rgba(0,0,0,0.03);">
        <button class="filter-btn active">Today</button>
        <button class="filter-btn">Weekly</button>
        <button class="filter-btn">Monthly</button>
        <button class="filter-btn">Yearly</button>
    </div>
@endsection

@section('content')
    <style>
        /* ── FILTER BUTTONS ─────────────────────────── */
        .filter-btn {
            border: none;
            background: transparent;
            color: #64748b;
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            letter-spacing: 0.01em;
        }

        .filter-btn.active {
            background: var(--primary-purple);
            color: #fff;
            box-shadow: 0 4px 12px var(--primary-shadow);
        }

        .filter-btn:hover:not(.active) {
            background: var(--primary-soft);
            color: var(--primary-purple);
        }

        /* ── LAYOUT ─────────────────────────────────── */
        .dashboard-container {
            max-width: 1600px;
            margin: 0 auto;
        }

        /* ── STAT CARDS ─────────────────────────────── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
            min-width: 0;
        }

        .stats-grid>* {
            min-width: 0;
        }

        .stat-box {
            cursor: pointer;
            background: #fff;
            border-radius: 16px;
            padding: 1.1rem 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 4px 16px rgba(0, 0, 0, 0.03);
            border: 1px solid #f1f5f9;
            transition: transform 0.2s, box-shadow 0.2s;
            min-height: 120px;
            position: relative;
            overflow: hidden;
        }

        .stat-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            border-radius: 16px 16px 0 0;
        }

        .stat-box.s-blue::before {
            background: linear-gradient(90deg, var(--primary-purple), var(--primary-purple));
        }

        .stat-box.s-red::before {
            background: linear-gradient(90deg, #f43f5e, #fb923c);
        }

        .stat-box.s-purple::before {
            background: linear-gradient(90deg, #a855f7, #ec4899);
        }

        .stat-box.s-green::before {
            background: linear-gradient(90deg, #10b981, #06b6d4);
        }

        .stat-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
        }

        .stat-content {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            width: 100%;
        }

        .stat-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 0.4rem;
        }

        .stat-number {
            font-size: 2.4rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -2px;
            line-height: 1;
        }

        .stat-trend {
            font-size: 0.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 0.5rem;
            flex-wrap: nowrap;
        }

        .trend-up {
            color: #10b981;
        }

        .trend-down {
            color: #f43f5e;
        }

        .trend-text {
            color: #94a3b8;
            font-weight: 500;
            font-size: 0.72rem;
            white-space: nowrap;
        }

        .icon-box {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
            margin-left: 0.75rem;
        }

        .box-blue {
            background: linear-gradient(135deg, var(--primary-soft), #dbeafe);
            color: var(--primary-purple);
        }

        .box-red {
            background: linear-gradient(135deg, #fce7f3, #fee2e2);
            color: #f43f5e;
        }

        .box-purple {
            background: linear-gradient(135deg, #f3e8ff, #fce7f3);
            color: #a855f7;
        }

        .box-green {
            background: linear-gradient(135deg, #d1fae5, #cffafe);
            color: #10b981;
        }

        /* ── MIDDLE GRID ────────────────────────────── */
        .middle-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 1.25rem;
            align-items: stretch;
            margin-bottom: 1.25rem;
            min-width: 0;
        }

        .middle-grid>* {
            min-width: 0;
            overflow: hidden;
        }

        /* ── CHART CARDS ────────────────────────────── */
        .chart-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.4rem;
            display: flex;
            flex-direction: column;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 4px 16px rgba(0, 0, 0, 0.03);
            border: 1px solid #f1f5f9;
            min-height: 380px;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.2rem;
        }

        .chart-title h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
        }

        .chart-title p {
            margin: 3px 0 0 0;
            color: #94a3b8;
            font-size: 0.75rem;
        }

        .year-drop {
            display: flex;
            align-items: center;
            font-weight: 600;
            font-size: 0.8rem;
            color: #475569;
            gap: 0.4rem;
        }

        /* ── LOCATION CARD ──────────────────────────── */
        .location-list {
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            margin-top: 0.75rem;
        }

        .loc-item {
            display: grid;
            grid-template-columns: 72px 1fr 36px;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            color: #475569;
            font-weight: 600;
        }

        .loc-bar-bg {
            width: 100%;
            height: 7px;
            background: #f1f5f9;
            border-radius: 99px;
            position: relative;
            overflow: hidden;
        }

        .loc-bar-fill {
            height: 100%;
            border-radius: 99px;
            position: absolute;
            left: 0;
            top: 0;
            transition: width 0.8s ease;
        }

        /* ── BOTTOM CHART ───────────────────────────── */
        .bottom-chart-container {
            background: #fff;
            border-radius: 16px;
            padding: 1.4rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 4px 16px rgba(0, 0, 0, 0.03);
            border: 1px solid #f1f5f9;
            min-height: 280px;
            display: flex;
            flex-direction: column;
        }

        /* ── RESPONSIVE ─────────────────────────────── */
        @media (max-width:1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .middle-grid {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
        }

        @media (max-width:700px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .chart-card,
            .bottom-chart-container {
                padding: 1rem;
            }

            .stat-box {
                min-height: auto;
            }
        }
    </style>

    <div class="dashboard-container">

        <!-- STATS CARDS -->
        @php $__dashUser = Auth::guard('crm')->user(); @endphp

        @if($__dashUser && $__dashUser->isEstimator())
        {{-- ── ESTIMATOR STATS (3 cards) ── --}}
        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <!-- Card 1: Total Estimations -->
            <div class="stat-box s-blue" onclick="window.location='{{ route('crm.leads.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Total Estimations</div>
                    <div class="stat-number" id="stat-est-total">{{ $estimatorStats['total'] }}</div>
                    <div class="stat-trend" style="color:var(--primary-purple);">
                        <i class="fas fa-calculator" style="font-size:0.9rem;"></i>
                        <span class="trend-text" id="est-total-label">All assigned to you</span>
                    </div>
                </div>
                <div class="icon-box box-blue">
                    <i class="fas fa-calculator"></i>
                </div>
            </div>

            <!-- Card 2: Estimated (submitted) -->
            <div class="stat-box s-green" onclick="window.location='{{ route('crm.leads.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Estimated</div>
                    <div class="stat-number" id="stat-est-estimated">{{ $estimatorStats['estimated'] }}</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-check-circle" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Submitted estimates</span>
                    </div>
                </div>
                <div class="icon-box box-green">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>

            <!-- Card 3: Pending -->
            <div class="stat-box s-red" onclick="window.location='{{ route('crm.leads.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Pending Estimations</div>
                    <div class="stat-number" id="stat-est-pending">{{ $estimatorStats['pending'] }}</div>
                    <div class="stat-trend {{ $estimatorStats['pending'] > 0 ? 'trend-down' : 'trend-up' }}" id="est-pending-trend">
                        <i class="fas fa-clock" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Awaiting your estimate</span>
                    </div>
                </div>
                <div class="icon-box box-red">
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </div>
        </div>

        @elseif($__dashUser && $__dashUser->isDesigner())
        {{-- ── DESIGNER STATS (3 cards) ── --}}
        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <!-- Card 1: Total Designs -->
            <div class="stat-box s-blue" onclick="window.location='{{ route('crm.design_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Total Designs</div>
                    <div class="stat-number" id="stat-des-total">{{ $designerStats['total'] }}</div>
                    <div class="stat-trend" style="color:var(--primary-purple);">
                        <i class="fas fa-layer-group" style="font-size:0.9rem;"></i>
                        <span class="trend-text" id="des-total-label">Selected period</span>
                    </div>
                </div>
                <div class="icon-box box-blue">
                    <i class="fas fa-paint-brush"></i>
                </div>
            </div>

            <!-- Card 2: Design Completed -->
            <div class="stat-box s-green" onclick="window.location='{{ route('crm.design_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Design Completed</div>
                    <div class="stat-number" id="stat-des-completed">{{ $designerStats['completed'] }}</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-check-circle" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Finished designs</span>
                    </div>
                </div>
                <div class="icon-box box-green">
                    <i class="fas fa-palette"></i>
                </div>
            </div>

            <!-- Card 3: Design Pending -->
            <div class="stat-box s-red" onclick="window.location='{{ route('crm.design_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Design Pending</div>
                    <div class="stat-number" id="stat-des-pending">{{ $designerStats['pending'] }}</div>
                    <div class="stat-trend {{ $designerStats['pending'] > 0 ? 'trend-down' : 'trend-up' }}" id="des-pending-trend">
                        <i class="fas fa-clock" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Awaiting your design</span>
                    </div>
                </div>
                <div class="icon-box box-red">
                    <i class="fas fa-bezier-curve"></i>
                </div>
            </div>
        </div>

        @elseif($__dashUser && $__dashUser->isPrepress())
        {{-- ── PREPRESS STATS (3 cards) ── --}}
        <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
            <!-- Card 1: Total Prepress Tickets -->
            <div class="stat-box s-blue" onclick="window.location='{{ route('crm.prepress_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Total Prepress Tickets</div>
                    <div class="stat-number" id="stat-prep-total">{{ $prepressStats['total'] }}</div>
                    <div class="stat-trend" style="color:var(--primary-purple);">
                        <i class="fas fa-layer-group" style="font-size:0.9rem;"></i>
                        <span class="trend-text" id="prep-total-label">Selected period</span>
                    </div>
                </div>
                <div class="icon-box box-blue">
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>

            <!-- Card 2: Prepress Completed -->
            <div class="stat-box s-green" onclick="window.location='{{ route('crm.prepress_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Prepress Completed</div>
                    <div class="stat-number" id="stat-prep-completed">{{ $prepressStats['completed'] }}</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-check-circle" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Sent to production</span>
                    </div>
                </div>
                <div class="icon-box box-green">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>

            <!-- Card 3: Prepress Pending -->
            <div class="stat-box s-red" onclick="window.location='{{ route('crm.prepress_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Prepress Pending</div>
                    <div class="stat-number" id="stat-prep-pending">{{ $prepressStats['pending'] }}</div>
                    <div class="stat-trend {{ $prepressStats['pending'] > 0 ? 'trend-down' : 'trend-up' }}" id="prep-pending-trend">
                        <i class="fas fa-clock" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Awaiting prepress review</span>
                    </div>
                </div>
                <div class="icon-box box-red">
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </div>
        </div>

        @elseif($__dashUser && $__dashUser->isProductionManager())
        {{-- ── PRODUCTION STATS (4 cards) ── --}}
        <div class="stats-grid">
            <!-- Card 1: Jobs in Queue -->
            <div class="stat-box s-blue" onclick="window.location='{{ route('crm.production_jobs.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Jobs in Queue</div>
                    <div class="stat-number" id="stat-prod-queue">{{ $productionStats['in_queue'] }}</div>
                    <div class="stat-trend" style="color:var(--primary-purple);">
                        <i class="fas fa-layer-group" style="font-size:0.9rem;"></i>
                        <span class="trend-text" id="prod-queue-label">Pending planning</span>
                    </div>
                </div>
                <div class="icon-box box-blue">
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>

            <!-- Card 2: Active in Press -->
            <div class="stat-box s-purple" onclick="window.location='{{ route('crm.press_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Active in Press</div>
                    <div class="stat-number" id="stat-prod-press">{{ $productionStats['in_press'] }}</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-cogs" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Currently in production</span>
                    </div>
                </div>
                <div class="icon-box box-purple">
                    <i class="fas fa-print"></i>
                </div>
            </div>

            <!-- Card 3: Pending QC -->
            <div class="stat-box s-red" onclick="window.location='{{ route('crm.qc_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Pending QC Approvals</div>
                    <div class="stat-number" id="stat-prod-qc">{{ $productionStats['pending_qc'] }}</div>
                    <div class="stat-trend {{ $productionStats['pending_qc'] > 0 ? 'trend-down' : 'trend-up' }}" id="prod-qc-trend">
                        <i class="fas fa-search" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Awaiting inspection</span>
                    </div>
                </div>
                <div class="icon-box box-red">
                    <i class="fas fa-clipboard-check"></i>
                </div>
            </div>

            <!-- Card 4: Completed -->
            <div class="stat-box s-green" onclick="window.location='{{ route('crm.warehouse_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Completed Jobs</div>
                    <div class="stat-number" id="stat-prod-completed">{{ $productionStats['completed'] }}</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-check-circle" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Warehouse ready</span>
                    </div>
                </div>
                <div class="icon-box box-green">
                    <i class="fas fa-box-open"></i>
                </div>
            </div>
        </div>

        @elseif($__dashUser && $__dashUser->isQC())
        {{-- ── QC INSPECTOR STATS (4 cards) ── --}}
        <div class="stats-grid">
            <div class="stat-box s-blue" onclick="window.location='{{ route('crm.qc_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Assigned QC Tickets</div>
                    <div class="stat-number" id="stat-qc-assigned">{{ $qcStats['assigned'] }}</div>
                    <div class="stat-trend" style="color:var(--primary-purple);">
                        <i class="fas fa-clipboard-list" style="font-size:0.9rem;"></i>
                        <span class="trend-text" id="qc-assigned-label">Today</span>
                    </div>
                </div>
                <div class="icon-box box-blue">
                    <i class="fas fa-clipboard-check"></i>
                </div>
            </div>

            <div class="stat-box s-red" onclick="window.location='{{ route('crm.qc_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">First Sheet Pending</div>
                    <div class="stat-number" id="stat-qc-first">{{ $qcStats['first_sheet'] }}</div>
                    <div class="stat-trend {{ $qcStats['first_sheet'] > 0 ? 'trend-down' : 'trend-up' }}" id="qc-first-trend">
                        <i class="fas fa-search" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Waiting inspection</span>
                    </div>
                </div>
                <div class="icon-box box-red">
                    <i class="fas fa-file-signature"></i>
                </div>
            </div>

            <div class="stat-box s-purple" onclick="window.location='{{ route('crm.qc_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Final QC Pending</div>
                    <div class="stat-number" id="stat-qc-final">{{ $qcStats['final_qc'] }}</div>
                    <div class="stat-trend {{ $qcStats['final_qc'] > 0 ? 'trend-down' : 'trend-up' }}" id="qc-final-trend">
                        <i class="fas fa-ruler-combined" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Before packing</span>
                    </div>
                </div>
                <div class="icon-box box-purple">
                    <i class="fas fa-microscope"></i>
                </div>
            </div>

            <div class="stat-box s-green" onclick="window.location='{{ route('crm.qc_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">QC Passed</div>
                    <div class="stat-number" id="stat-qc-passed">{{ $qcStats['passed'] }}</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-check-circle" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Approved sheets</span>
                    </div>
                </div>
                <div class="icon-box box-green">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>
        </div>

        @elseif(false)
        {{-- ── PRODUCTION SUPERVISOR STATS (4 cards) ── --}}
        <div class="stats-grid">
            <div class="stat-box s-blue" onclick="window.location='{{ route('crm.supervisor_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Assigned Supervisor Tickets</div>
                    <div class="stat-number" id="stat-sup-assigned">{{ $supervisorStats['assigned'] }}</div>
                    <div class="stat-trend" style="color:var(--primary-purple);">
                        <i class="fas fa-clipboard-list" style="font-size:0.9rem;"></i>
                        <span class="trend-text" id="sup-assigned-label">Today</span>
                    </div>
                </div>
                <div class="icon-box box-blue">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>

            <div class="stat-box s-red" onclick="window.location='{{ route('crm.supervisor_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Needs Review</div>
                    <div class="stat-number" id="stat-sup-review">{{ $supervisorStats['needs_review'] }}</div>
                    <div class="stat-trend {{ $supervisorStats['needs_review'] > 0 ? 'trend-down' : 'trend-up' }}" id="sup-review-trend">
                        <i class="fas fa-clock" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Awaiting decision</span>
                    </div>
                </div>
                <div class="icon-box box-red">
                    <i class="fas fa-clipboard-question"></i>
                </div>
            </div>

            <div class="stat-box s-purple" onclick="window.location='{{ route('crm.supervisor_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Active Finishing</div>
                    <div class="stat-number" id="stat-sup-active">{{ $supervisorStats['active'] }}</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-cogs" style="font-size:0.9rem;"></i>
                        <span class="trend-text">In post production</span>
                    </div>
                </div>
                <div class="icon-box box-purple">
                    <i class="fas fa-layer-group"></i>
                </div>
            </div>

            <div class="stat-box s-green" onclick="window.location='{{ route('crm.supervisor_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Completed Handoff</div>
                    <div class="stat-number" id="stat-sup-completed">{{ $supervisorStats['completed'] }}</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-check-circle" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Warehouse ready</span>
                    </div>
                </div>
                <div class="icon-box box-green">
                    <i class="fas fa-box-open"></i>
                </div>
            </div>
        </div>

        @elseif($__dashUser && $__dashUser->isPressOperator())
        {{-- ── PRESS OPERATOR STATS (4 cards) ── --}}
        <div class="stats-grid">
            <!-- Card 1: Assigned Tickets -->
            <div class="stat-box s-blue" onclick="window.location='{{ route('crm.press_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Assigned Press Tickets</div>
                    <div class="stat-number" id="stat-po-assigned">{{ $pressStats['assigned'] }}</div>
                    <div class="stat-trend" style="color:var(--primary-purple);">
                        <i class="fas fa-list-ul" style="font-size:0.9rem;"></i>
                        <span class="trend-text" id="po-assigned-label">Selected period</span>
                    </div>
                </div>
                <div class="icon-box box-blue">
                    <i class="fas fa-clipboard-list"></i>
                </div>
            </div>

            <!-- Card 2: Pending Setup -->
            <div class="stat-box s-red" onclick="window.location='{{ route('crm.press_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Pending Setup</div>
                    <div class="stat-number" id="stat-po-setup">{{ $pressStats['in_setup'] }}</div>
                    <div class="stat-trend {{ $pressStats['in_setup'] > 0 ? 'trend-down' : 'trend-up' }}" id="po-setup-trend">
                        <i class="fas fa-clock" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Awaiting calibration</span>
                    </div>
                </div>
                <div class="icon-box box-red">
                    <i class="fas fa-tools"></i>
                </div>
            </div>

            <!-- Card 3: Active in Press -->
            <div class="stat-box s-purple" onclick="window.location='{{ route('crm.press_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Running Production</div>
                    <div class="stat-number" id="stat-po-running">{{ $pressStats['running'] }}</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-cogs" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Currently printing</span>
                    </div>
                </div>
                <div class="icon-box box-purple">
                    <i class="fas fa-print"></i>
                </div>
            </div>

            <!-- Card 4: Completed Runs -->
            <div class="stat-box s-green" onclick="window.location='{{ route('crm.press_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Runs Completed</div>
                    <div class="stat-number" id="stat-po-completed">{{ $pressStats['completed'] }}</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-check-circle" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Sent to supervisor</span>
                    </div>
                </div>
                <div class="icon-box box-green">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>
        </div>

        @elseif($__dashUser && $__dashUser->isAccounts())
        {{-- ── ACCOUNTS STATS (4 cards) ── --}}
        <div class="stats-grid">
            <div class="stat-box s-blue" onclick="window.location='{{ route('crm.accounts_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Pending Check</div>
                    <div class="stat-number" id="stat-acc-check">{{ $accountsStats['pending_check'] }}</div>
                    <div class="stat-trend {{ $accountsStats['pending_check'] > 0 ? 'trend-down' : 'trend-up' }}" id="acc-check-trend">
                        <i class="fas fa-search" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Warehouse handoffs</span>
                    </div>
                </div>
                <div class="icon-box box-blue">
                    <i class="fas fa-search-dollar"></i>
                </div>
            </div>

            <div class="stat-box s-red" onclick="window.location='{{ route('crm.accounts_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Pending Payment</div>
                    <div class="stat-number" id="stat-acc-payment">{{ $accountsStats['pending_payment'] }}</div>
                    <div class="stat-trend {{ $accountsStats['pending_payment'] > 0 ? 'trend-down' : 'trend-up' }}" id="acc-payment-trend">
                        <i class="fas fa-clock" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Awaiting balance</span>
                    </div>
                </div>
                <div class="icon-box box-red">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
            </div>

            <div class="stat-box s-purple" onclick="window.location='{{ route('crm.accounts_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Pending Invoice</div>
                    <div class="stat-number" id="stat-acc-invoice">{{ $accountsStats['invoiced'] }}</div>
                    <div class="stat-trend {{ $accountsStats['invoiced'] > 0 ? 'trend-down' : 'trend-up' }}" id="acc-invoice-trend">
                        <i class="fas fa-file-signature" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Delivered orders</span>
                    </div>
                </div>
                <div class="icon-box box-purple">
                    <i class="fas fa-receipt"></i>
                </div>
            </div>

            <div class="stat-box s-green" onclick="window.location='{{ route('crm.accounts_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Payment Posted</div>
                    <div class="stat-number" id="stat-acc-completed">{{ $accountsStats['completed'] }}</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-check-circle" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Completed orders</span>
                    </div>
                </div>
                <div class="icon-box box-green">
                    <i class="fas fa-check-double"></i>
                </div>
            </div>
        </div>

        @elseif($__dashUser && $__dashUser->isShipping())
        {{-- ── SHIPPING STATS (4 cards) ── --}}
        <div class="stats-grid">
            <div class="stat-box s-blue" onclick="window.location='{{ route('crm.shipping_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Pending Shipping</div>
                    <div class="stat-number" id="stat-ship-pending">{{ $shippingStats['pending'] }}</div>
                    <div class="stat-trend {{ $shippingStats['pending'] > 0 ? 'trend-down' : 'trend-up' }}" id="ship-pending-trend">
                        <i class="fas fa-box" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Awaiting fulfillment</span>
                    </div>
                </div>
                <div class="icon-box box-blue">
                    <i class="fas fa-boxes"></i>
                </div>
            </div>

            <div class="stat-box s-red" onclick="window.location='{{ route('crm.shipping_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Label Generated</div>
                    <div class="stat-number" id="stat-ship-label">{{ $shippingStats['label_generated'] }}</div>
                    <div class="stat-trend {{ $shippingStats['label_generated'] > 0 ? 'trend-down' : 'trend-up' }}" id="ship-label-trend">
                        <i class="fas fa-barcode" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Ready for pickup</span>
                    </div>
                </div>
                <div class="icon-box box-red">
                    <i class="fas fa-ticket-alt"></i>
                </div>
            </div>

            <div class="stat-box s-purple" onclick="window.location='{{ route('crm.shipping_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">In Transit</div>
                    <div class="stat-number" id="stat-ship-transit">{{ $shippingStats['in_transit'] }}</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-truck-moving" style="font-size:0.9rem;"></i>
                        <span class="trend-text">On the way</span>
                    </div>
                </div>
                <div class="icon-box box-purple">
                    <i class="fas fa-truck-fast"></i>
                </div>
            </div>

            <div class="stat-box s-green" onclick="window.location='{{ route('crm.shipping_tickets.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Delivered</div>
                    <div class="stat-number" id="stat-ship-delivered">{{ $shippingStats['delivered'] }}</div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-check-circle" style="font-size:0.9rem;"></i>
                        <span class="trend-text">Successfully shipped</span>
                    </div>
                </div>
                <div class="icon-box box-green">
                    <i class="fas fa-home"></i>
                </div>
            </div>
        </div>

        @else
        {{-- ── DEFAULT STATS (4 cards for all other roles) ── --}}
        <div class="stats-grid">
            <!-- Card 1: Total -->
            <div class="stat-box s-blue" onclick="window.location='{{ route('crm.emails.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Total Quotations</div>
                    <div class="stat-number" id="stat-total">{{ number_format($totalEmails) }}</div>
                    @php $t = $trends['total'];
                    $color = $t >= 0 ? 'trend-up' : 'trend-down'; @endphp
                    <div class="stat-trend {{ $color }}" id="trend-total-container">
                        <span id="trend-total">{{ $t > 0 ? '+' : '' }}{{ $t }}%</span>
                        <span class="trend-text">vs last period</span>
                    </div>
                </div>
                <div class="icon-box box-blue">
                    <i class="far fa-envelope"></i>
                </div>
            </div>

            <!-- Card 2: Spam -->
            <div class="stat-box s-red" onclick="window.location='{{ route('crm.emails.spam') }}'">
                <div class="stat-content">
                    <div class="stat-label">Spam </div>
                    <div class="stat-number" id="stat-spam">{{ number_format($spamEmails) }}</div>
                    @php $t = $trends['spam'];
                    $color = $t <= 0 ? 'trend-up' : 'trend-down'; /* Less spam is good (Green) */ @endphp
                    <div class="stat-trend {{ $color }}" id="trend-spam-container">
                        <span id="trend-spam">{{ $t > 0 ? '+' : '' }}{{ $t }}%</span>
                        <span class="trend-text">vs last period</span>
                    </div>
                </div>
                <div class="icon-box box-red">
                    <i class="fas fa-ban"></i>
                </div>
            </div>

            <!-- Card 3: Responded -->
            <div class="stat-box s-purple" onclick="window.location='{{ route('crm.emails.index', ['status' => 'Responded']) }}'">
                <div class="stat-content">
                    <div class="stat-label">Replied </div>
                    <div class="stat-number" id="stat-replied">{{ number_format($repliedEmails) }}</div>
                    @php $t = $trends['replied'];
                    $color = $t >= 0 ? 'trend-up' : 'trend-down'; @endphp
                    <div class="stat-trend {{ $color }}" id="trend-replied-container">
                        <span id="trend-replied">{{ $t > 0 ? '+' : '' }}{{ $t }}%</span>
                        <span class="trend-text">vs last period</span>
                    </div>
                </div>
                <div class="icon-box box-purple">
                    <i class="far fa-comments"></i>
                </div>
            </div>

            <!-- Card 4: Orders -->
            <div class="stat-box s-green" onclick="window.location='{{ route('crm.orders.index') }}'">
                <div class="stat-content">
                    <div class="stat-label">Order Completed</div>
                    <div class="stat-number" id="stat-orders">{{ number_format($ordersDone) }}</div>
                    @php $t = $trends['orders'];
                    $color = $t >= 0 ? 'trend-up' : 'trend-down'; @endphp
                    <div class="stat-trend {{ $color }}" id="trend-orders-container">
                        <span id="trend-orders">{{ $t > 0 ? '+' : '' }}{{ $t }}%</span>
                        <span class="trend-text">vs last period</span>
                    </div>
                </div>
                <div class="icon-box box-green">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
        </div>
        @endif

        <!-- MIDDLE ROW: BAR CHART + LOCATIONS -->
        <div class="middle-grid">
            <!-- Chart 1: Bar -->
            <div class="chart-card">
                <div class="chart-header">
                    <div class="chart-title">
                        <h3>{{ ($__dashUser && ($__dashUser->isDesigner() || $__dashUser->isPrepress() || $__dashUser->isProductionManager() || $__dashUser->isQC() || $__dashUser->isPressOperator())) ? (($__dashUser->isPrepress()) ? 'Prepress Overview' : ($__dashUser->isProductionManager() ? 'Production Overview' : ($__dashUser->isQC() ? 'QC Overview' : ($__dashUser->isPressOperator() ? 'Press Machine Overview' : 'Design Overview')))) : 'Response Overview' }}</h3>
                        <p>{{ ($__dashUser && $__dashUser->isQC()) ? 'Passed vs Pending' : (($__dashUser && ($__dashUser->isDesigner() || $__dashUser->isPrepress() || $__dashUser->isProductionManager() || $__dashUser->isPressOperator())) ? 'Completed vs Pending' : 'Responded vs Pending') }}</p>
                    </div>
                    <div class="year-drop">2026 <i class="far fa-calendar"></i></div>
                </div>
                <div style="flex:1; position:relative; min-height: 0;">
                    <canvas id="topBarChart"></canvas>
                </div>
            </div>

            <!-- NEW: Locations Widget -->
            @if(!($__dashUser && ($__dashUser->isDesigner() || $__dashUser->isPrepress() || $__dashUser->isProductionManager() || $__dashUser->isQC() || $__dashUser->isPressOperator())))
            <div class="chart-card">
                <div class="chart-header" style="margin-bottom: 0.5rem;">
                    <div class="chart-title">
                        <h3>Inquiries by Location</h3>
                        <p>Traffic from top regions</p>
                    </div>
                    <div style="cursor: pointer; color: #94a3b8;"><i class="fas fa-ellipsis-h"></i></div>
                </div>

                <div
                    style="display: flex; flex-direction: column; align-items: center; justify-content: center; margin-top: 2rem; gap: 2rem; flex: 1;">
                    <!-- Interactive Vector World Map -->
                    <div style="width: 100%; height: 180px; position: relative;">
                        <div id="world-map" style="width: 100%; height: 100%;"></div>
                    </div>

                    <!-- List below Globe -->
                    <div class="location-list" style="width: 100%;">
                        @php $locColors = ['#3b82f6', '#ef4444', '#10b981']; @endphp
                        @forelse($locationData as $index => $loc)
                            @php
                                $cName = $loc['name'] ?: 'Unknown';
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
                            <div class="loc-item">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="width: 24px; display: flex; justify-content: center; align-items: center;">
                                        @if($cCode)
                                            <img src="https://flagcdn.com/w40/{{ $cCode }}.png"
                                                srcset="https://flagcdn.com/w80/{{ $cCode }}.png 2x" alt="{{ $cName }}"
                                                style="width: 20px; height: auto; border-radius: 2px; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                        @else
                                            <i class="fas fa-globe" style="color: #64748b; font-size: 1rem;"></i>
                                        @endif
                                    </div>
                                    <span>{{ $cIso }}</span>
                                </div>
                                <div class="loc-bar-bg">
                                    <div class="loc-bar-fill"
                                        style="width: {{ $loc['percent'] }}%; background: {{ $locColors[$index] ?? '#cbd5e1' }};">
                                    </div>
                                </div>
                                <span style="text-align: right;">{{ $loc['percent'] }}%</span>
                            </div>
                        @empty
                            <div style="text-align: center; color: #94a3b8; font-size: 0.8rem;">No data available</div>
                        @endforelse
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- BOTTOM ROW: WAVE CHART -->
        <div class="bottom-chart-container">
            <div class="chart-header">
                <div class="chart-title">
                    <h3>Trend Analysis</h3>
                    <p>Monthly Performance</p>
                </div>
                <div
                    style="background:#eff6ff; padding:6px 12px; border-radius:6px; color:#10b981; font-weight:700; font-size:0.8rem;">
                    +12.5% Growth</div>
            </div>
            <div style="flex: 1; position: relative; min-height: 0;">
                <canvas id="waveChart"></canvas>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script>
        Chart.defaults.font.family = "'DM Sans', sans-serif";
        Chart.register(ChartDataLabels); // Register the plugin globally

        // 1. TOP BAR CHART (Purple/Orange)
        const ctxBar = document.getElementById('topBarChart').getContext('2d');
        window.topBarChart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: {!! json_encode($chartTrends['labels']) !!},
                datasets: [
                    {
                        label: '{{ ($__dashUser && ($__dashUser->isDesigner() || $__dashUser->isPrepress() || $__dashUser->isProductionManager() || $__dashUser->isQC() || $__dashUser->isPressOperator())) ? (($__dashUser->isQC()) ? "Passed" : "Completed") : "Replied" }}',
                        data: {!! json_encode($chartTrends['replied']) !!},
                        backgroundColor: '#a855f7', // Purple
                        borderRadius: 6,
                        barThickness: 16
                    },
                    {
                        label: 'Pending',
                        data: {!! json_encode($chartTrends['pending']) !!},
                        backgroundColor: '#fed7aa', /* lighter orange */
                        borderRadius: 6,
                        barThickness: 16
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 20, color: '#64748b' } },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        color: '#1e293b',
                        font: { weight: 'bold', size: 10 },
                        offset: -2,
                        display: function (context) {
                            return context.dataset.data[context.dataIndex] > 0;
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f8fafc' },
                        border: { display: false },
                        ticks: { color: '#94a3b8' }
                    },
                    x: { grid: { display: false }, border: { display: false }, ticks: { color: '#64748b' } }
                },
                layout: { padding: { top: 20 } },
                interaction: {
                    intersect: false,
                    mode: 'index',
                }
            }
        });

        // 2. BOTTOM WAVE CHART (Smooth Curves)
        const ctxWave = document.getElementById('waveChart').getContext('2d');
        window.waveChart = new Chart(ctxWave, {
            type: 'line',
            data: {
                labels: {!! json_encode($chartTrends['labels']) !!},
                datasets: [
                    {
                        label: 'Total',
                        data: {!! json_encode($chartTrends['total'] ?? []) !!},
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.05)',
                        fill: 'start',
                        tension: 0.4,
                        pointRadius: 0,
                        borderWidth: 3
                    },
                    {
                        label: '{{ ($__dashUser && ($__dashUser->isDesigner() || $__dashUser->isPrepress() || $__dashUser->isProductionManager() || $__dashUser->isQC() || $__dashUser->isPressOperator())) ? (($__dashUser->isQC()) ? "Passed" : "Completed") : "Replied" }}',
                        data: {!! json_encode($chartTrends['replied']) !!},
                        borderColor: '#a855f7',
                        tension: 0.4,
                        pointRadius: 0,
                        borderWidth: 3
                    },
                    {
                        label: '{{ ($__dashUser && $__dashUser->isQC()) ? "Pending QC" : "Orders" }}',
                        data: {!! ($__dashUser && $__dashUser->isQC()) ? json_encode($chartTrends['pending'] ?? []) : json_encode($chartTrends['orders'] ?? []) !!},
                        borderColor: '#10b981',
                        tension: 0.4,
                        pointRadius: 0,
                        borderWidth: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    datalabels: { display: false }
                },
                scales: {
                    y: { display: true, beginAtZero: true, grid: { color: '#f8fafc' }, border: { display: false }, ticks: { color: '#94a3b8' } },
                    x: { grid: { display: false }, border: { display: false }, ticks: { color: '#64748b' } }
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
            }
        });

        // 4. HANDLE TIME FILTER CLICKS
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const range = this.innerText.toLowerCase();

                // Visual loading state — regular cards
                ['stat-total', 'stat-spam', 'stat-replied', 'stat-orders'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) { el.style.opacity = '0.4'; el.innerText = '...'; }
                });

                // Visual loading state — estimator cards
                ['stat-est-total', 'stat-est-estimated', 'stat-est-pending'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) { el.style.opacity = '0.4'; el.innerText = '...'; }
                });

                // Visual loading state — designer cards
                ['stat-des-total', 'stat-des-completed', 'stat-des-pending'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) { el.style.opacity = '0.4'; el.innerText = '...'; }
                });

                // Visual loading state — prepress cards
                ['stat-prep-total', 'stat-prep-completed', 'stat-prep-pending'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) { el.style.opacity = '0.4'; el.innerText = '...'; }
                });

                // Visual loading state — production cards
                ['stat-prod-queue', 'stat-prod-press', 'stat-prod-qc', 'stat-prod-completed'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) { el.style.opacity = '0.4'; el.innerText = '...'; }
                });

                // Visual loading state — QC cards
                ['stat-qc-assigned', 'stat-qc-first', 'stat-qc-final', 'stat-qc-passed'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) { el.style.opacity = '0.4'; el.innerText = '...'; }
                });

                // Visual loading state — supervisor cards
                ['stat-sup-assigned', 'stat-sup-review', 'stat-sup-active', 'stat-sup-completed'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) { el.style.opacity = '0.4'; el.innerText = '...'; }
                });

                // Visual loading state — press operator cards
                ['stat-po-assigned', 'stat-po-setup', 'stat-po-running', 'stat-po-completed'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) { el.style.opacity = '0.4'; el.innerText = '...'; }
                });

                fetchData(range);
            });
        });

        function fetchData(range) {
            fetch(`{{ route('crm.dashboard') }}?range=${range}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(response => response.json())
                .then(data => {
                    // Update Stats Numbers
                    const updateStat = (id, value) => {
                        const el = document.getElementById(id);
                        if (el) {
                            el.innerText = value;
                            el.style.opacity = '1';
                        }
                    };

                    const updateTrend = (id, value, invert = false) => {
                        const container = document.getElementById(id + '-container');
                        const text = document.getElementById(id);

                        if (!container || !text) return;

                        // Format Value
                        const val = parseFloat(value);
                        text.innerText = (val > 0 ? '+' : '') + val + '%';

                        let isGood = val >= 0;
                        if (invert) {
                            isGood = val <= 0;
                        }

                        container.className = 'stat-trend ' + (isGood ? 'trend-up' : 'trend-down');
                    };

                    updateStat('stat-total', data.stats.total);
                    updateStat('stat-spam', data.stats.spam);
                    updateStat('stat-replied', data.stats.replied);
                    updateStat('stat-orders', data.stats.orders);

                    // Update Trends
                    if (data.trends) {
                        updateTrend('trend-total', data.trends.total);
                        updateTrend('trend-spam', data.trends.spam, true); // Invert logic for spam
                        updateTrend('trend-replied', data.trends.replied);
                        updateTrend('trend-orders', data.trends.orders);
                    }

                    // ── Update Estimator Stats ──────────────────────
                    if (data.estimator) {
                        updateStat('stat-est-total', data.estimator.total);
                        updateStat('stat-est-estimated', data.estimator.estimated);
                        updateStat('stat-est-pending', data.estimator.pending);

                        // Update pending trend color
                        const pendingTrend = document.getElementById('est-pending-trend');
                        if (pendingTrend) {
                            pendingTrend.className = 'stat-trend ' + (data.estimator.pending > 0 ? 'trend-down' : 'trend-up');
                        }
                        // Update total label text
                        const totalLabel = document.getElementById('est-total-label');
                        if (totalLabel) {
                            const rangeText = { today: 'Today', weekly: 'This week', monthly: 'This month', yearly: 'This year' };
                            totalLabel.innerText = rangeText[range] || 'Selected period';
                        }
                    }
                    // ───────────────────────────────────────────────

                    // ── Update Designer Stats ──────────────────────
                    if (data.designer) {
                        updateStat('stat-des-total', data.designer.total);
                        updateStat('stat-des-completed', data.designer.completed);
                        updateStat('stat-des-pending', data.designer.pending);

                        // Update pending trend color
                        const desPendingTrend = document.getElementById('des-pending-trend');
                        if (desPendingTrend) {
                            desPendingTrend.className = 'stat-trend ' + (data.designer.pending > 0 ? 'trend-down' : 'trend-up');
                        }
                        // Update total label text
                        const desTotalLabel = document.getElementById('des-total-label');
                        if (desTotalLabel) {
                            const rangeText = { today: 'Today', weekly: 'This week', monthly: 'This month', yearly: 'This year' };
                            desTotalLabel.innerText = rangeText[range] || 'Selected period';
                        }
                    }
                    // ───────────────────────────────────────────────

                    // ── Update Prepress Stats ──────────────────────
                    if (data.prepress) {
                        updateStat('stat-prep-total', data.prepress.total);
                        updateStat('stat-prep-completed', data.prepress.completed);
                        updateStat('stat-prep-pending', data.prepress.pending);

                        // Update pending trend color
                        const prepPendingTrend = document.getElementById('prep-pending-trend');
                        if (prepPendingTrend) {
                            prepPendingTrend.className = 'stat-trend ' + (data.prepress.pending > 0 ? 'trend-down' : 'trend-up');
                        }
                        // Update total label text
                        const prepTotalLabel = document.getElementById('prep-total-label');
                        if (prepTotalLabel) {
                            const rangeText = { today: 'Today', weekly: 'This week', monthly: 'This month', yearly: 'This year' };
                            prepTotalLabel.innerText = rangeText[range] || 'Selected period';
                        }
                    }
                    // ───────────────────────────────────────────────

                    // ── Update Production Stats ──────────────────────
                    if (data.production) {
                        updateStat('stat-prod-queue', data.production.in_queue);
                        updateStat('stat-prod-press', data.production.in_press);
                        updateStat('stat-prod-qc', data.production.pending_qc);
                        updateStat('stat-prod-completed', data.production.completed);

                        // Update pending QC trend color
                        const prodQcTrend = document.getElementById('prod-qc-trend');
                        if (prodQcTrend) {
                            prodQcTrend.className = 'stat-trend ' + (data.production.pending_qc > 0 ? 'trend-down' : 'trend-up');
                        }
                        // Update queue label text
                        const prodQueueLabel = document.getElementById('prod-queue-label');
                        if (prodQueueLabel) {
                            const rangeText = { today: 'Today', weekly: 'This week', monthly: 'This month', yearly: 'This year' };
                            prodQueueLabel.innerText = rangeText[range] || 'Pending planning';
                        }
                    }
                    // ───────────────────────────────────────────────

                    // ── Update QC Stats ──────────────────────
                    if (data.qc) {
                        updateStat('stat-qc-assigned', data.qc.assigned);
                        updateStat('stat-qc-first', data.qc.first_sheet);
                        updateStat('stat-qc-final', data.qc.final_qc);
                        updateStat('stat-qc-passed', data.qc.passed);

                        const qcFirstTrend = document.getElementById('qc-first-trend');
                        if (qcFirstTrend) {
                            qcFirstTrend.className = 'stat-trend ' + (data.qc.first_sheet > 0 ? 'trend-down' : 'trend-up');
                        }
                        const qcFinalTrend = document.getElementById('qc-final-trend');
                        if (qcFinalTrend) {
                            qcFinalTrend.className = 'stat-trend ' + (data.qc.final_qc > 0 ? 'trend-down' : 'trend-up');
                        }
                        const qcAssignedLabel = document.getElementById('qc-assigned-label');
                        if (qcAssignedLabel) {
                            const rangeText = { today: 'Today', weekly: 'This week', monthly: 'This month', yearly: 'This year' };
                            qcAssignedLabel.innerText = rangeText[range] || 'Selected period';
                        }
                    }
                    // ───────────────────────────────────────────────

                    // ── Update Supervisor Stats ──────────────────────
                    if (data.supervisor) {
                        updateStat('stat-sup-assigned', data.supervisor.assigned);
                        updateStat('stat-sup-review', data.supervisor.needs_review);
                        updateStat('stat-sup-active', data.supervisor.active);
                        updateStat('stat-sup-completed', data.supervisor.completed);

                        const supReviewTrend = document.getElementById('sup-review-trend');
                        if (supReviewTrend) {
                            supReviewTrend.className = 'stat-trend ' + (data.supervisor.needs_review > 0 ? 'trend-down' : 'trend-up');
                        }
                        const supAssignedLabel = document.getElementById('sup-assigned-label');
                        if (supAssignedLabel) {
                            const rangeText = { today: 'Today', weekly: 'This week', monthly: 'This month', yearly: 'This year' };
                            supAssignedLabel.innerText = rangeText[range] || 'Selected period';
                        }
                    }
                    // ───────────────────────────────────────────────

                    // ── Update Press Stats ──────────────────────
                    if (data.press) {
                        updateStat('stat-po-assigned', data.press.assigned);
                        updateStat('stat-po-setup', data.press.in_setup);
                        updateStat('stat-po-running', data.press.running);
                        updateStat('stat-po-completed', data.press.completed);

                        // Update setup trend color
                        const poSetupTrend = document.getElementById('po-setup-trend');
                        if (poSetupTrend) {
                            poSetupTrend.className = 'stat-trend ' + (data.press.in_setup > 0 ? 'trend-down' : 'trend-up');
                        }
                        // Update assigned label text
                        const poAssignedLabel = document.getElementById('po-assigned-label');
                        if (poAssignedLabel) {
                            const rangeText = { today: 'Today', weekly: 'This week', monthly: 'This month', yearly: 'This year' };
                            poAssignedLabel.innerText = rangeText[range] || 'Selected period';
                        }
                    }
                    // ───────────────────────────────────────────────

                    // ── Update Accounts Stats ──────────────────────
                    if (data.accounts) {
                        updateStat('stat-acc-check', data.accounts.pending_check);
                        updateStat('stat-acc-payment', data.accounts.pending_payment);
                        updateStat('stat-acc-invoice', data.accounts.invoiced);
                        updateStat('stat-acc-completed', data.accounts.completed);

                        const accCheckTrend = document.getElementById('acc-check-trend');
                        if (accCheckTrend) {
                            accCheckTrend.className = 'stat-trend ' + (data.accounts.pending_check > 0 ? 'trend-down' : 'trend-up');
                        }
                        const accPaymentTrend = document.getElementById('acc-payment-trend');
                        if (accPaymentTrend) {
                            accPaymentTrend.className = 'stat-trend ' + (data.accounts.pending_payment > 0 ? 'trend-down' : 'trend-up');
                        }
                        const accInvoiceTrend = document.getElementById('acc-invoice-trend');
                        if (accInvoiceTrend) {
                            accInvoiceTrend.className = 'stat-trend ' + (data.accounts.invoiced > 0 ? 'trend-down' : 'trend-up');
                        }
                    }
                    // ───────────────────────────────────────────────

                    // ── Update Shipping Stats ──────────────────────
                    if (data.shipping) {
                        updateStat('stat-ship-pending', data.shipping.pending);
                        updateStat('stat-ship-label', data.shipping.label_generated);
                        updateStat('stat-ship-transit', data.shipping.in_transit);
                        updateStat('stat-ship-delivered', data.shipping.delivered);

                        const shipPendingTrend = document.getElementById('ship-pending-trend');
                        if (shipPendingTrend) {
                            shipPendingTrend.className = 'stat-trend ' + (data.shipping.pending > 0 ? 'trend-down' : 'trend-up');
                        }
                        const shipLabelTrend = document.getElementById('ship-label-trend');
                        if (shipLabelTrend) {
                            shipLabelTrend.className = 'stat-trend ' + (data.shipping.label_generated > 0 ? 'trend-down' : 'trend-up');
                        }
                    }
                    // ───────────────────────────────────────────────

                    // Update Main Charts
                    if (window.topBarChart && data.chart) {
                        window.topBarChart.data.labels = data.chart.labels;
                        window.topBarChart.data.datasets[0].data = data.chart.replied;
                        window.topBarChart.data.datasets[1].data = data.chart.pending;
                        window.topBarChart.update('none'); // Update without full animation
                    }

                    if (window.waveChart && data.chart) {
                        window.waveChart.data.labels = data.chart.labels;
                        window.waveChart.data.datasets[0].data = data.chart.total;
                        window.waveChart.data.datasets[1].data = data.chart.replied;
                        window.waveChart.data.datasets[2].data = {{ ($__dashUser && $__dashUser->isQC()) ? 'data.chart.pending' : 'data.chart.orders' }};
                        window.waveChart.update('none');
                    }
                })
                .catch(err => console.error('Error fetching data:', err));
        }

        // Auto Refresh Dashboard every 30 seconds
        setInterval(() => {
            const activeBtn = document.querySelector('.filter-btn.active');
            const range = activeBtn ? activeBtn.innerText.toLowerCase() : 'today';
            fetchData(range);
        }, 30000);
    </script>

    <!-- jsvectormap CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/css/jsvectormap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/js/jsvectormap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/maps/world.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (document.getElementById('world-map')) {
                new jsVectorMap({
                    selector: '#world-map',
                    map: 'world',
                    backgroundColor: 'transparent',
                    zoomButtons: false,
                    zoomOnScroll: false,
                    regionStyle: {
                        initial: {
                            fill: '#e8edf5',
                            stroke: '#ffffff',
                            strokeWidth: 0.5,
                        },
                        hover: {
                            fill: 'var(--primary-purple)',
                            cursor: 'pointer',
                        },
                        selected: {
                            fill: 'var(--primary-purple)',
                        }
                    },
                    markers: [
                        { name: 'Pakistan', coords: [30.3753, 69.3451] },
                        { name: 'United States', coords: [37.0902, -95.7129] },
                        { name: 'United Kingdom', coords: [55.3781, -3.4360] },
                    ],
                    markerStyle: {
                        initial: {
                            fill: 'var(--primary-purple)',
                            stroke: '#ffffff',
                            strokeWidth: 2,
                            r: 5,
                        },
                        hover: {
                            fill: 'var(--primary-purple)',
                            stroke: '#c7d2fe',
                            strokeWidth: 3,
                            cursor: 'pointer',
                        }
                    },
                    selectedRegions: ['PK', 'US', 'GB'],
                    regionStyle: {
                        initial: { fill: '#dde3f0', stroke: '#fff', strokeWidth: 0.5 },
                        selected: { fill: 'var(--primary-purple)' },
                        hover: { fill: 'var(--primary-purple)', cursor: 'pointer' },
                    },
                    onRegionTooltipShow(event, tooltip, code) {
                        tooltip.text(tooltip.text(), true);
                    }
                });
            }
        });
    </script>
@endsection
