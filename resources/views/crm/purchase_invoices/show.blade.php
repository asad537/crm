@extends('crm.layout')
@section('title', $invoice->number . ' - ' . $invoice->vendor_name)
@section('content')
@php
    $fmt = function ($value) { return number_format((float) $value, 2); };
    $paid = $invoice->paidTotal();
    $balance = $invoice->balanceDue();
    $isImage = $invoice->attachment_name && preg_match('/\.(jpe?g|png|webp)$/i', $invoice->attachment_name);
@endphp

@include('crm.general_ledger._styles')
@include('crm.expenses._styles')
@include('crm.purchase_invoices._styles')

<div class="gl-page">
    <div class="gl-head pi-noprint">
        <div class="gl-sub"><strong>{{ $invoice->number }}</strong> — purchase invoice from {{ $invoice->vendor_name }}</div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <a class="pi-btn" href="{{ route('crm.purchase_invoices.index') }}"><i class="fas fa-arrow-left"></i>&nbsp; All Invoices</a>
            <a class="pi-btn" href="{{ route('crm.purchase_invoices.edit', $invoice->id) }}"><i class="fas fa-pen"></i>&nbsp; Edit</a>
            <button type="button" class="pi-btn" onclick="window.print()"><i class="fas fa-print"></i>&nbsp; Print</button>
            @if($canDelete)
            <form method="POST" action="{{ route('crm.purchase_invoices.destroy', $invoice->id) }}" onsubmit="return confirm('Delete {{ $invoice->number }} and its {{ $invoice->payments->count() }} payment(s)?');">
                {{ csrf_field() }} {{ method_field('DELETE') }}
                <button type="submit" class="pi-btn danger"><i class="fas fa-trash"></i>&nbsp; Delete</button>
            </form>
            @endif
        </div>
    </div>

    <div class="pi-card">
        <div class="pi-card-head">
            <i class="fas fa-file-invoice" style="color:var(--brand)"></i> Purchase Invoice {{ $invoice->number }}
            <span class="pi-status {{ $invoice->status() }}" style="margin-left:auto">{{ $invoice->statusLabel() }}</span>
        </div>
        <div class="pi-card-body">
            <div class="pi-meta">
                <div><div class="k">Supplier</div><div class="v">{{ $invoice->vendor_name }}</div></div>
                <div><div class="k">Issue Date</div><div class="v">{{ $invoice->issue_date->format('d M Y') }}</div></div>
                <div><div class="k">Due Date</div><div class="v">{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : '—' }}</div></div>
                <div><div class="k">Reference</div><div class="v">{{ $invoice->reference ?: '—' }}</div></div>
                <div><div class="k">Project</div><div class="v">{{ $invoice->project ?: '—' }}</div></div>
                <div><div class="k">Created By</div><div class="v">{{ optional($invoice->creator)->name ?: '—' }}</div></div>
            </div>
            @if($invoice->description)
                <div style="margin-top:1rem"><div class="pi-meta"><div><div class="k">Description</div><div class="v" style="font-weight:500;white-space:pre-line">{{ $invoice->description }}</div></div></div></div>
            @endif

            <div class="gl-tablewrap" style="margin-top:1.2rem">
                <table class="pi-items">
                    <thead><tr><th style="width:40px">#</th><th>Item</th><th style="width:190px">Category</th><th class="r" style="width:110px">Quantity</th><th class="r" style="width:130px">Unit Price</th><th class="r" style="width:140px">Total</th></tr></thead>
                    <tbody>
                    @foreach($invoice->items as $i => $item)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td style="font-weight:600;color:var(--ink)">
                                @if($item->fixedAsset)<a class="pi-asset-chip" style="text-decoration:none;margin-right:.35rem" href="{{ route('crm.fixed_assets.index', ['search' => $item->fixedAsset->name]) }}" title="Open in Fixed Assets"><i class="fas fa-boxes"></i> {{ $item->fixedAsset->number }}</a>@endif
                                @if($item->inventoryItem)<a class="pi-asset-chip pi-stock-chip" style="text-decoration:none;margin-right:.35rem" href="{{ route('crm.inventory.movements', $item->inventory_item_id) }}" title="Open stock history"><i class="fas fa-cubes"></i> Stock +{{ rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.') }} {{ $item->inventoryItem->unit }}</a>@endif{{ $item->name }}
                            </td>
                            <td>@if($item->category)<span class="gl-badge part">{{ $item->category }}</span>@else<span class="gl-mutedtext">—</span>@endif</td>
                            <td class="r">{{ rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') }}</td>
                            <td class="r">{{ $fmt($item->unit_price) }}</td>
                            <td class="r pi-line-total">{{ $fmt($item->line_total) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @php $categoryTotals = $invoice->categoryTotals(); @endphp
            @if(count($categoryTotals) > 1)
                <div style="margin-top:.9rem;display:flex;flex-wrap:wrap;gap:.4rem;align-items:center;font-size:.76rem">
                    <span class="gl-mutedtext" style="font-weight:700">By category:</span>
                    @foreach($categoryTotals as $category => $amount)
                        <span class="gl-badge part">{{ $category }} · {{ $fmt($amount) }}</span>
                    @endforeach
                </div>
            @endif

            <div class="pi-sum" style="margin-top:1rem">
                <div class="big"><span>Invoice Amount</span><span>{{ $fmt($invoice->total) }} {{ $invoice->currency }}</span></div>
                <div><span>Paid</span><span style="color:var(--green);font-weight:700">{{ $fmt($paid) }}</span></div>
                @if($balance < -0.009)
                    <div><span>Balance Due</span><span>{{ $fmt(0) }}</span></div>
                    <div class="big"><span>Advance to Vendor</span><span style="color:#b45309">{{ $fmt(-$balance) }} {{ $invoice->currency }}</span></div>
                    <p class="pi-advance-note">Paid {{ $fmt(-$balance) }} more than this bill. The extra is an advance to {{ $invoice->vendor_name }}: it shows as "Advances to Vendors" on the Balance Sheet and is taken off their next bill.</p>
                @else
                    <div class="big"><span>Balance Due</span><span style="color:{{ $balance > 0.009 ? 'var(--red)' : 'var(--green)' }}">{{ $fmt($balance) }} {{ $invoice->currency }}</span></div>
                @endif
            </div>
        </div>
    </div>

    @if($invoice->attachment_path)
    <div class="pi-card">
        <div class="pi-card-head"><i class="fas fa-image" style="color:var(--brand)"></i> Invoice image
            <a class="pi-btn pi-noprint" style="margin-left:auto" href="{{ route('crm.purchase_invoices.attachment', $invoice->id) }}" target="_blank"><i class="fas fa-external-link-alt"></i>&nbsp; Open</a>
        </div>
        <div class="pi-card-body">
            @if($isImage)
                <img class="pi-attach-img" src="{{ route('crm.purchase_invoices.attachment', $invoice->id) }}" alt="Invoice image {{ $invoice->attachment_name }}">
            @else
                <a href="{{ route('crm.purchase_invoices.attachment', $invoice->id) }}" target="_blank"><i class="fas fa-file-pdf"></i> {{ $invoice->attachment_name }}</a>
            @endif
        </div>
    </div>
    @endif

    <div class="pi-card">
        <div class="pi-card-head"><i class="fas fa-money-bill-wave" style="color:var(--brand)"></i> Payments
<span class="gl-mutedtext" style="margin-left:auto;font-weight:600;font-size:.75rem">Every payment here also shows under Payments and in {{ $invoice->vendor_name }}'s vendor ledger.</span>
        </div>
        <div class="pi-card-body">
            @if($invoice->payments->isEmpty())
                <div class="gl-mutedtext">No payments recorded yet.</div>
            @else
                <div class="gl-tablewrap">
                    <table class="pi-items">
                        <thead><tr><th style="width:110px">Date</th><th>Method</th><th>Reference / Note</th><th>Recorded By</th><th class="r" style="width:130px">Amount</th><th class="pi-noprint" style="width:44px"></th></tr></thead>
                        <tbody>
                        @foreach($invoice->payments as $payment)
                            <tr>
                                <td>{{ $payment->paid_at->format('d M Y') }}</td>
                                <td>{{ $payment->method ?: '—' }}</td>
                                <td>{{ trim($payment->reference . ' ' . $payment->note) ?: '—' }}</td>
                                <td>{{ optional($payment->creator)->name ?: '—' }}</td>
                                <td class="r pi-line-total">{{ $fmt($payment->amount) }}</td>
                                <td class="pi-noprint">
                                    @if($canDelete)
                                    <form method="POST" action="{{ route('crm.purchase_invoices.payments.destroy', [$invoice->id, $payment->id]) }}" onsubmit="return confirm('Remove this payment of {{ $fmt($payment->amount) }}?');">
                                        {{ csrf_field() }} {{ method_field('DELETE') }}
                                        <button type="submit" class="ex-icon danger" title="Remove payment"><i class="fas fa-trash"></i></button>
                                    </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <form method="POST" action="{{ route('crm.purchase_invoices.payments.store', $invoice->id) }}" class="pi-noprint" style="margin-top:1.1rem;border-top:1px solid var(--line);padding-top:1rem">
                {{ csrf_field() }}
                @if($errors->any())
                    <div class="ex-errors" style="margin-bottom:.8rem"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
                @endif
                <div class="pi-grid">
                    <div class="ex-f"><label>Amount <span class="req">*</span></label><input type="number" name="amount" step="0.01" min="0.01" value="{{ old('amount', $balance > 0.009 ? $balance : '') }}" required></div>
                    <div class="ex-f"><label>Payment Date <span class="req">*</span></label><input type="date" name="paid_at" value="{{ old('paid_at', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required></div>
                    <div class="ex-f"><label>Method</label>
                        <select name="method"><option value="">Select...</option>@foreach($paymentMethods as $method)<option {{ old('method') === $method ? 'selected' : '' }}>{{ $method }}</option>@endforeach</select>
                    </div>
                    <div class="ex-f"><label>Reference</label><input name="reference" value="{{ old('reference') }}" maxlength="120" placeholder="Cheque / transfer #"></div>
                    @include('crm.bank_accounts._select', ['baSelected' => old('bank_account_id')])
                    <div class="ex-f" style="grid-column: span 2"><label>Note</label><input name="note" value="{{ old('note') }}" maxlength="500"></div>
                </div>
                <div class="pi-actions" style="margin-top:.9rem"><button type="submit" class="ex-add"><i class="fas fa-check"></i> Record Payment</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
