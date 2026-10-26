@extends('crm.layout')
@section('title', 'Payment ' . $expense->number)
@section('content')
@php
    $fmt = function ($value) { return number_format((float) $value, 2); };
    $isImage = $expense->proof_name && preg_match('/\.(jpe?g|png|webp)$/i', $expense->proof_name);
    $payeeTone = ['supplier' => 't-pay', 'customer' => 't-rec', 'other' => 'part'];
    $words = $expense->amountInWords();
@endphp

@include('crm.general_ledger._styles')
@include('crm.expenses._styles')
@include('crm.purchase_invoices._styles')
<style>
    .pv-amount { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; flex-wrap: wrap;
        background: var(--well); border: 1px solid var(--line); border-radius: 14px; padding: 1rem 1.2rem; margin-bottom: 1.2rem; }
    .pv-amount .k { font-size: .62rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--faint); }
    .pv-amount .big { font-size: 1.9rem; font-weight: 800; color: var(--red); font-variant-numeric: tabular-nums; line-height: 1.1; margin-top: .2rem; }
    .pv-amount .big small { font-size: .85rem; color: var(--muted); font-weight: 700; }
    .pv-amount .words { font-size: .8rem; font-weight: 600; color: var(--body); font-style: italic; max-width: 420px; text-align: right; }
    .pv-note { margin-top: 1.1rem; padding: .8rem 1rem; border-left: 3px solid var(--brand); background: var(--well); border-radius: 0 10px 10px 0;
        font-size: .86rem; color: var(--body); white-space: pre-line; }
    .pv-account a { color: var(--brand); text-decoration: none; }
    .pv-auto { display: block; font-size: .68rem; font-weight: 600; color: var(--faint); margin-top: .15rem; }
    .pv-sign { display: none; }
    @media print {
        .pv-sign { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; margin-top: 3.5rem; }
        .pv-sign div { border-top: 1px solid #94a3b8; padding-top: .4rem; font-size: .75rem; color: #475569; text-align: center; }
    }
</style>

<div class="gl-page">
    <div class="gl-head pi-noprint">
        <div class="gl-sub"><strong>{{ $expense->number }}</strong> — {{ $expense->category }} payment to {{ $expense->payee_name ?: $expense->payee_type_label }}</div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <a class="pi-btn" href="{{ route('crm.payments.index') }}"><i class="fas fa-arrow-left"></i>&nbsp; All Payments</a>
            <button type="button" class="pi-btn" data-payment="{{ json_encode($expense->formData()) }}" onclick="exOpenEdit(JSON.parse(this.dataset.payment))"><i class="fas fa-pen"></i>&nbsp; Edit</button>
            <button type="button" class="pi-btn" onclick="window.print()"><i class="fas fa-print"></i>&nbsp; Print</button>
            @if($canDelete)
            <form method="POST" action="{{ route('crm.expenses.destroy', $expense->id) }}" onsubmit="return confirm('Delete this payment? It will be removed from Payments and its bank / cash account.');">
                {{ csrf_field() }} {{ method_field('DELETE') }}
                <button type="submit" class="pi-btn danger"><i class="fas fa-trash"></i>&nbsp; Delete</button>
            </form>
            @endif
        </div>
    </div>

    <div class="pi-card">
        <div class="pi-card-head">
            <i class="fas fa-hand-holding-usd" style="color:var(--brand)"></i> Payment Voucher {{ $expense->number }}
            <span class="pi-status paid" style="margin-left:auto">Paid</span>
        </div>
        <div class="pi-card-body">
            <div class="pv-amount">
                <div>
                    <div class="k">Amount Paid</div>
                    <div class="big">{{ $fmt($expense->amount) }} <small>{{ $expense->currency }}</small></div>
                </div>
                @if($words)<div class="words">{{ $words }}</div>@endif
            </div>

            <div class="pi-meta">
                <div><div class="k">Payment Date</div><div class="v">{{ $expense->expense_date->format('d M Y') }}</div></div>
                <div><div class="k">Expense Category</div><div class="v">{{ $expense->category }}</div></div>
                <div><div class="k">Paid To</div><div class="v">{{ $expense->payee_name ?: '—' }} <span class="gl-badge {{ $payeeTone[$expense->payee_type] ?? 'part' }}" style="margin-left:.3rem">{{ $expense->payee_type_label }}</span></div></div>
                <div><div class="k">Paid From</div><div class="v">{{ $expense->paid_from }}</div></div>
                <div class="pv-account"><div class="k">Bank / Cash Account</div><div class="v">
                    @if($account)
                        <a href="{{ route('crm.bank_accounts.show', $account->id) }}">{{ $account->name }}</a>
                        @if($autoBooked)<span class="pv-auto">auto, by payment method</span>@endif
                    @else
                        <span class="gl-mutedtext">Not booked to an account</span>
                    @endif
                </div></div>
                <div><div class="k">Reference</div><div class="v">{{ $expense->reference ?: '—' }}</div></div>
                <div><div class="k">Recorded By</div><div class="v">{{ optional($expense->creator)->name ?: '—' }}</div></div>
                <div><div class="k">Recorded On</div><div class="v">{{ $expense->created_at ? $expense->created_at->format('d M Y, h:i A') : '—' }}</div></div>
            </div>

            <div style="margin-top:1.1rem"><div class="pi-meta"><div><div class="k">Description</div><div class="v" style="font-weight:600">{{ $expense->name }}</div></div></div></div>
            @if($expense->note)<div class="pv-note">{{ $expense->note }}</div>@endif

            <div class="pv-sign"><div>Prepared By</div><div>Approved By</div><div>Received By</div></div>
        </div>
    </div>

    @if($expense->proof_path)
    <div class="pi-card">
        <div class="pi-card-head"><i class="fas fa-paperclip" style="color:var(--brand)"></i> Proof
            <a class="pi-btn pi-noprint" style="margin-left:auto" href="{{ route('crm.expenses.proof', $expense->id) }}" target="_blank"><i class="fas fa-external-link-alt"></i>&nbsp; Open</a>
        </div>
        <div class="pi-card-body">
            @if($isImage)
                <img class="pi-attach-img" src="{{ route('crm.expenses.proof', $expense->id) }}" alt="Payment proof {{ $expense->proof_name }}">
            @else
                <a href="{{ route('crm.expenses.proof', $expense->id) }}" target="_blank"><i class="fas fa-file-pdf"></i> {{ $expense->proof_name }}</a>
            @endif
        </div>
    </div>
    @endif
</div>

@include('crm.payments._make_payment')
@endsection
