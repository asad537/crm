<!DOCTYPE html><html><head><meta charset="utf-8"></head>
<body style="font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;background:#f4f5f9;margin:0;padding:20px;color:#1e293b;">
<table width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" border="0" style="background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e2e8f0;">
    <tr><td height="4" style="background:#6c5ce7;"></td></tr>
    <tr><td style="padding:24px 36px;border-bottom:1px solid #f1f5f9;">
        <div style="font-size:10px;font-weight:800;color:#6c5ce7;text-transform:uppercase;letter-spacing:.1em;">Print Ready</div>
        <div style="font-size:20px;font-weight:800;color:#0f172a;margin-top:2px;">{{ $title }}</div>
        <div style="font-size:12px;color:#94a3b8;margin-top:4px;">{{ $ticket->ticket_number }} · Job {{ $ticket->job_number }}{{ $actor ? ' · by '.$actor->name : '' }}</div>
    </td></tr>
    <tr><td style="padding:22px 36px;font-size:14px;line-height:1.7;color:#334155;">
        @if($event === 'created')
            <p style="margin:0 0 12px;">A paid order has been sent to production and needs print-ready artwork. Pick it up from <strong>Active Tickets</strong> in the Print Ready tab.</p>
        @elseif($event === 'completed')
            <p style="margin:0 0 12px;">The designer has marked this ticket as completed. Print-ready files are attached to the ticket in the CRM.</p>
        @else
            <p style="margin:0 0 12px;">A change has been requested on this ticket. Please review the note and update the artwork.</p>
            @if($ticket->change_request_note)<div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:10px 14px;color:#7c2d12;margin-bottom:12px;">{{ $ticket->change_request_note }}</div>@endif
        @endif
        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin:0 0 16px;"><tr><td style="padding:12px 16px;font-size:13px;line-height:1.9;">
            <div><span style="color:#64748b;display:inline-block;width:150px;">Client</span><strong>{{ $ticket->client_name }}</strong></div>
            <div><span style="color:#64748b;display:inline-block;width:150px;">Product(s)</span>{{ $products ?: '—' }}</div>
            <div><span style="color:#64748b;display:inline-block;width:150px;">Printer's deadline</span>{{ optional($ticket->printers_deadline)->format('d M Y') ?: '—' }}</div>
            <div><span style="color:#64748b;display:inline-block;width:150px;">Designer</span>{{ optional($ticket->designer)->name ?: 'Not assigned yet' }}</div>
            @if($ticket->folder_path)<div><span style="color:#64748b;display:inline-block;width:150px;">Folder path</span><code style="font-size:12px">{{ $ticket->folder_path }}</code></div>@endif
        </td></tr></table>
        <table cellpadding="0" cellspacing="0" border="0"><tr><td style="background:#6c5ce7;border-radius:8px;"><a href="{{ $url }}" style="display:inline-block;padding:11px 22px;color:#fff;font-weight:700;text-decoration:none;font-size:14px;">Open ticket</a></td></tr></table>
    </td></tr>
</table></td></tr></table></body></html>
