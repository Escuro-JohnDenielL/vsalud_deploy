<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Approved</title>
</head>

<body style="margin:0; padding:24px 12px; background-color:#f5f2df; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:16px; line-height:1.5;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f5f2df" style="width:100%; border-collapse:collapse; background-color:#f5f2df; color:#004c3a;"><tr><td align="center" bgcolor="#f5f2df" style="background-color:#f5f2df; color:#004c3a;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="width:100%; max-width:600px; border-collapse:collapse; background-color:#ffffff; color:#004c3a;"><tr><td bgcolor="#ffffff" style="padding:28px; background-color:#ffffff; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:16px; line-height:1.5;">

        <h2 style="margin:0 0 20px; background-color:#ffffff; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:22px; line-height:1.4;">Hi {{ $data['name'] }},</h2>

        <p style="margin:0 0 16px; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            Good news. Your payment has been <strong style="background-color:#e7f1e8; color:#174c39;">approved</strong>.
        </p>

        <h3 style="margin:24px 0 12px; background-color:#ffffff; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:18px; line-height:1.4;">Payment Details</h3>

        <ul style="margin:0 0 16px; padding:0; list-style:none; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            <li style="background-color:#ffffff; color:#004c3a;"><strong>Tracking Code:</strong> {{ $data['tracking_code'] }}</li>
            <li style="background-color:#ffffff; color:#004c3a;"><strong>Reservation Code:</strong> {{ $data['reservation_code'] }}</li>
            <li style="background-color:#ffffff; color:#004c3a;"><strong>Payment Type:</strong> {{ $data['payment_type'] }}</li>
            <li style="background-color:#ffffff; color:#004c3a;"><strong>Payment Method:</strong> {{ $data['payment_method'] }}</li>
        </ul>

        <p style="margin:24px 0 16px; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            Thank you for settling your payment. If you have any questions, feel free to reply to this email or contact us directly.
        </p>

        <p style="margin:24px 0 0; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            Warm regards,<br>
            <strong>Events Team</strong>
        </p>
        </td></tr></table>
    </td></tr></table>
</body>
</html>
