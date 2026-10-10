<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Password Reset Code</title>
</head>

<body style="margin:0; padding:24px 12px; background-color:#f5f2df; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:16px; line-height:1.5;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f5f2df" style="width:100%; border-collapse:collapse; background-color:#f5f2df; color:#004c3a;"><tr><td align="center" bgcolor="#f5f2df" style="background-color:#f5f2df; color:#004c3a;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="width:100%; max-width:600px; border-collapse:collapse; background-color:#ffffff; color:#004c3a;"><tr><td bgcolor="#ffffff" style="padding:28px; background-color:#ffffff; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:16px; line-height:1.5;">

        <h2 style="margin:0 0 20px; background-color:#ffffff; color:#004c3a; font-family:Inter, Arial, sans-serif; font-size:22px; line-height:1.4;">Hi {{ $data['name'] }},</h2>

        <p style="margin:0 0 16px; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            We received a request to change your password for your Villa Salud account.
        </p>

        <p style="margin:0 0 16px; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            Use the verification code below to proceed with changing your password. This code will expire in <strong>10 minutes</strong>.
        </p>

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#e4eff8" style="width:100%; border-collapse:collapse; background-color:#e4eff8; color:#123d67;"><tr><td align="center" bgcolor="#e4eff8" style="padding:20px; background-color:#e4eff8; color:#123d67; font-size:32px; font-weight:bold; letter-spacing:8px; line-height:1.4;">{{ $data['code'] }}</td></tr></table>

        <p style="margin:16px 0; background-color:#ffffff; color:#4b5d55; font-size:16px; line-height:1.5;">
            If you did not request a password change, please ignore this email or contact support immediately.
        </p>

        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f6e8e6" style="width:100%; margin:20px 0; border-collapse:collapse; background-color:#f6e8e6; color:#743232;"><tr><td bgcolor="#f6e8e6" style="padding:15px; background-color:#f6e8e6; color:#743232; font-size:14px; line-height:1.5;"><strong>Security Notice:</strong> Never share this code with anyone. Our team will never ask for your verification code.</td></tr></table>

        <p style="margin:24px 0 0; background-color:#ffffff; color:#004c3a; font-size:16px; line-height:1.5;">
            Warm regards,<br>
            <strong>Events Team</strong>
        </p>
        </td></tr></table>
    </td></tr></table>
</body>
</html>
