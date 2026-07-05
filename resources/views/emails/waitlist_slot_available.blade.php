<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Slot Available — Book Now</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 30px; color: #333;">

    <div style="max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">

        <h2 style="color: #2c3e50;">Hi {{ $data['name'] }},</h2>

        <p style="font-size: 16px;">
            Great news! 🎉 A slot has opened up for <strong>{{ $data['date'] }}</strong>.
        </p>

        <p style="font-size: 16px;">
            Since you're on our waitlist, you get first priority to claim it.
            <strong style="color: #e74c3c;">You have {{ $data['hours'] }} hours</strong> from now to submit your reservation for this date.
        </p>

        <div style="text-align: center; margin: 30px 0;">
            <a href="{{ url('/mreserve') }}?date={{ $data['date'] }}"
               style="display: inline-block; background-color: #27ae60; color: white; padding: 14px 32px; font-size: 16px; text-decoration: none; border-radius: 6px; font-weight: bold;">
                Book Your Reservation
            </a>
        </div>

        <p style="font-size: 14px; color: #777;">
            If you don't submit a reservation within {{ $data['hours'] }} hours, the slot will be offered to the next person on the waitlist.
        </p>

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
