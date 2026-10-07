@php $label = $brief->job_number; $url = route('crm.orders.manual.production.show', $order->id); @endphp
<!DOCTYPE html><html><head><meta charset="utf-8"></head>
<body style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;background:#f4f5f9;margin:0;padding:20px;color:#1e293b;">
<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
    <tr><td height="4" style="background:#376094;"></td></tr>
    <tr><td style="padding:24px 36px;border-bottom:1px solid #f1f5f9;">
        <div style="font-size:10px;font-weight:800;color:#376094;text-transform:uppercase;letter-spacing:.1em;">New Production Job</div>
        <div style="font-size:20px;font-weight:800;color:#0f172a;margin-top:2px;">{{ $label }}</div>
        <div style="font-size:12px;color:#94a3b8;margin-top:4px;">Sent by {{ $agentUser->name ?? 'CRM' }} on {{ optional($brief->sent_at)->format('d M Y H:i') }}</div>
    </td></tr>
    <tr><td style="padding:22px 36px;font-size:14px;line-height:1.7;color:#334155;">
        <p style="margin:0 0 12px;">A paid order has been sent to production. The full job briefing is attached as a PDF and available in the CRM.</p>
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin:0 0 16px;"><tr><td style="padding:12px 16px;font-size:13px;line-height:1.9;">
            <div><span style="color:#64748b;display:inline-block;width:150px;">Client</span><strong>{{ $brief->client_name }}</strong></div>
            <div><span style="color:#64748b;display:inline-block;width:150px;">Product(s)</span><strong>{{ $products ?: '—' }}</strong></div>
            <div><span style="color:#64748b;display:inline-block;width:150px;">Production type</span>{{ $brief->productionTypeLabel() ?: '—' }}</div>
            <div><span style="color:#64748b;display:inline-block;width:150px;">Job type</span>{{ $brief->job_type ?: '—' }}</div>
            <div><span style="color:#64748b;display:inline-block;width:150px;">Printer's deadline</span>{{ optional($brief->printers_deadline)->format('d M Y') ?: '—' }}</div>
            <div><span style="color:#64748b;display:inline-block;width:150px;">Client's deadline</span>{{ optional($brief->clients_deadline)->format('d M Y') ?: '—' }}</div>
            @if($brief->folder_path)<div><span style="color:#64748b;display:inline-block;width:150px;">Folder path</span><code style="font-size:12px">{{ $brief->folder_path }}</code></div>@endif
        </td></tr></table>
        <table cellpadding="0" cellspacing="0" border="0"><tr><td style="background:#376094;border-radius:8px;"><a href="{{ $url }}" style="display:inline-block;padding:11px 22px;color:#fff;font-weight:700;text-decoration:none;font-size:14px;">Open job in CRM</a></td></tr></table>
    </td></tr>
</table></td></tr></table></body></html>
