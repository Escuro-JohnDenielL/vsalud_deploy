<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reservation Window Expired</title>
</head>

<body style="margin:0; padding:24px 12px; background-color:#f5f2df; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:16px; line-height:1.5;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f5f2df" style="width:100%; border-collapse:collapse; background-color:#f5f2df; color:#004c3a;"><tr><td align="center" bgcolor="#f5f2df" style="background-color:#f5f2df; color:#004c3a;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="width:100%; max-width:600px; border-collapse:collapse; background-color:#ffffff; color:#004c3a;"><tr><td bgcolor="#ffffff" style="padding:28px; background-color:#ffffff; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:16px; line-height:1.5;">

        <h2 style="margin:0 0 20px; background-color:#ffffff; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:22px; line-height:1.4;">Hi {{ $data['name'] }},</h2>

        <p style="margin:0 0 16px; padding:12px; background-color:#f6e8e6; color:#743232; font-size:16px; line-height:1.5;">
            Unfortunately, the {{ $data['hours'] ?? 24 }}-hour window to claim your slot for <strong>{{ $data['date'] }}</strong> has passed without a reservation being submitted.
        </p>

        <p style="margin:0 0 16px; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            The slot has been offered to the next person on the waitlist.
        </p>

        <p style="margin:0 0 16px; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            You can still check availability and submit a reservation directly if slots are open:
        </p>

        <table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" style="margin:24px auto; border-collapse:collapse; background-color:#ffffff; color:#004c3a;"><tr><td align="center" bgcolor="#006b53" style="padding:14px 32px; background-color:#006b53; color:#ffffff; font-size:16px; line-height:1.5;"><a href="{{ url('/mreserve') }}" style="display:inline-block; background-color:#006b53; color:#ffffff; font-family:Inter, Arial, sans-serif; font-size:16px; font-weight:bold; line-height:1.5; text-decoration:none;">Make a Reservation</a></td></tr></table>

        <p style="margin:24px 0 16px; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            If you have any questions, feel free to reply to this email.
        </p>

        <p style="margin:24px 0 0; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            Warm regards,<br>
            <strong>Events Team</strong>
        </p>
        </td></tr></table>
    </td></tr></table>
</body>
</html>
