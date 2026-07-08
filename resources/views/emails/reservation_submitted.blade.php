<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reservation Confirmation</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 30px; color: #333;">

    <div
        style="max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">

        <h2 style="color: #2c3e50;">👋 Hi {{ $data['name'] }},</h2>

        <p style="font-size: 16px;">
            Thank you for reaching out to us! 🎉<br>
            We have successfully received your reservation request. Below are the details you submitted:
        </p>

        <h3 style="color: #3498db; margin-top: 30px;">📋 Reservation Details</h3>

        <ul style="line-height: 1.8; list-style: none; padding: 0;">
            <li>🔖 <strong>Reservation Code:</strong> {{ $data['tracking_code'] }}</li>
            <li>📅 <strong>Date:</strong> {{ $data['date'] }}</li>
            <li>⏰ <strong>Time:</strong> {{ $data['time'] }}</li>
            <li>🏛️ <strong>Venue:</strong> {{ $data['venue'] }} {{ $data['other_venue'] ?? '' }}</li>
            <li>🎉 <strong>Event Type:</strong> {{ $data['event_type'] }} {{ $data['other_event_type'] ?? '' }}</li>
            <li>🎨 <strong>Theme/Motif:</strong> {{ $data['theme_motif'] }} {{ $data['other_theme_motif'] ?? '' }}</li>
            <li>📝 <strong>Special Request:</strong> {{ $data['message'] ?: 'None' }}</li>
        </ul>

        <p style="font-size: 16px; margin-top: 30px;">
            We'll review your request and get back to you shortly with confirmation and next steps. 😊
        </p>

        <p style="font-size: 16px;">
            If you have any questions or need to make changes, feel free to reply to this email.
        </p>

        <p style="margin-top: 30px;">
            Warm regards,<br>
            <strong>Events Team</strong><br><br>
            {{-- 📞 <strong>Phone:</strong> {{ payment_setting('contact_phone', '(+63) 912 345 6789') }}<br> --}}
            {{-- 📧 <strong>Email:</strong> <a href="mailto:{{ payment_setting('contact_email', 'coheredit@gmail.com') }}"
                style="color: #3498db;">{{ payment_setting('contact_email', 'coheredit@gmail.com') }}</a> --}}
        </p>
    </div>

</body>

</html>
