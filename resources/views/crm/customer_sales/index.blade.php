@extends('crm.layout')

@section('title', 'Customers & Sales')

@section('header_actions')
    @if($selectedCustomer)
        <button class="cs-btn cs-btn-soft" type="button" onclick="openCustomerModal()"><i class="fas fa-user-plus"></i> Add Customer</button>
        @if(!$showIncome)<button class="cs-btn cs-btn-primary" type="button" onclick="openSaleModal()"><i class="fas fa-plus"></i> Add Sale</button>@endif
    @else
        <button class="cs-btn cs-btn-primary" type="button" onclick="openCustomerModal()"><i class="fas fa-user-plus"></i> Add Customer</button>
    @endif
@endsection

@section('content')
<style>
    .cs-page{--cs-main:var(--primary-purple);--cs-soft:var(--primary-soft);--cs-shadow:var(--primary-shadow);color:#243147}
    .cs-btn{min-height:42px;padding:.65rem 1rem;border:0;border-radius:11px;display:inline-flex;align-items:center;justify-content:center;gap:.5rem;font-weight:800;cursor:pointer}
    .cs-btn-primary{background:var(--primary-purple,#6d5dfc);color:#fff;box-shadow:0 8px 20px var(--primary-shadow,rgba(109,93,252,.2))}.cs-btn-soft{background:var(--primary-soft,#f1efff);color:var(--primary-purple,#6d5dfc)}
    .cs-hero{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.35rem 1.5rem;background:linear-gradient(135deg,#fff,var(--cs-soft));border:1px solid #e4eaf1;border-radius:18px;margin-bottom:1rem;box-shadow:0 7px 22px rgba(15,23,42,.05)}
    .cs-hero h2{margin:0;font-size:1.35rem}.cs-muted{color:#91a0b7;font-size:.78rem}.cs-search{width:300px;max-width:100%;padding:.72rem .85rem;border:1.5px solid #dbe3ed;border-radius:11px;outline:0}.cs-search:focus,.cs-control:focus{border-color:var(--cs-main);box-shadow:0 0 0 3px var(--cs-shadow)}
    .cs-directory-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;margin-bottom:1rem}.cs-directory-stat{display:flex;align-items:center;gap:1rem;min-height:104px;padding:1rem 1.2rem;background:#fff;border:1px solid #e5ebf2;border-radius:17px;box-shadow:0 7px 24px rgba(15,23,42,.05)}.cs-directory-stat-icon{width:52px;height:52px;flex:0 0 52px;display:flex;align-items:center;justify-content:center;border-radius:15px;font-size:1.2rem}.cs-directory-stat-copy span{display:block;color:#91a0b7;font-size:.69rem;font-weight:850;letter-spacing:.065em;text-transform:uppercase}.cs-directory-stat-copy strong{display:block;margin-top:.18rem;color:#111827;font-size:1.55rem;line-height:1.1}.cs-directory-stat-copy small{display:block;margin-top:.28rem;color:#10a875;font-size:.68rem;font-weight:800}
    .cs-table-wrap{overflow:auto;background:#fff;border:1px solid #e5ebf2;border-radius:16px;box-shadow:0 9px 28px rgba(15,23,42,.055)}.cs-table{width:100%;border-collapse:collapse;min-width:900px}.cs-table th{padding:.9rem 1rem;background:#f7f9fc;border-bottom:2px solid var(--cs-soft);color:#728097;text-align:left;font-size:.67rem;letter-spacing:.05em;text-transform:uppercase}.cs-table td{padding:.95rem 1rem;border-bottom:1px solid #edf1f5;font-size:.82rem;vertical-align:middle}.cs-table tbody tr:hover{background:var(--cs-soft)}
    .cs-person{display:flex;align-items:center;gap:.7rem}.cs-avatar{width:38px;height:38px;display:flex;align-items:center;justify-content:center;border-radius:11px;background:var(--cs-soft);color:var(--cs-main);font-weight:900}.cs-person strong{display:block;color:#1f2b3d}.cs-link{display:inline-flex;align-items:center;gap:.4rem;padding:.43rem .7rem;border:1px solid var(--cs-shadow);border-radius:9px;text-decoration:none;color:var(--cs-main);font-weight:800}.cs-link:hover{background:var(--cs-main);color:#fff}
    .cs-row-actions{display:flex;align-items:center;gap:.4rem}.cs-delete{display:inline-flex;align-items:center;justify-content:center;min-width:35px;min-height:35px;border:1px solid #fecaca;border-radius:9px;background:#fff5f5;color:#dc2626;cursor:pointer}.cs-delete:hover:not(:disabled){background:#fee2e2}.cs-delete:disabled{opacity:.45;cursor:not-allowed}
    .cs-due-edit{border:0;background:transparent;color:#94a3b8;width:24px;height:24px;border-radius:6px;cursor:pointer;font-size:.68rem;margin-left:.2rem;vertical-align:middle;opacity:.55;transition:all .15s}.cs-due-edit:hover,tr:hover .cs-due-edit{opacity:1;background:#eef2ff;color:var(--cs-main)}
    .cs-back{display:inline-flex;align-items:center;gap:.45rem;margin-bottom:.8rem;color:var(--cs-main);text-decoration:none;font-weight:800}.cs-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1rem}.cs-stat{padding:1rem 1.1rem;background:#fff;border:1px solid #e5ebf2;border-radius:15px;box-shadow:0 6px 18px rgba(15,23,42,.045)}.cs-stat span{display:block;color:#8695aa;font-size:.68rem;font-weight:800;text-transform:uppercase}.cs-stat strong{display:block;margin-top:.45rem;font-size:1.15rem;color:#1f2937}.cs-status{display:inline-flex;padding:.32rem .6rem;border-radius:999px;background:var(--cs-soft);color:var(--cs-main);font-weight:800;font-size:.7rem}.cs-pay{display:flex;align-items:center;gap:.35rem}.cs-pay input{width:90px}.cs-icon-btn{width:34px;height:34px;border:0;border-radius:9px;background:var(--cs-main);color:#fff;cursor:pointer}
    .cs-toolbar{display:flex;gap:.65rem;align-items:center;margin-bottom:.85rem}.cs-toolbar form{display:flex;gap:.55rem;flex:1}.cs-control{width:100%;padding:.66rem .75rem;border:1.5px solid #dce4ee;border-radius:10px;background:#fff;outline:0;box-sizing:border-box}.cs-modal-bg{position:fixed;inset:0;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;padding:1rem;z-index:9999}.cs-modal{width:100%;max-width:850px;max-height:92vh;overflow:auto;background:#fff;border-radius:18px;box-shadow:0 24px 65px rgba(15,23,42,.25)}.cs-modal-head{display:flex;justify-content:space-between;align-items:center;padding:1rem 1.2rem;border-bottom:1px solid #edf1f5}.cs-modal-title{display:flex;align-items:center;gap:.75rem}.cs-modal-icon{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;background:var(--cs-main);color:#fff}.cs-modal h3{margin:0}.cs-close{border:0;background:#f1f5f9;width:34px;height:34px;border-radius:9px;cursor:pointer}.cs-form{padding:1.1rem}.cs-grid{display:grid;grid-template-columns:repeat(12,1fr);gap:.8rem}.cs-field{grid-column:span 4}.cs-field-6{grid-column:span 6}.cs-field-12{grid-column:1/-1}.cs-field label{display:block;margin-bottom:.35rem;color:#475569;font-size:.72rem;font-weight:800}.cs-actions{display:flex;justify-content:flex-end;gap:.65rem;margin-top:1rem;padding-top:1rem;border-top:1px solid #edf1f5}.cs-totals{grid-column:1/-1;display:grid;grid-template-columns:repeat(4,1fr);padding:.8rem;border-radius:12px;background:var(--cs-soft);border:1px solid var(--cs-shadow)}.cs-totals span{display:block;color:#728097;font-size:.67rem;text-transform:uppercase;font-weight:800}.cs-totals strong{display:block;margin-top:.3rem;color:var(--cs-main)}.cs-empty{text-align:center!important;padding:2rem!important;color:#94a3b8}
    @media(max-width:800px){.cs-stats{grid-template-columns:repeat(2,1fr)}.cs-directory-stats{grid-template-columns:1fr}.cs-hero{align-items:flex-start;flex-direction:column}.cs-search{width:100%}.cs-field,.cs-field-6{grid-column:1/-1}}
</style>

<div class="cs-page">
@if(!$selectedCustomer)
    <div class="cs-hero">
        <div><h2>Customer Directory</h2><div class="cs-muted">Add a customer first, then record sales and payments against that customer.</div></div>
        <input id="customerSearch" class="cs-search" placeholder="Search customer, company, country..." oninput="filterCustomers(this.value)">
    </div>
    <div class="cs-directory-stats">
        <div class="cs-directory-stat"><span class="cs-directory-stat-icon" style="color:var(--cs-main);background:var(--cs-soft)"><i class="fas fa-users"></i></span><div class="cs-directory-stat-copy"><span>Total Customers</span><strong>{{ number_format($directorySummary['customers']) }}</strong><small>Customer profiles</small></div></div>
        <div class="cs-directory-stat"><span class="cs-directory-stat-icon" style="color:#059669;background:#e1f8ef"><i class="fas fa-shopping-bag"></i></span><div class="cs-directory-stat-copy"><span>Total Orders</span><strong>{{ number_format($directorySummary['orders']) }}</strong><small>Recorded customer sales</small></div></div>
        <div class="cs-directory-stat"><span class="cs-directory-stat-icon" style="color:#2563eb;background:#e8f1ff"><i class="fas fa-chart-line"></i></span><div class="cs-directory-stat-copy"><span>Avg. Order Value</span><strong>{{ number_format($directorySummary['average_order'],2) }}</strong><small>Per recorded order</small></div></div>
    </div>
    <div class="cs-table-wrap">
        <table class="cs-table" id="customersTable">
            <thead><tr><th>Customer</th><th>Country</th><th>Phone</th><th>Email</th><th>Orders</th><th>Total Sales</th><th>Outstanding</th><th>Action</th></tr></thead>
            <tbody>
            @forelse($customers as $customer)
                <tr data-search="{{ strtolower($customer->name.' '.$customer->company_name.' '.$customer->country.' '.$customer->phone.' '.$customer->email) }}">
                    <td><div class="cs-person"><span class="cs-avatar">{{ strtoupper(substr($customer->name,0,1)) }}</span><div><strong>{{ $customer->name }}</strong><div class="cs-muted">{{ $customer->contact_person ? 'Contact: '.$customer->contact_person : ($customer->company_name ?: 'Customer #'.str_pad($customer->id,4,'0',STR_PAD_LEFT)) }}</div></div></div></td>
                    <td>{{ $customer->country ?: '-' }}</td><td>{{ $customer->phone ?: '-' }}</td><td>{{ $customer->email ?: '-' }}</td>
                    @if($customer->isIncomeCustomer())
                        @php($__ibal = $customer->incomeEntries->sum(fn($e) => $e->balance))
                        <td colspan="3"><span class="cs-status" style="background:#ede9fe;color:#6d28d9"><i class="fas fa-home"></i> {{ $customer->income_head ?: 'Other income' }}</span> <span class="cs-muted">@if($customer->recurring_active){{ $customer->currency }} {{ number_format((float)$customer->recurring_amount,2) }}/month · day {{ $customer->recurring_day }}@else One-off / historical income @endif</span>
                            @if($__ibal > 0.009)<div style="margin-top:.25rem;font-weight:800;color:#b91c1c">Due: {{ $customer->currency }} {{ number_format($__ibal,2) }}</div>@else<div style="margin-top:.25rem;font-weight:800;color:#15803d">All paid</div>@endif</td>
                    @else
                    <td>{{ $customer->sales_count }} @if($canSeeIncome && $customer->incomeEntries->isNotEmpty())<div class="cs-muted">Rent entry</div>@endif</td><td>{{ $customer->currency }} {{ number_format($customer->sales->sum('total_amount'),2) }}</td><td>{{ $customer->currency }} {{ number_format($customer->sales->sum('balance_amount'),2) }}</td>
                    @endif
                    <td><div class="cs-row-actions">
                        <a class="cs-link" href="{{ route('crm.customer_sales.index',['customer_id'=>$customer->id]) }}">{{ $customer->isIncomeCustomer() ? 'View Income' : 'View Sales' }} <i class="fas fa-arrow-right"></i></a>
                        @if(Auth::guard('crm')->user()->isAdmin() || Auth::guard('crm')->user()->isSalesManager() || Auth::guard('crm')->user()->isAccounts())
                            <form method="POST" action="{{ route('crm.customers.destroy', $customer->id) }}" onsubmit="return confirm('Delete this empty customer profile? This cannot be undone.');">
                                @csrf @method('DELETE')
                                @php($__locked = $customer->sales_count || $customer->incomeEntries->sum('amount_paid') > 0)
                                <button class="cs-delete" type="submit" title="{{ $__locked ? 'Customer has records' : 'Delete customer' }}" aria-label="Delete customer" {{ $__locked ? 'disabled' : '' }}><i class="fas fa-trash-alt"></i></button>
                            </form>
                        @endif
                    </div></td>
                </tr>
            @empty
                <tr><td colspan="8" class="cs-empty">No customers yet. Click “Add Customer” to create the first customer.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@elseif($showIncome)
    <a class="cs-back" href="{{ route('crm.customer_sales.index') }}"><i class="fas fa-arrow-left"></i> All Customers</a>
    @if($hasBoth)<a class="cs-link" style="margin:0 0 .8rem .6rem" href="{{ route('crm.customer_sales.index',['customer_id'=>$selectedCustomer->id]) }}">View sales</a>@endif
    <div class="cs-hero"><div><h2>{{ $selectedCustomer->name }} <span class="cs-status" style="background:#ede9fe;color:#6d28d9;font-size:.7rem;vertical-align:middle"><i class="fas fa-home"></i> {{ $selectedCustomer->income_head ?: 'Other income' }}</span></h2><div class="cs-muted">{{ $selectedCustomer->company_name }} · {{ $selectedCustomer->country }} · {{ $selectedCustomer->email }} @if($selectedCustomer->recurring_active)· {{ $selectedCustomer->currency }} {{ number_format((float)$selectedCustomer->recurring_amount,2) }} every month, due on day {{ $selectedCustomer->recurring_day }} @else · One-off / historical income; no automatic monthly charge @endif</div></div>
        @if($canEditIncome && $selectedCustomer->isIncomeCustomer())<button class="cs-btn cs-btn-soft" type="button" onclick="document.getElementById('incomeScheduleModal').style.display='flex'"><i class="fas fa-cog"></i> Schedule</button>@endif</div>
    <div class="cs-stats">
        <div class="cs-stat"><span>Total Due (all months)</span><strong>{{ $selectedCustomer->currency }} {{ number_format($incomeSummary['due'],2) }}</strong></div>
        <div class="cs-stat"><span>Received</span><strong>{{ $selectedCustomer->currency }} {{ number_format($incomeSummary['paid'],2) }}</strong></div>
        <div class="cs-stat"><span>Outstanding</span><strong style="color:{{ $incomeSummary['balance'] > 0.009 ? '#b91c1c' : '#15803d' }}">{{ $selectedCustomer->currency }} {{ number_format($incomeSummary['balance'],2) }}</strong></div>
        <div class="cs-stat"><span>Overdue months</span><strong>{{ $incomeSummary['overdue'] }}</strong></div>
    </div>
    @if($canEditIncome)
    <form method="POST" action="{{ route('crm.income.add_period', $selectedCustomer->id) }}" class="cs-toolbar" style="gap:.5rem;align-items:flex-end;flex-wrap:wrap">{{ csrf_field() }}
        <div style="flex:0 0 150px"><label class="cs-muted" style="font-size:.68rem;font-weight:800">Add a month</label><input class="cs-control" type="month" name="period" value="{{ now()->addMonth()->format('Y-m') }}" required></div>
        <div style="flex:0 0 140px"><label class="cs-muted" style="font-size:.68rem;font-weight:800">Amount</label><input class="cs-control" type="number" step=".01" min="0" name="amount_due" value="{{ $selectedCustomer->recurring_amount }}" required></div>
        <div style="flex:0 0 160px"><label class="cs-muted" style="font-size:.68rem;font-weight:800">Head</label><input class="cs-control" name="income_head" value="{{ $selectedCustomer->income_head }}"></div>
        <button class="cs-btn cs-btn-soft" style="flex:0 0 auto"><i class="fas fa-plus"></i> Add</button>
        <span class="cs-muted" style="font-size:.7rem">A new month is automatic only when the recurring schedule is active.</span>
    </form>
    @endif
    <div class="cs-table-wrap"><table class="cs-table"><thead><tr><th>Month</th><th>Head</th><th>Due date</th><th style="text-align:right">Due</th><th style="text-align:right">Received</th><th style="text-align:right">Balance</th><th>Status</th><th>Payment details</th><th>Proof</th><th></th></tr></thead><tbody>
    @forelse($incomeEntries as $e)
        @php($__st = $e->status)
        <tr>
            <td><strong>{{ $e->period_label }}</strong></td>
            <td>{{ $e->income_head }}</td>
            <td>{{ $e->due_date->format('d M Y') }}</td>
            <td style="text-align:right;white-space:nowrap">{{ number_format($e->amount_due,2) }} @if($canEditIncome)<button class="cs-due-edit" type="button" title="Change this month's due" onclick="openDueModal({{ $e->id }})"><i class="fas fa-pen"></i></button>@endif</td>
            <td style="text-align:right">{{ number_format($e->amount_paid,2) }}</td>
            <td style="text-align:right;font-weight:800;color:{{ $e->balance > 0.009 ? '#b91c1c' : '#15803d' }}">{{ number_format($e->balance,2) }}</td>
            <td><span class="cs-status" style="{{ $__st==='paid' ? 'background:#dcfce7;color:#15803d' : ($__st==='partial' ? 'background:#fef3c7;color:#b45309' : ($__st==='overdue' ? 'background:#fee2e2;color:#b91c1c' : 'background:#e0f2fe;color:#0369a1')) }}">{{ ucfirst($__st) }}</span></td>
            <td class="cs-muted" style="font-size:.74rem">@if($e->paid_at){{ $e->paid_at->format('d M Y') }}@if($e->payment_method) · {{ $e->payment_method }}@endif @if($e->reference)· {{ $e->reference }}@endif @if($e->note)<div>{{ $e->note }}</div>@endif @else — @endif</td>
            <td>@if($e->proof_path)<a class="cs-link" href="{{ route('crm.income.proof', $e->id) }}" target="_blank"><i class="fas fa-paperclip"></i> View</a>@else<span class="cs-muted">—</span>@endif</td>
            <td style="white-space:nowrap">@if($canEditIncome)<button class="cs-btn {{ $__st==='paid' ? 'cs-btn-soft' : 'cs-btn-primary' }}" type="button" style="padding:.45rem .75rem;font-size:.74rem" onclick="openPayModal({{ $e->id }})"><i class="fas {{ $__st==='paid' ? 'fa-edit' : 'fa-hand-holding-usd' }}"></i> {{ $__st==='paid' ? 'Edit' : 'Record payment' }}</button>@endif</td>
        </tr>
    @empty<tr><td colspan="10" class="cs-empty">No months due yet — check the schedule start month.</td></tr>@endforelse
    </tbody></table></div>
    @if($canEditIncome)
    {{-- Record payment modal --}}
    <div class="cs-modal-bg" id="incomePayModal" onclick="if(event.target===this)this.style.display='none'"><div class="cs-modal" style="max-width:560px"><div class="cs-modal-head"><div class="cs-modal-title"><span class="cs-modal-icon"><i class="fas fa-hand-holding-usd"></i></span><div><h3>Record payment</h3><div class="cs-muted" id="incomePaySub"></div></div></div><button class="cs-close" type="button" onclick="document.getElementById('incomePayModal').style.display='none'"><i class="fas fa-times"></i></button></div>
        <form method="POST" id="incomePayForm" enctype="multipart/form-data" class="cs-form">{{ csrf_field() }}<div class="cs-grid">
            <div class="cs-field-6"><label>Amount received ({{ $selectedCustomer->currency }}) *</label><input class="cs-control" type="number" step=".01" min="0" name="amount_paid" required></div>
            <div class="cs-field-6"><label>Received on</label><input class="cs-control" type="date" name="paid_at" value="{{ date('Y-m-d') }}"></div>
            <div class="cs-field-6"><label>Method</label><select class="cs-control" name="payment_method">@foreach(['Cash','Bank Transfer','Cheque','Card','Online'] as $m)<option>{{ $m }}</option>@endforeach</select></div>
            <div class="cs-field-6"><label>Reference #</label><input class="cs-control" name="reference" placeholder="Cheque / transfer no."></div>
            <div class="cs-field-12"><label>Proof (image or PDF)</label><input class="cs-control" type="file" name="proof" accept=".jpg,.jpeg,.png,.pdf,.webp"></div>
            <div class="cs-field-12"><label>Note</label><input class="cs-control" name="note"></div>
            <div class="cs-field-12 cs-muted" style="font-size:.72rem">Enter the total received for this month. Less than the due = Partial; equal = Paid.</div>
        </div><div class="cs-actions"><button class="cs-btn cs-btn-soft" type="button" onclick="document.getElementById('incomePayModal').style.display='none'">Cancel</button><button class="cs-btn cs-btn-primary"><i class="fas fa-check"></i> Save payment</button></div></form></div></div>
    {{-- Change due modal --}}
    <div class="cs-modal-bg" id="incomeDueModal" onclick="if(event.target===this)this.style.display='none'"><div class="cs-modal" style="max-width:460px"><div class="cs-modal-head"><div class="cs-modal-title"><span class="cs-modal-icon"><i class="fas fa-pen"></i></span><div><h3>Change due</h3><div class="cs-muted" id="incomeDueSub"></div></div></div><button class="cs-close" type="button" onclick="document.getElementById('incomeDueModal').style.display='none'"><i class="fas fa-times"></i></button></div>
        <form method="POST" id="incomeDueForm" class="cs-form">{{ csrf_field() }}{{ method_field('PUT') }}<div class="cs-grid">
            <div class="cs-field-6"><label>Amount due ({{ $selectedCustomer->currency }}) *</label><input class="cs-control" type="number" step=".01" min="0" name="amount_due" required></div>
            <div class="cs-field-6"><label>Due date</label><input class="cs-control" type="date" name="due_date"></div>
            <div class="cs-field-12"><label>Note</label><input class="cs-control" name="note" placeholder="e.g. one month discount"></div>
        </div><div class="cs-actions"><button class="cs-btn cs-btn-soft" type="button" onclick="document.getElementById('incomeDueModal').style.display='none'">Cancel</button><button class="cs-btn cs-btn-primary"><i class="fas fa-check"></i> Save</button></div></form></div></div>
    @endif
    @if($canEditIncome)
    <div class="cs-modal-bg" id="incomeScheduleModal" onclick="if(event.target===this)this.style.display='none'"><div class="cs-modal" style="max-width:560px"><div class="cs-modal-head"><div class="cs-modal-title"><span class="cs-modal-icon"><i class="fas fa-cog"></i></span><div><h3>Recurring schedule</h3><div class="cs-muted">{{ $selectedCustomer->name }}</div></div></div><button class="cs-close" type="button" onclick="document.getElementById('incomeScheduleModal').style.display='none'"><i class="fas fa-times"></i></button></div>
        <form method="POST" action="{{ route('crm.income.schedule', $selectedCustomer->id) }}" class="cs-form">{{ csrf_field() }}{{ method_field('PUT') }}<div class="cs-grid">
            <div class="cs-field-6"><label>Income head *</label><input class="cs-control" name="income_head" value="{{ $selectedCustomer->income_head }}" list="incomeHeads" required></div>
            <div class="cs-field-6"><label>Monthly amount ({{ $selectedCustomer->currency }}) *</label><input class="cs-control" type="number" step=".01" min="0" name="recurring_amount" value="{{ $selectedCustomer->recurring_amount }}" required></div>
            <div class="cs-field-6"><label>Due day of month *</label><input class="cs-control" type="number" min="1" max="31" name="recurring_day" value="{{ $selectedCustomer->recurring_day }}" required></div>
            <div class="cs-field-6"><label>First month *</label><input class="cs-control" type="month" name="recurring_start" value="{{ optional($selectedCustomer->recurring_start)->format('Y-m') }}" required></div>
            <div class="cs-field-12"><label style="display:flex;gap:.5rem;align-items:center"><input type="checkbox" name="recurring_active" value="1" {{ $selectedCustomer->recurring_active ? 'checked' : '' }}> Active — keep creating a due row every month</label></div>
        </div><div class="cs-actions"><button class="cs-btn cs-btn-soft" type="button" onclick="document.getElementById('incomeScheduleModal').style.display='none'">Cancel</button><button class="cs-btn cs-btn-primary">Save</button></div></form></div></div>
    @endif
@else
    <a class="cs-back" href="{{ route('crm.customer_sales.index') }}"><i class="fas fa-arrow-left"></i> All Customers</a>
    @if($canSeeIncome && $incomeEntries->isNotEmpty())<a class="cs-link" style="margin:0 0 .8rem .6rem" href="{{ route('crm.customer_sales.index',['customer_id'=>$selectedCustomer->id,'view'=>'income']) }}">View rent / other income</a>@endif
    <div class="cs-hero"><div><h2>{{ $selectedCustomer->name }}</h2><div class="cs-muted">{{ $selectedCustomer->company_name }} · {{ $selectedCustomer->country }} · {{ $selectedCustomer->email }}</div></div><span class="cs-status">{{ $selectedCustomer->currency }}</span></div>
    <div class="cs-stats">
        <div class="cs-stat"><span>Total Sales</span><strong>{{ $selectedCustomer->currency }} {{ number_format($summary['total'],2) }}</strong></div>
        <div class="cs-stat"><span>Amount Received</span><strong>{{ $selectedCustomer->currency }} {{ number_format($summary['paid'],2) }}</strong></div>
        <div class="cs-stat"><span>Outstanding</span><strong>{{ $selectedCustomer->currency }} {{ number_format($summary['balance'],2) }}</strong></div>
        <div class="cs-stat"><span>Open Orders</span><strong>{{ $summary['open_count'] }}</strong></div>
    </div>
    @if($summary['credit'] > 0.009)<div class="cs-hero" style="padding:.8rem 1rem"><strong>Customer credit: {{ $selectedCustomer->currency }} {{ number_format($summary['credit'],2) }}</strong><span class="cs-muted">Received above invoice totals; preserved separately from outstanding.</span></div>@endif
    <div class="cs-toolbar"><form method="GET"><input type="hidden" name="customer_id" value="{{ $selectedCustomer->id }}"><input class="cs-control" name="search" value="{{ request('search') }}" placeholder="Search order or item..."><select class="cs-control" name="payment_status" onchange="this.form.submit()"><option value="">All payments</option>@foreach(['Unpaid','Partial','Paid'] as $status)<option {{ request('payment_status')===$status?'selected':'' }}>{{ $status }}</option>@endforeach</select><button class="cs-btn cs-btn-soft">Search</button></form></div>
    <div class="cs-table-wrap"><table class="cs-table"><thead><tr><th>Date</th><th>Order</th><th>Item</th><th>Qty</th><th>Total</th><th>Paid / Balance</th><th>Order Status</th><th>Payment</th><th>Action</th></tr></thead><tbody>
    @forelse($sales as $sale)
        <tr><td>{{ $sale->order_date->format('d M Y') }}</td><td><strong>{{ $sale->order_number }}</strong></td><td>{{ $sale->item_name }}</td><td>{{ $sale->quantity === null ? '—' : number_format($sale->quantity,2).' '.$sale->unit }}</td><td>{{ $sale->currency }} {{ number_format($sale->total_amount,2) }}</td><td>{{ number_format($sale->paid_amount,2) }} / {{ number_format($sale->balance_amount,2) }} @if($sale->paid_amount > $sale->total_amount)<div class="cs-muted">Credit {{ number_format($sale->paid_amount-$sale->total_amount,2) }}</div>@endif</td><td><span class="cs-status">{{ $sale->order_status }}</span></td><td>
            @if($sale->crm_email_id)
                <span class="cs-status">{{ $sale->payment_status }}</span>
            @else
                <form class="cs-pay" method="POST" action="{{ route('crm.customer_sales.update_payment',$sale->id) }}">{{ csrf_field() }}{{ method_field('PATCH') }}<input class="cs-control" name="paid_amount" type="number" step=".01" min="0" value="{{ $sale->paid_amount }}"><button class="cs-icon-btn"><i class="fas fa-save"></i></button></form>
            @endif
        </td><td>
            @if($sale->crm_email_id)
                <a class="cs-link" href="{{ route('crm.orders.invoice', $sale->crm_email_id) }}">View Invoice</a>
            @else
                <button class="cs-icon-btn" type="button" onclick="editSale({{ $sale->id }})"><i class="fas fa-edit"></i></button>
            @endif
        </td></tr>
    @empty<tr><td colspan="9" class="cs-empty">No sales recorded for this customer.</td></tr>@endforelse
    </tbody></table></div>
    @if(method_exists($sales,'links')) {{ $sales->links() }} @endif
@endif
</div>

<div class="cs-modal-bg" id="customerModal" onclick="if(event.target===this)closeCustomerModal()"><div class="cs-modal" style="max-width:720px"><div class="cs-modal-head"><div class="cs-modal-title"><span class="cs-modal-icon"><i class="fas fa-user-plus"></i></span><div><h3>Add Customer</h3><div class="cs-muted">Customer profile is saved separately for the active project.</div></div></div><button class="cs-close" onclick="closeCustomerModal()"><i class="fas fa-times"></i></button></div><form class="cs-form" method="POST" action="{{ route('crm.customers.store') }}">{{ csrf_field() }}<div class="cs-grid">
    <div class="cs-field-6"><label>Customer Name *</label><input class="cs-control" name="name" required></div><div class="cs-field-6"><label>Company</label><input class="cs-control" name="company_name"></div><div class="cs-field-6"><label>Contact Person</label><input class="cs-control" name="contact_person"></div>
    <div class="cs-field"><label>Phone</label><input class="cs-control" name="phone"></div><div class="cs-field"><label>Email</label><input class="cs-control" type="email" name="email"></div><div class="cs-field"><label>Country *</label><select class="cs-control" name="country" id="customerCountry" required><option value="">Select country</option></select></div>
    <div class="cs-field"><label>Currency *</label><select class="cs-control" name="currency" id="customerCurrency" required>@foreach(['AED','USD','GBP','EUR','PKR','SAR','QAR','OMR','KWD','CAD','AUD'] as $currency)<option>{{ $currency }}</option>@endforeach</select></div><div class="cs-field"><label>TRN / Tax Number</label><input class="cs-control" name="tax_number"></div>
    @if($canEditIncome)
    <div class="cs-field-12" style="padding:.7rem .85rem;border:1px solid #e9e5ff;border-radius:12px;background:#faf9ff">
        <label>Customer type</label>
        <div style="display:flex;gap:1.2rem;flex-wrap:wrap;font-size:.82rem"><label style="display:flex;gap:.4rem;align-items:center"><input type="radio" name="customer_type" value="sales" checked onchange="csToggleIncome()"> Sales customer (orders & invoices)</label><label style="display:flex;gap:.4rem;align-items:center"><input type="radio" name="customer_type" value="income" onchange="csToggleIncome()"> Other income — rent, service charges… (monthly due)</label></div>
        <div id="csIncomeFields" style="display:none;margin-top:.7rem" class="cs-grid">
            <div class="cs-field"><label>Income head *</label><input class="cs-control" name="income_head" list="incomeHeads" placeholder="Rent"><datalist id="incomeHeads"><option value="Rent"><option value="Service charges"><option value="Utilities"><option value="Commission"><option value="Other income"></datalist></div>
            <div class="cs-field"><label>Monthly amount *</label><input class="cs-control" type="number" step=".01" min="0" name="recurring_amount"></div>
            <div class="cs-field"><label>Due day of month *</label><input class="cs-control" type="number" min="1" max="31" name="recurring_day" value="1"></div>
            <div class="cs-field-6"><label>First month *</label><input class="cs-control" type="month" name="recurring_start" value="{{ date('Y-m') }}"></div>
            <div class="cs-field-6 cs-muted" style="align-self:end;font-size:.72rem">A due row is created for every month from the first month onward; Accounts records what is received against each.</div>
        </div>
    </div>
    @endif
    <div class="cs-field-6"><label>Billing Address</label><textarea class="cs-control" name="billing_address"></textarea></div><div class="cs-field-6"><label>Shipping Address</label><textarea class="cs-control" name="shipping_address"></textarea></div><div class="cs-field-12"><label>Notes</label><textarea class="cs-control" name="notes"></textarea></div>
</div><div class="cs-actions"><button class="cs-btn cs-btn-soft" type="button" onclick="closeCustomerModal()">Cancel</button><button class="cs-btn cs-btn-primary">Save Customer</button></div></form></div></div>

@if($selectedCustomer)
<div class="cs-modal-bg" id="saleModal" onclick="if(event.target===this)closeSaleModal()"><div class="cs-modal"><div class="cs-modal-head"><div class="cs-modal-title"><span class="cs-modal-icon"><i class="fas fa-file-invoice-dollar"></i></span><div><h3 id="saleModalTitle">Add Customer Sale</h3><div class="cs-muted">{{ $selectedCustomer->name }}</div></div></div><button class="cs-close" onclick="closeSaleModal()"><i class="fas fa-times"></i></button></div><form class="cs-form" id="saleForm" method="POST" action="{{ route('crm.customer_sales.store') }}">{{ csrf_field() }}<input id="saleMethod" type="hidden" name="_method" value="POST"><input type="hidden" name="customer_id" value="{{ $selectedCustomer->id }}"><div class="cs-grid">
    <div class="cs-field"><label>Order Number *</label><input class="cs-control" name="order_number" value="SO-{{ date('ymd-His') }}" required></div><div class="cs-field"><label>Order Date *</label><input class="cs-control" type="date" name="order_date" value="{{ date('Y-m-d') }}" required></div><div class="cs-field"><label>Due Date</label><input class="cs-control" type="date" name="due_date"></div>
    <div class="cs-field-6"><label>Product / Item *</label><input class="cs-control" name="item_name" required></div><div class="cs-field-6"><label>Description</label><input class="cs-control" name="description"></div>
    <div class="cs-field"><label>Quantity *</label><input class="cs-control cs-calc" type="number" step=".01" min=".01" name="quantity" value="1" required></div><div class="cs-field"><label>Unit</label><select class="cs-control" name="unit">@foreach(['Pieces','Boxes','Sheets','Rolls','Kg','Sets','Units'] as $unit)<option>{{ $unit }}</option>@endforeach</select></div><div class="cs-field"><label>Unit Price *</label><input class="cs-control cs-calc" type="number" step=".000001" min="0" name="unit_price" value="0" required></div>
    <div class="cs-field"><label>Discount</label><input class="cs-control cs-calc" type="number" step=".01" min="0" name="discount_amount" value="0"></div><div class="cs-field"><label>Tax</label><input class="cs-control cs-calc" type="number" step=".01" min="0" name="tax_amount" value="0"></div><div class="cs-field"><label>Shipping</label><input class="cs-control cs-calc" type="number" step=".01" min="0" name="shipping_cost" value="0"></div>
    <div class="cs-field"><label>Paid Amount</label><input class="cs-control cs-calc" type="number" step=".01" min="0" name="paid_amount" value="0"></div><div class="cs-field"><label>Currency</label><select class="cs-control" name="currency">@foreach(['AED','USD','GBP','EUR','PKR','SAR','QAR','OMR','KWD','CAD','AUD'] as $currency)<option {{ $selectedCustomer->currency===$currency?'selected':'' }}>{{ $currency }}</option>@endforeach</select></div><div class="cs-field"><label>Payment Method</label><select class="cs-control" name="payment_method"><option value="">Select</option>@foreach(['Cash','Bank Transfer','Card','Cheque','Credit'] as $method)<option>{{ $method }}</option>@endforeach</select></div>
    <div class="cs-field"><label>Order Status</label><select class="cs-control" name="order_status">@foreach(['Draft','Confirmed','In Production','Ready','Dispatched','Delivered','Completed','On Hold','Cancelled'] as $status)<option>{{ $status }}</option>@endforeach</select></div><div class="cs-field-12"><label>Notes</label><textarea class="cs-control" name="notes"></textarea></div>
    <div class="cs-totals"><div><span>Subtotal</span><strong id="csSubtotal">0.00</strong></div><div><span>Total</span><strong id="csTotal">0.00</strong></div><div><span>Balance</span><strong id="csBalance">0.00</strong></div><div><span>Payment</span><strong id="csPaymentStatus">Unpaid</strong></div></div>
</div><div class="cs-actions"><button class="cs-btn cs-btn-soft" type="button" onclick="closeSaleModal()">Cancel</button><button class="cs-btn cs-btn-primary">Save Sale</button></div></form></div></div>
@endif
@endsection

@section('scripts')
<script>
function filterCustomers(value){var q=(value||'').toLowerCase().trim();document.querySelectorAll('#customersTable tbody tr[data-search]').forEach(function(row){row.style.display=!q||(row.getAttribute('data-search')||'').indexOf(q)!==-1?'table-row':'none'})}
function csToggleIncome(){var r=document.querySelector('input[name=customer_type]:checked');var box=document.getElementById('csIncomeFields');if(!box)return;var on=r&&r.value==='income';box.style.display=on?'grid':'none';box.querySelectorAll('input').forEach(function(i){ if(['income_head','recurring_amount','recurring_day','recurring_start'].indexOf(i.name)!==-1) i.required=on; });}
function openCustomerModal(){document.getElementById('customerModal').style.display='flex';document.body.style.overflow='hidden'}function closeCustomerModal(){document.getElementById('customerModal').style.display='none';document.body.style.overflow=''}
(function(){var select=document.getElementById('customerCountry');if(!select)return;var fallback=['United Arab Emirates','United States','United Kingdom','Pakistan','Saudi Arabia','Qatar','Oman','Kuwait','Bahrain','Canada','Australia','India','China','Germany','France'];function render(list){select.innerHTML='<option value="">Select country</option>';list.sort().forEach(function(name){var option=document.createElement('option');option.value=name;option.textContent=name;select.appendChild(option)})}fetch('https://restcountries.com/v3.1/all?fields=name').then(function(r){return r.json()}).then(function(rows){render(rows.map(function(row){return row.name.common}))}).catch(function(){render(fallback)})})();
@if($selectedCustomer && $showIncome)
var incomeEntries={!! json_encode($incomeEntries->keyBy('id')->map(fn($e)=>['label'=>$e->period_label,'due'=>(float)$e->amount_due,'paid'=>(float)$e->amount_paid,'paid_at'=>$e->paid_at ? $e->paid_at->format('Y-m-d') : null,'method'=>$e->payment_method,'ref'=>$e->reference,'note'=>$e->note,'due_date'=>$e->due_date->format('Y-m-d')])) !!};
function openPayModal(id){var e=incomeEntries[id];if(!e)return;var f=document.getElementById('incomePayForm');f.action='{{ url('/crm/income/entries') }}/'+id+'/pay';f.reset();document.getElementById('incomePaySub').textContent=e.label+' · due '+e.due.toFixed(2);f.elements.amount_paid.value=(e.paid>0?e.paid:e.due).toFixed(2);f.elements.paid_at.value=e.paid_at||'{{ date('Y-m-d') }}';if(e.method)f.elements.payment_method.value=e.method;f.elements.reference.value=e.ref||'';f.elements.note.value=e.note||'';document.getElementById('incomePayModal').style.display='flex';f.elements.amount_paid.focus();}
function openDueModal(id){var e=incomeEntries[id];if(!e)return;var f=document.getElementById('incomeDueForm');f.action='{{ url('/crm/income/entries') }}/'+id;f.reset();document.getElementById('incomeDueSub').textContent=e.label;f.elements.amount_due.value=e.due.toFixed(2);f.elements.due_date.value=e.due_date;f.elements.note.value=e.note||'';document.getElementById('incomeDueModal').style.display='flex';}
@endif
@if($selectedCustomer && !$showIncome)
var customerSales=@json($sales->getCollection()->keyBy('id'));
function openSaleModal(){var form=document.getElementById('saleForm');form.reset();form.action='{{ route('crm.customer_sales.store') }}';document.getElementById('saleMethod').value='POST';document.getElementById('saleModalTitle').textContent='Add Customer Sale';form.elements.customer_id.value='{{ $selectedCustomer->id }}';form.elements.quantity.required=true;form.elements.order_date.value='{{ date('Y-m-d') }}';form.elements.currency.value='{{ $selectedCustomer->currency }}';document.getElementById('saleModal').style.display='flex';document.body.style.overflow='hidden';calculateSale()}
function closeSaleModal(){document.getElementById('saleModal').style.display='none';document.body.style.overflow=''}
function editSale(id){var data=customerSales[id];if(!data)return;var form=document.getElementById('saleForm');form.reset();form.action='{{ url('/crm/customer-sales') }}/'+id;document.getElementById('saleMethod').value='PUT';document.getElementById('saleModalTitle').textContent='Edit Customer Sale';Object.keys(data).forEach(function(name){var field=form.elements[name];if(!field)return;var value=data[name];if((name==='order_date'||name==='due_date')&&value)value=String(value).substring(0,10);field.value=value===null?'':value});form.elements.quantity.required=!(data.import_source_key && data.quantity===null);document.getElementById('saleModal').style.display='flex';document.body.style.overflow='hidden';calculateSale()}
function calculateSale(){var f=document.getElementById('saleForm');if(!f)return;var n=function(name){return parseFloat(f.elements[name].value)||0},subtotal=n('quantity')*n('unit_price'),total=Math.max(0,subtotal-n('discount_amount')+n('tax_amount')+n('shipping_cost')),paid=n('paid_amount'),balance=Math.max(0,total-paid);document.getElementById('csSubtotal').textContent=subtotal.toFixed(2);document.getElementById('csTotal').textContent=total.toFixed(2);document.getElementById('csBalance').textContent=balance.toFixed(2);document.getElementById('csPaymentStatus').textContent=paid<=0?'Unpaid':(balance>0?'Partial':'Paid')}
document.querySelectorAll('.cs-calc').forEach(function(field){field.addEventListener('input',calculateSale)});
@endif
</script>
@endsection
