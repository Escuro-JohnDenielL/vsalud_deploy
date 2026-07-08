<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Upcoming Reservation Reminder</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 30px; color: #333;">

    <div
        style="max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">

        <h2 style="color: #2c3e50;">Hi {{ $data['name'] }},</h2>

        <p style="font-size: 16px;">
            This is a friendly reminder that your upcoming reservation is just <strong>one week away</strong>! 🗓️
        </p>

        <h3 style="color: #3498db; margin-top: 30px;">📋 Event Details</h3>

        <ul style="line-height: 1.8; list-style: none; padding: 0;">
            <li>🔖 <strong>Reservation Code:</strong> {{ $data['tracking_code'] }}</li>
            <li>📅 <strong>Date:</strong> {{ $data['date'] }}</li>
            <li>⏰ <strong>Time:</strong> {{ $data['time'] }}</li>
            <li>🏛️ <strong>Venue:</strong> {{ $data['venue'] }}</li>
            <li>🎉 <strong>Event Type:</strong> {{ $data['event_type'] }}</li>
            <li>🎨 <strong>Theme/Motif:</strong> {{ $data['theme_motif'] }}</li>
        </ul>

        <div style="background-color: #fff8e1; border-left: 4px solid #f39c12; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px; color: #856404;">
                💡 <strong>Reminder:</strong> If you need to make any changes or have questions about your booking, please don't hesitate to reply to this email or contact us directly.
            </p>
        </div>

        <p style="font-size: 16px;">
            We look forward to making your event special! 🎊
        </p>

        <p style="margin-top: 30px;">
            Warm regards,<br>
            <strong>Events Team</strong>
        </p>
    </div>

</body>
</html>
