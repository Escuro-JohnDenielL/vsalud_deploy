<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Password Reset Code</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 30px; color: #333;">

    <div
        style="max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">

        <h2 style="color: #2c3e50;">Hi {{ $data['name'] }},</h2>

        <p style="font-size: 16px;">
            We received a request to change your password for your Villa Salud account.
        </p>

        <p style="font-size: 16px;">
            Use the verification code below to proceed with changing your password. This code will expire in <strong>10 minutes</strong>.
        </p>

        <div style="text-align: center; margin: 30px 0;">
            <div style="display: inline-block; background-color: #f0f4f8; padding: 20px 40px; border-radius: 8px; letter-spacing: 8px; font-size: 32px; font-weight: bold; color: #0d7a3e;">
                {{ $data['code'] }}
            </div>
        </div>

        <p style="font-size: 16px; color: #6b7280;">
            If you did not request a password change, please ignore this email or contact support immediately.
        </p>

        <div style="background-color: #fdf2f2; border-left: 4px solid #e74c3c; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; font-size: 14px; color: #721c24;">
                ⚠️ <strong>Security Notice:</strong> Never share this code with anyone. Our team will never ask for your verification code.
            </p>
        </div>

        <p style="margin-top: 30px;">
            Warm regards,<br>
            <strong>Events Team</strong>
        </p>
    </div>

</body>
</html>
