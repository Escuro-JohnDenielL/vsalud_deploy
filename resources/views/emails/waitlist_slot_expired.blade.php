<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reservation Window Expired</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 30px; color: #333;">

    <div style="max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">

        <h2 style="color: #2c3e50;">Hi {{ $data['name'] }},</h2>

        <p style="font-size: 16px;">
            Unfortunately, the {{ $data['hours'] ?? 24 }}-hour window to claim your slot for <strong>{{ $data['date'] }}</strong> has passed without a reservation being submitted. 😔
        </p>

        <p style="font-size: 16px;">
            The slot has been offered to the next person on the waitlist.
        </p>

        <p style="font-size: 16px;">
            You can still check availability and submit a reservation directly if slots are open:
        </p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ url('/mreserve') }}"
               style="display: inline-block; background-color: #3498db; color: white; padding: 14px 32px; font-size: 16px; text-decoration: none; border-radius: 6px; font-weight: bold;">
                Make a Reservation
            </a>
        </div>

        <p style="font-size: 16px; margin-top: 30px;">
            If you have any questions, feel free to reply to this email.
        </p>

        <p style="margin-top: 30px;">
            Warm regards,<br>
            <strong>Events Team</strong>
        </p>
    </div>

</body>
</html>
