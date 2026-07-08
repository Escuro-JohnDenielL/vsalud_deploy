<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Reminder</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 30px; color: #333;">

    <div
        style="max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">

        <h2 style="color: #2c3e50;">Hi {{ $data['name'] }},</h2>

        <p style="font-size: 16px;">
            This is a reminder that the payment for your reservation is still <strong style="color: #e74c3c;">pending</strong>. 💳
        </p>

        <p style="font-size: 16px;">
            Please settle your payment at your earliest convenience to secure your booking. You may upload your proof of payment through our payment portal:
        </p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ url('/payment') }}"
               style="background-color: #3498db; color: white; padding: 14px 32px; text-decoration: none; border-radius: 6px; font-size: 16px; font-weight: bold;">
                Go to Payment Portal
            </a>
        </div>

        <h3 style="color: #3498db; margin-top: 30px;">📋 Reservation Summary</h3>

        <ul style="line-height: 1.8; list-style: none; padding: 0;">
            <li>🔖 <strong>Reservation Code:</strong> {{ $data['tracking_code'] }}</li>
            <li>📅 <strong>Date:</strong> {{ $data['date'] }}</li>
            <li>⏰ <strong>Time:</strong> {{ $data['time'] }}</li>
            <li>🏛️ <strong>Venue:</strong> {{ $data['venue'] }}</li>
            <li>🎉 <strong>Event Type:</strong> {{ $data['event_type'] }}</li>
            <li>🎨 <strong>Theme/Motif:</strong> {{ $data['theme_motif'] }}</li>
        </ul>

        <div style="background-color: #fdf2f2; border-left: 4px solid #e74c3c; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px; color: #721c24;">
                ⚠️ <strong>Note:</strong> If payment is not received, your reservation may be at risk. If you've already made a payment, please disregard this message.
            </p>
        </div>

        <p style="font-size: 16px;">
            If you have any questions or need assistance, simply reply to this email.
        </p>

        <p style="margin-top: 30px;">
            Warm regards,<br>
            <strong>Events Team</strong>
        </p>
    </div>

</body>
</html>
