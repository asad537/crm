<!DOCTYPE html>
<html>

<head>
    <title>Cancellation Notification</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div
        style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-top: 4px solid #d9534f;">
        <h2 style="color: #d9534f; margin-top: 0;">Request Cancelled - {{ $data['order_no'] }}</h2>

        <p>A <strong>{{ $data['type'] }}</strong> has been cancelled by the customer.</p>

        <div style="background-color: #f9f9f9; padding: 15px; margin: 20px 0; border-left: 4px solid #d9534f;">
            <p style="margin: 0 0 10px 0;"><strong>Reference Number:</strong> {{ $data['order_no'] }}</p>
            <p style="margin: 0 0 10px 0;"><strong>Type:</strong> {{ $data['type'] }}</p>
            <p style="margin: 0 0 10px 0;"><strong>Customer Name:</strong> {{ $data['customer_name'] }}</p>
            <p style="margin: 0;"><strong>Customer Email:</strong> {{ $data['customer_email'] }}</p>
        </div>

        <p>Please check the admin panel for more details.</p>

        <hr style="border: 0; border-top: 1px solid #ddd; margin: 20px 0;">
        <p style="font-size: 12px; color: #777; text-align: center;">This is an automated message from the application.
        </p>
    </div>
</body>

</html>