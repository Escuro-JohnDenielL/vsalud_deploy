<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Cancellation Request Update</title>
</head>

<body style="margin:0; padding:24px 12px; background-color:#f5f2df; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:16px; line-height:1.5;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f5f2df" style="width:100%; border-collapse:collapse; background-color:#f5f2df; color:#004c3a;">
        <tr><td align="center" bgcolor="#f5f2df" style="background-color:#f5f2df; color:#004c3a;">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="width:100%; max-width:600px; border-collapse:collapse; background-color:#ffffff; color:#004c3a;">
                <tr><td bgcolor="#ffffff" style="padding:28px; background-color:#ffffff; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:16px; line-height:1.5;">

        <h2 style="margin:0 0 20px; background-color:#ffffff; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:22px; line-height:1.4;">Hi {{ $data['name'] }},</h2>

        <p style="margin:0 0 16px; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            After reviewing your request, we regret to inform you that your cancellation request has been <strong style="background-color:#f6e8e6; color:#743232;">denied</strong>.
        </p>

        <h3 style="margin:24px 0 12px; background-color:#ffffff; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:18px; line-height:1.4;">Reservation Details</h3>

        <ul style="margin:0 0 16px; padding:0; list-style:none; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            <li style="background-color:#ffffff; color:#004c3a;"> <strong>Reservation Code:</strong> {{ $data['tracking_code'] }}</li>
            <li style="background-color:#ffffff; color:#004c3a;"> <strong>Date:</strong> {{ $data['date'] }}</li>
            <li style="background-color:#ffffff; color:#004c3a;"> <strong>Time:</strong> {{ $data['time'] }}</li>
            <li style="background-color:#ffffff; color:#004c3a;"> <strong>Venue:</strong> {{ $data['venue'] }}</li>
            <li style="background-color:#ffffff; color:#004c3a;"> <strong>Event Type:</strong> {{ $data['event_type'] }}</li>
            <li style="background-color:#ffffff; color:#004c3a;"> <strong>Theme/Motif:</strong> {{ $data['theme_motif'] }}</li>
        </ul>

        @if($data['admin_note'] ?? null)
            <h3 style="margin:24px 0 12px; background-color:#ffffff; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:18px; line-height:1.4;">Reason</h3>
            <p style="margin:0 0 16px; padding:15px; background-color:#fff2d1; color:#634800; font-size:16px; line-height:1.5;">
                {{ $data['admin_note'] }}
            </p>
        @endif

        <p style="margin:24px 0 16px; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            If you have any questions or would like to discuss this further, please feel free to reply to this email or contact us directly.
        </p>

        <p style="margin:24px 0 0; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            Warm regards,<br>
            <strong>Events Team</strong>
        </p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
