<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Update</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 30px; color: #333;">

    <div
        style="max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">

        <h2 style="color: #2c3e50;">Hi {{ $data['name'] }},</h2>

        <p style="font-size: 16px;">
            After reviewing the payment you submitted, we regret to inform you that it has been <strong style="color: #e74c3c;">rejected</strong>.
        </p>

        <h3 style="color: #3498db; margin-top: 30px;">📋 Payment Details</h3>

        <ul style="line-height: 1.8; list-style: none; padding: 0;">
            <li>🔖 <strong>Tracking Code:</strong> {{ $data['tracking_code'] }}</li>
            <li>📄 <strong>Reservation Code:</strong> {{ $data['reservation_code'] }}</li>
            <li>💳 <strong>Payment Type:</strong> {{ $data['payment_type'] }}</li>
            <li>🏦 <strong>Payment Method:</strong> {{ $data['payment_method'] }}</li>
        </ul>

        {{-- The admin note is escaped by Blade because it uses the double brace syntax --}}
        @if($data['admin_note'] ?? null)
            <h3 style="color: #e67e22; margin-top: 30px;">📝 Reason</h3>
            <p style="font-size: 16px; background: #fff8f0; padding: 15px; border-radius: 6px;">
                {{ $data['admin_note'] }}
            </p>
        @endif

        <p style="font-size: 16px; margin-top: 30px;">
            If you have any questions or would like to discuss this further, please feel free to reply to this email or contact us directly.
        </p>

        <p style="margin-top: 30px;">
            Warm regards,<br>
            <strong>Events Team</strong>
        </p>
    </div>

</body>
</html>
