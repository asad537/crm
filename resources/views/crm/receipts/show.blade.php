@extends('crm.layout')
@section('title', 'Receipt ' . $receipt->number)
@section('content')
@php
    $fmt = function ($value) { return number_format((float) $value, 2); };
    $isImage = $receipt->proof_name && preg_match('/\.(jpe?g|png|webp)$/i', $receipt->proof_name);
    $words = $receipt->amountInWords();
    $openInvoices = collect();   // editing a receipt never needs the invoice list
@endphp

@include('crm.general_ledger._styles')
@include('crm.expenses._styles')
@include('crm.purchase_invoices._styles')
<style>
    .rv-amount { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
        background: var(--well); border: 1px solid var(--line); border-radius: 14px; padding: 1rem 1.2rem; margin-bottom: 1.2rem; }
    .rv-amount .k { font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--faint); }
    .rv-amount .big { font-size: 1.9rem; font-weight: 800; color: #15803d; font-variant-numeric: tabular-nums; line-height: 1.1; margin-top: .2rem; }
    .rv-amount .big small { font-size: .85rem; color: var(--muted); font-weight: 700; }
    .rv-amount .words { font-size: .8rem; font-weight: 600; color: var(--body); font-style: italic; max-width: 420px; text-align: right; }
    .rv-note { margin-top: 1.1rem; padding: .8rem 1rem; border-left: 3px solid #16a34a; background: var(--well); border-radius: 0 10px 10px 0; font-size: .86rem; color: var(--body); white-space: pre-line; }
    .rv-account a { color: var(--brand); text-decoration: none; }
    .rv-auto { display: block; font-size: .68rem; font-weight: 600; color: var(--faint); margin-top: .15rem; }
    .rv-sign { display: none; }
    @media print {
        .rv-sign { display: grid; grid-template-columns: repeat(2, 1fr); gap: 3rem; margin-top: 3.5rem; }
        .rv-sign div { border-top: 1px solid #94a3b8; padding-top: .4rem; font-size: .75rem; color: #475569; text-align: center; }
    }
</style>

<div class="gl-page">
    <div class="gl-head pi-noprint">
        <div class="gl-sub"><strong>{{ $receipt->number }}</strong> — {{ $receipt->category }} received{{ $receipt->payer_name ? ' from ' . $receipt->payer_name : '' }}</div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <a class="pi-btn" href="{{ route('crm.receipts.index') }}"><i class="fas fa-arrow-left"></i>&nbsp; All Receipts</a>
            @if(!$receipt->isOnAccount())<button type="button" class="pi-btn" data-receipt="{{ json_encode($receipt->formData()) }}" onclick="mrOpenEdit(JSON.parse(this.dataset.receipt))"><i class="fas fa-pen"></i>&nbsp; Edit</button>@endif
            <button type="button" class="pi-btn" onclick="window.print()"><i class="fas fa-print"></i>&nbsp; Print</button>
            @if($canDelete)
            <form method="POST" action="{{ route('crm.direct_receipts.destroy', $receipt->id) }}" onsubmit="return confirm('Delete this receipt? It will be removed from Receipts and its bank / cash account.');">
                {{ csrf_field() }} {{ method_field('DELETE') }}
                <button type="submit" class="pi-btn danger"><i class="fas fa-trash"></i>&nbsp; Delete</button>
            </form>
            @endif
        </div>
    </div>

    <div class="pi-card">
        <div class="pi-card-head">
            <i class="fas fa-hand-holding-usd" style="color:#16a34a"></i> Receipt Voucher {{ $receipt->number }}
            <span class="pi-status paid" style="margin-left:auto">Received</span>
        </div>
        <div class="pi-card-body">
            <div class="rv-amount">
                <div><div class="k">Amount Received</div><div class="big">{{ $fmt($receipt->amount) }} <small>{{ $receipt->currency }}</small></div></div>
                @if($words)<div class="words">{{ $words }}</div>@endif
            </div>
            <div class="pi-meta">
                <div><div class="k">Date</div><div class="v">{{ $receipt->receipt_date->format('d M Y') }}</div></div>
                <div><div class="k">Income Category</div><div class="v">{{ $receipt->category }}</div></div>
                <div><div class="k">Received From</div><div class="v">{{ $receipt->payer_name ?: '—' }} <span class="gl-badge {{ $receipt->payer_type === 'customer' ? 't-rec' : 'part' }}" style="margin-left:.3rem">{{ \App\CrmDirectReceipt::PAYER_TYPES[$receipt->payer_type] ?? 'Other' }}</span></div></div>
                <div><div class="k">Method</div><div class="v">{{ $receipt->method ?: '—' }}</div></div>
                <div class="rv-account"><div class="k">Bank / Cash Account</div><div class="v">
                    @if($account)<a href="{{ route('crm.bank_accounts.show', $account->id) }}">{{ $account->name }}</a>@if($autoBooked)<span class="rv-auto">auto, by payment method</span>@endif
                    @else<span class="gl-mutedtext">Not booked to an account</span>@endif
                </div></div>
                <div><div class="k">Reference</div><div class="v">{{ $receipt->reference ?: '—' }}</div></div>
                <div><div class="k">Recorded By</div><div class="v">{{ optional($receipt->creator)->name ?: '—' }}</div></div>
            </div>
            <div style="margin-top:1.1rem"><div class="pi-meta"><div><div class="k">Description</div><div class="v" style="font-weight:600">{{ $receipt->description }}</div></div></div></div>
            @if($receipt->note)<div class="rv-note">{{ $receipt->note }}</div>@endif
            <div class="rv-sign"><div>Received By</div><div>Paid By</div></div>
        </div>
    </div>

    @if($receipt->proof_path)
    <div class="pi-card">
        <div class="pi-card-head"><i class="fas fa-paperclip" style="color:var(--brand)"></i> Proof
            <a class="pi-btn pi-noprint" style="margin-left:auto" href="{{ route('crm.direct_receipts.proof', $receipt->id) }}" target="_blank"><i class="fas fa-external-link-alt"></i>&nbsp; Open</a>
        </div>
        <div class="pi-card-body">
            @if($isImage)<img class="pi-attach-img" src="{{ route('crm.direct_receipts.proof', $receipt->id) }}" alt="Receipt proof {{ $receipt->proof_name }}">
            @else<a href="{{ route('crm.direct_receipts.proof', $receipt->id) }}" target="_blank"><i class="fas fa-file-pdf"></i> {{ $receipt->proof_name }}</a>@endif
        </div>
    </div>
    @endif
</div>

@include('crm.receipts._make_receipt')
@endsection
