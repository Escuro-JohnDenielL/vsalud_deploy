<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Villa Salud</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 30px;">
        <h1 style="color: #2d5016;">Welcome to Villa Salud!</h1>
    </div>

    <p>Hello{{ $data['name'] ? ' ' . $data['name'] : '' }},</p>

    <p>Thank you for choosing Villa Salud. We're delighted to have you with us!</p>

    <p>If you have any questions or need assistance, feel free to reach out to our team.</p>

    <p>We look forward to serving you!</p>

    <hr style="border: 1px solid #eee; margin: 30px 0;">

    <p style="color: #888; font-size: 12px; text-align: center;">
        Villa Salud<br>
        This is a test email to confirm your mail system is working.
    </p>
</body>
</html>
