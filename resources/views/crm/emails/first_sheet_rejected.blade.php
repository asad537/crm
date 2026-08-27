<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 20px; color: #334155; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .header { background: #ef4444; color: #ffffff; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 700; }
        .content { padding: 30px; }
        .info-box { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 6px; padding: 15px; margin-bottom: 20px; }
        .info-item { margin-bottom: 8px; font-size: 14px; }
        .info-item strong { color: #1e293b; }
        .notes-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; margin-top: 10px; font-style: italic; color: #475569; }
        .footer { padding: 20px; text-align: center; color: #94a3b8; font-size: 12px; border-top: 1px solid #f1f5f9; }
        .btn { display: inline-block; background-color: #ef4444; color: #ffffff; text-decoration: none; padding: 10px 20px; border-radius: 5px; font-weight: 600; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>First Sheet Rejected</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>The QC Inspector has rejected the first sheet for <strong>Job #{{ $job->id }}</strong>. The job has been sent back to the press operator for adjustments.</p>
            
            <div class="info-box">
                <div class="info-item"><strong>Job ID:</strong> {{ $job->id }}</div>
                <div class="info-item"><strong>Product:</strong> {{ $job->salesOrder->lead->product_name ?? 'N/A' }}</div>
                <div class="info-item"><strong>Client:</strong> {{ $job->salesOrder->lead->client_name ?? 'N/A' }}</div>
                <div class="info-item"><strong>Facility:</strong> {{ $job->facility->city ?? 'N/A' }}</div>
                <div class="info-item"><strong>Press Operator:</strong> {{ $job->operator->name ?? 'N/A' }}</div>
            </div>

            <p><strong>QC Rejection Notes:</strong></p>
            <div class="notes-box">
                {!! nl2br(e($notes)) !!}
            </div>

            <center>
                <a href="{{ route('crm.production_jobs.show', $job->id) }}" class="btn">View Ticket Details</a>
            </center>
        </div>
        <div class="footer">
            This is an automated notification from your CRM system.
        </div>
    </div>
</body>
</html>
