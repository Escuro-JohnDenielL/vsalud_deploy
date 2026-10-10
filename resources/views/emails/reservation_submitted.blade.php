<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reservation Confirmation</title>
</head>

<body style="margin:0; padding:24px 12px; background-color:#f5f2df; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:16px; line-height:1.5;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f5f2df" style="width:100%; border-collapse:collapse; background-color:#f5f2df; color:#004c3a;"><tr><td align="center" bgcolor="#f5f2df" style="background-color:#f5f2df; color:#004c3a;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="width:100%; max-width:600px; border-collapse:collapse; background-color:#ffffff; color:#004c3a;"><tr><td bgcolor="#ffffff" style="padding:28px; background-color:#ffffff; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:16px; line-height:1.5;">

        <h2 style="margin:0 0 20px; background-color:#ffffff; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:22px; line-height:1.4;">Hi {{ $data['name'] }},</h2>

        <p style="margin:0 0 16px; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            Thank you for reaching out to us!<br>
            We have successfully received your reservation request. Below are the details you submitted:
        </p>

        <h3 style="margin:24px 0 12px; background-color:#ffffff; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:18px; line-height:1.4;">Reservation Details</h3>

        <ul style="margin:0 0 16px; padding:0; list-style:none; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            <li style="background-color:#ffffff; color:#004c3a;"><strong>Reservation Code:</strong> {{ $data['tracking_code'] }}</li>
            <li style="background-color:#ffffff; color:#004c3a;"><strong>Date:</strong> {{ $data['date'] }}</li>
            <li style="background-color:#ffffff; color:#004c3a;"><strong>Time:</strong> {{ $data['time'] }}</li>
            <li style="background-color:#ffffff; color:#004c3a;"><strong>Venue:</strong> {{ $data['venue'] }} {{ $data['other_venue'] ?? '' }}</li>
            <li style="background-color:#ffffff; color:#004c3a;"><strong>Event Type:</strong> {{ $data['event_type'] }} {{ $data['other_event_type'] ?? '' }}</li>
            <li style="background-color:#ffffff; color:#004c3a;"><strong>Theme/Motif:</strong> {{ $data['theme_motif'] }} {{ $data['other_theme_motif'] ?? '' }}</li>
            <li style="background-color:#ffffff; color:#004c3a;"><strong>Special Request:</strong> {{ $data['message'] ?: 'None' }}</li>
        </ul>

        <p style="margin:24px 0 16px; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            We'll review your request and get back to you shortly with confirmation and next steps.
        </p>

        <p style="margin:0 0 16px; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            If you have any questions or need to make changes, feel free to reply to this email.
        </p>

        <p style="margin:24px 0 0; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            Warm regards,<br>
            <strong>Events Team</strong><br><br>
            {{-- <strong>Phone:</strong> {{ payment_setting('contact_phone', '(+63) 912 345 6789') }}<br> --}}
            {{-- <strong>Email:</strong> <a href="mailto:{{ payment_setting('contact_email', 'coheredit@gmail.com') }}"
                style="color: #3498db;">{{ payment_setting('contact_email', 'coheredit@gmail.com') }}</a> --}}
        </p>
        </td></tr></table>
    </td></tr></table>

</body>

</html>
