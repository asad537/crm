@extends('crm.layout')
@section('title', 'Budget ' . $year)
@section('content')
@include('crm.general_ledger._styles')
@include('crm.purchase_invoices._styles')
<style>
    .bd-table { width: 100%; border-collapse: collapse; }
    .bd-table th { font-size: .63rem; text-transform: uppercase; letter-spacing: .08em; font-weight: 800; color: var(--muted); text-align: left; padding: .6rem 1rem; background: var(--well); border-bottom: 1px solid var(--line); }
    .bd-table td { padding: .5rem 1rem; border-bottom: 1px solid var(--line-soft); font-size: .86rem; }
    .bd-table .r { text-align: right; font-variant-numeric: tabular-nums; }
    .bd-table input { width: 150px; border: 1px solid var(--line); border-radius: 8px; padding: .45rem .6rem; text-align: right; font-family: inherit; font-size: .86rem; }
    .bd-table input:focus { border-color: var(--brand); outline: none; }
    .bd-month { font-size: .72rem; color: var(--muted); }
    .bd-copy { border: 0; background: none; color: var(--brand); font-weight: 700; font-size: .72rem; cursor: pointer; padding: 0; }
    .bd-total td { font-weight: 800; background: var(--well); }
</style>

<div class="gl-page">
    <div class="gl-head">
        <div class="gl-sub"><strong>Budget {{ $year }}</strong> — a yearly amount for each income and expense category. The Budget vs Actual report compares it with the real Profit &amp; Loss.</div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;align-items:center">
            <a class="pi-btn" href="{{ route('crm.budgets.edit', ['year' => $year - 1]) }}"><i class="fas fa-chevron-left"></i>&nbsp; {{ $year - 1 }}</a>
            <a class="pi-btn" href="{{ route('crm.budgets.edit', ['year' => $year + 1]) }}">{{ $year + 1 }} &nbsp;<i class="fas fa-chevron-right"></i></a>
            <a class="pi-btn" href="{{ route('crm.financial_reports.index', ['report' => 'budget']) }}"><i class="fas fa-bullseye"></i>&nbsp; Budget vs Actual</a>
        </div>
    </div>

    <form method="POST" action="{{ route('crm.budgets.update') }}">
        {{ csrf_field() }}
        <input type="hidden" name="year" value="{{ $year }}">
        <div style="display:flex;justify-content:flex-end;gap:.5rem;margin-bottom:.8rem">
            <button type="button" class="pi-btn" onclick="bdCopyAll()"><i class="fas fa-copy"></i>&nbsp; Fill empty ones from {{ $year - 1 }} actual</button>
            <button type="submit" class="ex-add"><i class="fas fa-check"></i> Save Budget</button>
        </div>
        @foreach($groups as $group => $g)
            <div class="pi-card">
                <div class="pi-card-head">{{ $g['title'] }} <span class="gl-mutedtext" style="margin-left:auto;font-weight:700" id="bdTotal-{{ $group }}"></span></div>
                <table class="bd-table">
                    <thead><tr><th>Category</th><th class="r">{{ $year - 1 }} Actual</th><th class="r">Budget {{ $year }} (year)</th><th class="r">Per Month</th></tr></thead>
                    <tbody>
                    @forelse($g['rows'] as $row)
                        <tr>
                            <td>{{ $row['category'] }}</td>
                            <td class="r">{{ number_format($row['last_year'], 2) }} @if($row['last_year'] > 0)<button type="button" class="bd-copy" data-value="{{ round($row['last_year'], 2) }}" onclick="bdCopy(this)">use</button>@endif</td>
                            <td class="r"><input type="number" step="0.01" min="0" name="budget[{{ $group }}][{{ $row['category'] }}]" value="{{ $row['budget'] }}" data-group="{{ $group }}" data-last="{{ round($row['last_year'], 2) }}" oninput="bdRecalc()" placeholder="0.00"></td>
                            <td class="r bd-month"></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="gl-mutedtext">No categories yet — add one below.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endforeach

        <div class="pi-card">
            <div class="pi-card-head">Add a category not used yet</div>
            <div class="pi-card-body pi-grid">
                <div class="ex-f"><label>Group</label><select name="new_group" style="width:100%;border:1px solid var(--line);border-radius:9px;padding:.55rem">@foreach(\App\CrmBudget::GROUPS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div>
                <div class="ex-f"><label>Category</label><input name="new_category" maxlength="120" placeholder="e.g. Marketing" style="width:100%;border:1px solid var(--line);border-radius:9px;padding:.55rem"></div>
                <div class="ex-f"><label>Budget for the year</label><input type="number" name="new_amount" step="0.01" min="0" style="width:100%;border:1px solid var(--line);border-radius:9px;padding:.55rem"></div>
            </div>
        </div>
        <div class="pi-actions"><button type="submit" class="ex-add"><i class="fas fa-check"></i> Save Budget</button></div>
    </form>
</div>

<script>
    function bdMoney(n) { return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function bdRecalc() {
        var totals = {};
        document.querySelectorAll('.bd-table input[data-group]').forEach(function (input) {
            var v = parseFloat(input.value) || 0;
            totals[input.dataset.group] = (totals[input.dataset.group] || 0) + v;
            input.closest('tr').querySelector('.bd-month').textContent = v ? bdMoney(v / 12) : '';
        });
        Object.keys(totals).forEach(function (g) { var el = document.getElementById('bdTotal-' + g); if (el) el.textContent = 'Total ' + bdMoney(totals[g]); });
    }
    function bdCopy(btn) { var input = btn.closest('tr').querySelector('input'); input.value = btn.dataset.value; bdRecalc(); }
    function bdCopyAll() {
        document.querySelectorAll('.bd-table input[data-group]').forEach(function (input) { if (!input.value && parseFloat(input.dataset.last) > 0) input.value = input.dataset.last; });
        bdRecalc();
    }
    bdRecalc();
</script>
@endsection
