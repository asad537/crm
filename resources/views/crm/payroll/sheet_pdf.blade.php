@php
    $companyName = $meta['isAlMassa'] ? 'AL MASSA AL MALAKIYA BOXES AND PACKING IND. LLC' : 'MY BOX PRINTING';
    $documentTitle = 'SALARY SHEET';
    $fmt = fn ($v) => number_format((float) $v, 2);
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
    $lines = $run->lines;
@endphp
<!doctype html>
<html><head><meta charset="utf-8"><title>Salary Sheet — {{ $run->periodLabel() }}</title>
@include('crm.general_ledger._pdf_styles')
<style>.signs{width:100%;border-collapse:separate;border-spacing:40px 0;margin:40px -40px 0}.signs td{width:33.33%;padding-top:6px;border-top:1px solid #64748b;color:#475569;text-align:center;font-size:8px}</style>
</head><body>
@include('crm.general_ledger._pdf_letterhead')
<div class="report-head">
    <div class="report-head-copy"><strong>Salary Sheet — {{ $run->periodLabel() }}</strong><span>Status: {{ \App\CrmPayrollRun::STATUSES[$run->status] }}{{ $run->approved_at ? ' · approved by ' . optional($run->approver)->name . ' on ' . $run->approved_at->format('d M Y') : '' }}</span></div>
    <div class="report-head-meta">Generated {{ $meta['generatedAt'] }}<br>{{ $lines->count() }} employee(s)</div>
</div>
<table class="summary"><tr>
    <td><span class="label">Gross</span><span class="value">AED {{ $fmt($lines->sum('gross')) }}</span></td>
    <td class="balance"><span class="label">Deductions</span><span class="value">AED {{ $fmt($lines->sum(fn ($l) => $l->totalDeductions())) }}</span></td>
    <td class="paid"><span class="label">Net Salary</span><span class="value">AED {{ $fmt($lines->sum('net')) }}</span></td>
</tr></table>
<table class="data">
    <thead><tr>
        <th style="width:3%">#</th><th style="width:17%;text-align:left">Employee</th><th style="width:8%">Days</th>
        <th class="num" style="width:9%">Basic Pay</th><th class="num" style="width:8%">Allowances</th><th class="num" style="width:8%">Overtime</th><th class="num" style="width:7%">Bonus</th>
        <th class="num" style="width:9%">Gross</th><th class="num" style="width:9%">Deductions</th><th class="num" style="width:9%">Net</th><th style="width:13%">Payment</th>
    </tr></thead>
    <tbody>
    @foreach($lines as $i => $line)
        <tr>
            <td class="center">{{ $i + 1 }}</td>
            <td><strong>{{ $line->employee_name }}</strong>@if($line->designation)<br><span style="color:#6f7d90">{{ $line->designation }}</span>@endif</td>
            <td class="center">{{ $line->isDaily() ? $num($line->days_worked) . ' worked' : ((float) $line->absent_days > 0 ? $num($line->absent_days) . ' absent' : 'Full') }}</td>
            <td class="num">{{ $fmt($line->basicPay()) }}</td>
            <td class="num">{{ $fmt($line->allowances) }}</td>
            <td class="num">{{ $fmt($line->overtimeAmount()) }}</td>
            <td class="num">{{ $fmt($line->bonus) }}</td>
            <td class="num">{{ $fmt($line->gross) }}</td>
            <td class="num due">{{ $fmt($line->totalDeductions()) }}</td>
            <td class="num"><strong>{{ $fmt($line->net) }}</strong></td>
            <td class="center">@if($line->paid_at)<span class="credit">Paid {{ $line->paid_at->format('d M') }}</span><br>{{ $line->paid_method }}@else{{ $line->payment_method }}@endif</td>
        </tr>
    @endforeach
    <tr class="tot">
        <td colspan="3">TOTAL</td>
        <td class="num">{{ $fmt($lines->sum(fn ($l) => $l->basicPay())) }}</td>
        <td class="num">{{ $fmt($lines->sum('allowances')) }}</td>
        <td class="num">{{ $fmt($lines->sum(fn ($l) => $l->overtimeAmount())) }}</td>
        <td class="num">{{ $fmt($lines->sum('bonus')) }}</td>
        <td class="num">{{ $fmt($lines->sum('gross')) }}</td>
        <td class="num">{{ $fmt($lines->sum(fn ($l) => $l->totalDeductions())) }}</td>
        <td class="num">{{ $fmt($lines->sum('net')) }}</td>
        <td></td>
    </tr>
    </tbody>
</table>
<table class="signs"><tr><td>Prepared By</td><td>Checked By (Accounts)</td><td>Approved By</td></tr></table>
<div class="footer">Confidential salary sheet - {{ $companyName }}</div>
</body></html>
