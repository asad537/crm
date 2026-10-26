@php
    $companyName = $meta['isAlMassa'] ? 'AL MASSA AL MALAKIYA BOXES AND PACKING IND. LLC' : 'MY BOX PRINTING';
    $documentTitle = 'PAYSLIP';
    $brand = $meta['isAlMassa'] ? '#f45a24' : '#6c5ce7';
    $fmt = fn ($v) => number_format((float) $v, 2);
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.') ?: '0';
    $employee = $line->employee;
    $name = ucwords($line->employee_name);
    $periodStart = $run->periodStart();
    $periodEnd = $periodStart->copy()->endOfMonth();

    // Standard rows on both sides so the slip always has the same shape.
    $earnings = [
        ['Basic Salary' . ($line->isDaily() ? ' (' . $num($line->days_worked) . ' days × ' . $fmt($line->basic_rate) . ')' : ''), $line->basicPay()],
        ['Allowances', (float) $line->allowances],
        ['Overtime' . ((float) $line->overtime_hours > 0 ? ' (' . $num($line->overtime_hours) . ' hrs × ' . $fmt($line->overtime_rate) . ')' : ''), $line->overtimeAmount()],
        ['Bonus', (float) $line->bonus],
    ];
    $deductions = [
        ['Absence' . ((float) $line->absent_days > 0 ? ' (' . $num($line->absent_days) . ' day' . ((float) $line->absent_days == 1 ? '' : 's') . ')' : ''), (float) $line->absence_deduction],
        ['Advance Recovery', (float) $line->advance_deduction],
        ['Other Deductions', (float) $line->other_deductions],
    ];
    $rows = max(count($earnings), count($deductions));
    $words = $line->netInWords();
    $bank = $employee ? trim(($employee->bank_name ?: '') . ($employee->iban ? ' · ' . $employee->iban : ''), ' ·') : '';
@endphp
<!doctype html>
<html><head><meta charset="utf-8"><title>Payslip — {{ $name }} — {{ $run->periodLabel() }}</title>
@include('crm.general_ledger._pdf_styles')
<style>
    body{font-size:9px;color:#1f2937}
    /* Portrait letterhead: keep the company name on one line. */
    .logo-cell{width:90px}.logo{width:76px;max-height:76px}.company-name{font-size:11.5px;letter-spacing:0}.document-cell{width:105px;padding-top:28px!important;font-size:19px;letter-spacing:1px}
    .letter-rule{margin:8px 0 16px}

    .slip-head{width:100%;border-collapse:collapse;margin-bottom:14px}
    .slip-head td{vertical-align:middle;padding:0}
    .slip-title{font-size:15px;font-weight:bold;color:#06265e;letter-spacing:.2px}
    .slip-sub{margin-top:3px;color:#6b7280;font-size:8.5px}
    .pill{display:inline-block;padding:4px 11px;border-radius:10px;font-size:7.5px;font-weight:bold;letter-spacing:.6px}
    .pill.paid{background:#e7f6ee;color:#047857}.pill.unpaid{background:#fff4e5;color:#b45309}

    .section-label{margin:0 0 5px;color:#06265e;font-size:7.5px;font-weight:bold;text-transform:uppercase;letter-spacing:.8px}
    .details{width:100%;border-collapse:collapse;margin-bottom:16px;border:1px solid #e3e8ef}
    .details td{padding:7px 10px;border-bottom:1px solid #eef1f5;vertical-align:top}
    .details tr:last-child td{border-bottom:none}
    .details .k{width:17%;color:#7b8798;font-size:7.3px;font-weight:bold;text-transform:uppercase;letter-spacing:.3px}
    .details .v{width:33%;color:#111827;font-weight:bold}
    .details .split{border-left:1px solid #e3e8ef}

    .pay{width:100%;border-collapse:collapse;border:1px solid #d7dee8}
    .pay th{background:#06265e;color:#fff;padding:8px 10px;font-size:7.5px;font-weight:bold;text-transform:uppercase;letter-spacing:.5px;text-align:left}
    .pay td{padding:7px 10px;border-bottom:1px solid #eef1f5;color:#1f2937}
    .pay .r{text-align:right;white-space:nowrap}
    .pay .mid{border-left:1px solid #d7dee8}
    .pay .zero{color:#9aa4b2}
    .pay .tot td{background:#f3f6fb;font-weight:bold;color:#06265e;border-top:1px solid #c9d2df;border-bottom:none}

    .net{width:100%;border-collapse:collapse;margin-top:14px}
    .net td{padding:11px 14px;background:#f3f6fb;vertical-align:middle}
    .net .bar{width:4px;padding:0;background:{{ $brand }}}
    .net .k{font-size:9.5px;font-weight:bold;color:#06265e;text-transform:uppercase;letter-spacing:.6px}
    .net .w{margin-top:3px;color:#4b5563;font-size:8px;font-style:italic}
    .net .v{text-align:right;font-size:17px;font-weight:bold;color:#06265e;white-space:nowrap}

    .remarks{margin-top:10px;color:#4b5563;font-size:8.5px}
    .signs{width:100%;border-collapse:collapse;margin-top:54px}
    .signs td{width:33.33%;padding:0 14px;text-align:center;color:#4b5563;font-size:8px}
    .signs .line{border-top:1px solid #6b7280;padding-top:6px}
    .note{margin-top:22px;text-align:center;color:#9aa4b2;font-size:7px}
</style>
</head><body>
@include('crm.general_ledger._pdf_letterhead')

<table class="slip-head"><tr>
    <td>
        <div class="slip-title">Salary Slip — {{ $run->periodLabel() }}</div>
        <div class="slip-sub">Pay period {{ $periodStart->format('d M Y') }} – {{ $periodEnd->format('d M Y') }}</div>
    </td>
    <td style="text-align:right">
        @if($line->paid_at)
            <span class="pill paid">PAID</span>
            <div class="slip-sub">on {{ $line->paid_at->format('d M Y') }} · {{ $line->paid_method }}</div>
        @else
            <span class="pill unpaid">NOT PAID YET</span>
        @endif
    </td>
</tr></table>

<div class="section-label">Employee Details</div>
<table class="details">
    <tr><td class="k">Employee Name</td><td class="v">{{ $name }}</td>
        <td class="k split">Employee Code</td><td class="v">{{ optional($employee)->employee_code ?: '—' }}</td></tr>
    <tr><td class="k">Designation</td><td class="v">{{ $line->designation ? ucwords($line->designation) : '—' }}</td>
        <td class="k split">Department</td><td class="v">{{ $line->department ?: '—' }}</td></tr>
    <tr><td class="k">Salary Type</td><td class="v">{{ $line->isDaily() ? 'Daily Wage' : 'Monthly' }}</td>
        <td class="k split">{{ $line->isDaily() ? 'Days Worked' : 'Absent Days' }}</td><td class="v">{{ $num($line->isDaily() ? $line->days_worked : $line->absent_days) }}</td></tr>
    <tr><td class="k">Payment Mode</td><td class="v">{{ $line->paid_method ?: $line->payment_method ?: '—' }}</td>
        <td class="k split">Bank / IBAN</td><td class="v">{{ $bank ?: '—' }}</td></tr>
</table>

<table class="pay">
    <thead><tr>
        <th style="width:32%">Earnings</th><th class="r" style="width:18%">Amount (AED)</th>
        <th class="mid" style="width:32%">Deductions</th><th class="r" style="width:18%">Amount (AED)</th>
    </tr></thead>
    <tbody>
    @for($i = 0; $i < $rows; $i++)
        @php [$eLabel, $eAmount] = $earnings[$i] ?? ['', null]; [$dLabel, $dAmount] = $deductions[$i] ?? ['', null]; @endphp
        <tr>
            <td>{{ $eLabel }}</td><td class="r {{ $eAmount !== null && $eAmount == 0 ? 'zero' : '' }}">{{ $eAmount !== null ? $fmt($eAmount) : '' }}</td>
            <td class="mid">{{ $dLabel }}</td><td class="r {{ $dAmount !== null && $dAmount == 0 ? 'zero' : '' }}">{{ $dAmount !== null ? $fmt($dAmount) : '' }}</td>
        </tr>
    @endfor
    <tr class="tot">
        <td>Gross Earnings</td><td class="r">{{ $fmt($line->gross) }}</td>
        <td class="mid">Total Deductions</td><td class="r">{{ $fmt($line->totalDeductions()) }}</td>
    </tr>
    </tbody>
</table>

<table class="net"><tr>
    <td class="bar"></td>
    <td>
        <div class="k">Net Salary Payable</div>
        @if($words)<div class="w">{{ $words }}</div>@endif
    </td>
    <td class="v">AED {{ $fmt($line->net) }}</td>
</tr></table>

@if($line->note || $line->paid_reference)
    <div class="remarks">
        @if($line->note)<b>Remarks:</b> {{ $line->note }}@endif
        @if($line->note && $line->paid_reference) &nbsp;·&nbsp; @endif
        @if($line->paid_reference)<b>Payment reference:</b> {{ $line->paid_reference }}@endif
    </div>
@endif

<table class="signs"><tr>
    <td><div class="line">Prepared By</div></td>
    <td><div class="line">Approved By</div></td>
    <td><div class="line">Employee Signature</div></td>
</tr></table>

<div class="note">This payslip is confidential and intended only for the named employee.</div>
<div class="footer">{{ $companyName }} · Payslip {{ $run->periodLabel() }}</div>
</body></html>
