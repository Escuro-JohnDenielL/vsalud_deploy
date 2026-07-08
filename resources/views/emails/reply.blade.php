<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Reply to Inquiry</title>
</head>

<body style="font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 30px; color: #333;">

    <div
        style="max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">

        <h2 style="color: #2c3e50;">👋 Hi {{ $data['name'] }},</h2>

        <p style="font-size: 16px;">
            Thank you for your reservation inquiry! We've received your request and would be happy to assist your
            further. 😊
        </p>

        <p style="font-size: 16px; margin-top: 30px;">
            To help us process your reservation smoothly, feel free to reply on this message with any additional
            details, questions, or changes you'd like to make. 😊
        </p>

        <p style="font-size: 16px;">
            Looking forward to your response.
        </p>

        <p style="font-size: 16px;">
            <strong>Requested further details: </strong> {{ $data['message'] }}
        </p>

        <p style="margin-top: 30px;">
            Warm regards,<br>
            <strong>Events Team</strong><br><br>
            {{-- 📞 <strong>Phone:</strong> (+63) 912 345 6789<br> --}}
            {{-- 📧 <strong>Email:</strong> <a href="mailto:coheredit@gmail.com"
                style="color: #3498db;">coheredit@gmail.com</a> --}}
        </p>
    </div>

</body>

</html>
