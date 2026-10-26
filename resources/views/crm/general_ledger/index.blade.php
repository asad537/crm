@extends('crm.layout')
@section('title', 'General Ledger')
@section('content')
@php
    $isPayable = $tab === 'payable';
    $isAll = $tab === 'all';
    $fmt = function ($value) { return number_format((float) $value, 2); };
    if ($isPayable) {
        $paidRatio = $payableTotals['total'] > 0 ? min(100, $payableTotals['paid'] / $payableTotals['total'] * 100) : 0;
    } else {
        $paidRatio = $receivableTotals['total'] > 0 ? min(100, $receivableTotals['received'] / $receivableTotals['total'] * 100) : 0;
    }
    $netPosition = $receivableTotals['pending'] - $payableTotals['balance'];
    $statusOptions = $isAll
        ? ['all' => 'All Types', 'payable' => 'Payable Only', 'receivable' => 'Receivable Only']
        : ($isPayable
            ? ['all' => 'All Statuses', 'paid' => 'Paid', 'partial' => 'Partial', 'unpaid' => 'Unpaid']
            : ['all' => 'All Statuses', 'received' => 'Received', 'partial' => 'Partial', 'pending' => 'Outstanding']);
    $exportParams = array_filter([
        'tab' => $tab, 'search' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo,
        'status' => $status !== 'all' ? $status : null,
        'party' => $party ?: null,
    ]);
@endphp

@include('crm.general_ledger._styles')

<div class="gl-page">
    <div class="gl-head">
        <div class="gl-sub">
            @if($isAll)<strong>All Entries</strong> — payable and receivable together, newest first
            @elseif($isPayable)<strong>Accounts Payable</strong> — every vendor purchase, what's paid and what's still owed
            @else<strong>Accounts Receivable</strong> — customer sales, orders and rent with received amounts and outstanding balances @endif
        </div>
        <div class="gl-tabs">
            <a class="gl-tab {{ $isAll ? 'active' : '' }}" href="{{ route('crm.general_ledger.index', ['tab' => 'all']) }}"><i class="fas fa-book"></i> All Entries</a>
            <a class="gl-tab {{ $isPayable ? 'active' : '' }}" href="{{ route('crm.general_ledger.index', ['tab' => 'payable']) }}"><i class="fas fa-truck-loading"></i> Accounts Payable</a>
            <a class="gl-tab {{ !$isAll && !$isPayable ? 'active' : '' }}" href="{{ route('crm.general_ledger.index', ['tab' => 'receivable']) }}"><i class="fas fa-hand-holding-usd"></i> Accounts Receivable</a>
        </div>
    </div>

    @if(session('error'))<div style="background:#fef2f2;border:1px solid #fecaca;color:#7f1d1d;font-size:.86rem;padding:.85rem 1.1rem;border-radius:10px;margin-bottom:1rem">{{ session('error') }}</div>@endif

    <div class="gl-stats">
        @php $__glBase = request()->except(['tab','page']); @endphp
        @if($isAll)
            <a href="{{ route('crm.general_ledger.index', array_merge($__glBase, ['tab'=>'receivable'])) }}" class="gl-stat gl-stat-link" title="View all receivables">
                <div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-hand-holding-usd"></i></span><span class="gl-k">Receivable Pending</span><i class="fas fa-arrow-right gl-stat-go"></i></div>
                <div class="gl-v">{{ $fmt($receivableTotals['pending']) }} <small>AED</small></div>
                <div class="gl-note">{{ $receivableTotals['count'] }} customer order(s)</div>
            </a>
            <a href="{{ route('crm.general_ledger.index', array_merge($__glBase, ['tab'=>'payable'])) }}" class="gl-stat red gl-stat-link" title="View all payables">
                <div class="gl-stat-top"><span class="gl-ico red"><i class="fas fa-truck-loading"></i></span><span class="gl-k">Payable Balance</span><i class="fas fa-arrow-right gl-stat-go"></i></div>
                <div class="gl-v">{{ $fmt($payableTotals['balance']) }} <small>AED</small></div>
                <div class="gl-note">{{ $payableTotals['count'] }} vendor purchase(s)</div>
            </a>
            <div class="gl-stat {{ $netPosition >= 0 ? 'green' : 'red' }}">
                <div class="gl-stat-top"><span class="gl-ico {{ $netPosition >= 0 ? 'green' : 'red' }}"><i class="fas fa-balance-scale"></i></span><span class="gl-k">Overall Balance {{ $netPosition >= 0 ? '(In your favour)' : '(You owe)' }}</span></div>
                <div class="gl-v">{{ $netPosition < 0 ? '-' : '+' }}{{ $fmt(abs($netPosition)) }} <small>AED</small></div>
                <div class="gl-note">Money clients still owe you ({{ $fmt($receivableTotals['pending']) }}) minus money you owe vendors ({{ $fmt($payableTotals['balance']) }}).</div>
            </div>
        @elseif($isPayable)
            <a href="{{ route('crm.general_ledger.index', array_merge($__glBase, ['tab'=>'payable','status'=>'all'])) }}" class="gl-stat gl-stat-link" title="Show all purchases">
                <div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-file-invoice"></i></span><span class="gl-k">Total Purchases</span><i class="fas fa-arrow-right gl-stat-go"></i></div>
                <div class="gl-v">{{ $fmt($payableTotals['total']) }} <small>AED</small></div>
                <div class="gl-bar"><span style="width:{{ round($paidRatio) }}%"></span></div>
                <div class="gl-note">{{ $payableTotals['count'] }} purchase(s) · {{ round($paidRatio) }}% settled</div>
            </a>
            <a href="{{ route('crm.general_ledger.index', array_merge($__glBase, ['tab'=>'payable','status'=>'paid'])) }}" class="gl-stat green gl-stat-link" title="Show fully-paid purchases">
                <div class="gl-stat-top"><span class="gl-ico green"><i class="fas fa-check-circle"></i></span><span class="gl-k">Paid to Vendors</span><i class="fas fa-arrow-right gl-stat-go"></i></div>
                <div class="gl-v">{{ $fmt($payableTotals['paid']) }} <small>AED</small></div>
            </a>
            <a href="{{ route('crm.general_ledger.index', array_merge($__glBase, ['tab'=>'payable','status'=>'unpaid'])) }}" class="gl-stat red gl-stat-link" title="Show unpaid purchases">
                <div class="gl-stat-top"><span class="gl-ico red"><i class="fas fa-exclamation-circle"></i></span><span class="gl-k">Balance Payable</span><i class="fas fa-arrow-right gl-stat-go"></i></div>
                <div class="gl-v">{{ $fmt($payableTotals['balance']) }} <small>AED</small></div>
            </a>
        @else
            <a href="{{ route('crm.general_ledger.index', array_merge($__glBase, ['tab'=>'receivable','status'=>'all'])) }}" class="gl-stat gl-stat-link" title="Show all receivables">
                <div class="gl-stat-top"><span class="gl-ico brand"><i class="fas fa-file-invoice-dollar"></i></span><span class="gl-k">Total Receivable</span><i class="fas fa-arrow-right gl-stat-go"></i></div>
                <div class="gl-v">{{ $fmt($receivableTotals['total']) }} <small>AED</small></div>
                <div class="gl-bar"><span style="width:{{ round($paidRatio) }}%"></span></div>
                <div class="gl-note">{{ $receivableTotals['count'] }} receivable entries · {{ round($paidRatio) }}% collected</div>
            </a>
            <a href="{{ route('crm.general_ledger.index', array_merge($__glBase, ['tab'=>'receivable','status'=>'received'])) }}" class="gl-stat green gl-stat-link" title="Show received payments">
                <div class="gl-stat-top"><span class="gl-ico green"><i class="fas fa-check-circle"></i></span><span class="gl-k">Received</span><i class="fas fa-arrow-right gl-stat-go"></i></div>
                <div class="gl-v">{{ $fmt($receivableTotals['received']) }} <small>AED</small></div>
            </a>
            <a href="{{ route('crm.general_ledger.index', array_merge($__glBase, ['tab'=>'receivable','status'=>'pending'])) }}" class="gl-stat red gl-stat-link" title="Show pending receivables">
                <div class="gl-stat-top"><span class="gl-ico red"><i class="fas fa-hourglass-half"></i></span><span class="gl-k">Pending</span><i class="fas fa-arrow-right gl-stat-go"></i></div>
                <div class="gl-v">{{ $fmt($receivableTotals['pending']) }} <small>AED</small></div>
            </a>
        @endif
    </div>

    <div class="gl-filterbar">
        <form class="gl-filters" method="GET" action="{{ route('crm.general_ledger.index') }}" id="glFilterForm">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="gl-field"><label>From</label><input type="date" name="date_from" value="{{ $dateFrom }}" onchange="document.getElementById('glFilterForm').submit()"></div>
            <div class="gl-field"><label>To</label><input type="date" name="date_to" value="{{ $dateTo }}" onchange="document.getElementById('glFilterForm').submit()"></div>
            <div class="gl-field"><label>{{ $isAll ? 'Type' : 'Status' }}</label>
                <select name="status" onchange="document.getElementById('glFilterForm').submit()">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" {{ $status === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if($partyOptions->count())
            <div class="gl-field"><label>{{ $partyLabel }}</label>
                <select name="party" onchange="document.getElementById('glFilterForm').submit()">
                    <option value="">All {{ $partyLabel }}s</option>
                    @foreach($partyOptions as $name)
                        <option value="{{ $name }}" {{ $party === $name ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="gl-field"><label>Search</label><input name="search" id="glSearchInput" value="{{ $search }}" placeholder="Type to search live..." autocomplete="off"></div>
            @if($search || $dateFrom || $dateTo || $status !== 'all' || $party)
                <a class="gl-reset" href="{{ route('crm.general_ledger.index', ['tab' => $tab]) }}">Reset</a>
            @endif
        </form>
        @php $__exportHint = $party ? 'Every entry for '.$party : 'One line per '.strtolower($partyLabel).' with total, paid/received and balance (pick one to export its entries)'; @endphp
        <div class="gl-exports">
            <a class="gl-export" title="{{ $__exportHint }}" href="{{ route('crm.general_ledger.export', array_merge($exportParams, ['format' => 'excel'])) }}"><i class="fas fa-file-excel"></i> Excel</a>
            <a class="gl-export" title="{{ $__exportHint }}" href="{{ route('crm.general_ledger.export', array_merge($exportParams, ['format' => 'pdf'])) }}"><i class="fas fa-file-pdf"></i> PDF</a>
        </div>
    </div>

    <div class="gl-count">Showing <b>{{ $entries->total() }}</b> entr{{ $entries->total() === 1 ? 'y' : 'ies' }}{{ $search !== '' ? ' for "'.$search.'"' : '' }}{{ $dateFrom || $dateTo ? ' · '.($dateFrom ?: 'start').' → '.($dateTo ?: 'today') : '' }}</div>

    <div class="gl-card">
        <div class="gl-tablewrap">
            <table class="gl-table">
                <thead><tr>
                    <th style="width:96px">Date</th>
                    @if($isAll)<th style="width:118px">Type</th>@endif
                    <th style="min-width:200px">{{ $isPayable ? 'Vendor' : ($isAll ? 'Party' : 'Client') }}</th>
                    <th style="width:110px">Ref #</th><th style="min-width:130px">Detail</th>
                    @if(!$isAll && !$isPayable)<th>Payment Term</th>@endif
                    <th class="gl-r" style="width:130px">Total</th><th class="gl-r" style="width:110px">Paid</th><th class="gl-r" style="width:110px">Balance</th><th style="width:120px">Status</th>
                </tr></thead>
                <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td class="gl-date" data-label="Date">{{ \Carbon\Carbon::parse($entry->date)->format('d M Y') }}</td>
                        @if($isAll)
                            <td data-label="Type"><span class="gl-badge {{ $entry->type === 'payable' ? 't-pay' : 't-rec' }}">{{ ucfirst($entry->type) }}</span></td>
                        @endif
                        <td data-label="{{ $isPayable ? 'Vendor' : ($isAll ? 'Party' : 'Client') }}"><div class="gl-party"><span class="gl-avatar">{{ strtoupper(substr($entry->party ?: '?', 0, 1)) }}</span><span class="gl-name">{{ $entry->party }}</span></div></td>
                        <td class="gl-mutedtext" data-label="Ref #">{{ $entry->ref }}</td>
                        <td data-label="Detail">{{ $entry->detail }}</td>
                        @if(!$isAll && !$isPayable)<td class="gl-mutedtext" data-label="Payment Term">{{ $entry->term ?: '—' }}</td>@endif
                        <td class="gl-r gl-num" data-label="Total">{{ $fmt($entry->total) }} <small>{{ $entry->currency }}</small></td>
                        <td class="gl-r gl-num gl-green" data-label="Paid">{{ $fmt($entry->paid) }}</td>
                        <td class="gl-r gl-num {{ $entry->balance > 0 ? 'gl-red' : '' }}" data-label="Balance">{{ $fmt($entry->balance) }}</td>
                        <td data-label="Status"><span class="gl-badge {{ $entry->status_tone }}">{{ $entry->status_label }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $isAll ? 10 : 9 }}"><div class="gl-empty"><i class="fas fa-book"></i>No entries match the selected filters.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())
            <div class="gl-pagination">{{ $entries->links() }}</div>
        @endif
    </div>
</div>

<script>
    // Live search: auto-submit the filter form a short moment after the user stops
    // typing (debounced), so results update without clicking Search. Works correctly
    // with server-side pagination (unlike client-only row hiding).
    (function () {
        var input = document.getElementById('glSearchInput');
        var form = document.getElementById('glFilterForm');
        if (!input || !form) return;
        var timer = null;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(function () { form.submit(); }, 450);
        });
        // Enter still submits immediately (native form behaviour) — cancel the pending timer.
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { clearTimeout(timer); }
        });
        // Keep the cursor at the end of the search box (run now — DOMContentLoaded does
        // not re-fire on AJAX partial nav; this IIFE already runs after the DOM is ready).
        if (input.value) {
            input.focus();
            var v = input.value; input.value = ''; input.value = v;
        }
    })();
</script>
@endsection
