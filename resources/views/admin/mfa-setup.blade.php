<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Set Up Two-Factor Authentication - Villa Salud</title>
    <link rel="icon" type="image/png" href="{{ asset('images/vs_logo.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .mfa-container {
            max-width: 580px;
            width: 100%;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            padding: 40px;
        }
        .mfa-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .mfa-header h1 {
            font-size: 24px;
            font-weight: 700;
            color: #0d7a3e;
            margin-bottom: 8px;
        }
        .mfa-header p {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.5;
        }




        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; font-size: 14px; color: #374151; margin-bottom: 6px; }
        .form-group input[type="text"],
        .form-group input[type="number"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 16px;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s;
            text-align: center;
            letter-spacing: 4px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #0d7a3e;
            box-shadow: 0 0 0 3px rgba(13,122,62,0.15);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
        }
        .btn-primary { background: #0d7a3e; color: #fff; }
        .btn-primary:hover { background: #0b6b36; }
        .btn-primary:disabled { background: #9ca3af; cursor: not-allowed; }
        .btn-outline { background: transparent; color: #0d7a3e; border: 2px solid #0d7a3e; }
        .btn-outline:hover { background: #f0fdf4; }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #4b5563;
        }
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #0d7a3e;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 16px;
        }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #0d7a3e; }
        .alert-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #2563eb; }

        .dev-code {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
            text-align: center;
        }

        .timer-text { font-size: 13px; color: #6b7280; margin-top: 8px; }

        .resend-link {
            display: inline-block;
            margin-top: 8px;
            font-size: 14px;
            color: #0d7a3e;
            cursor: pointer;
            text-decoration: underline;
        }
        .resend-link:hover { color: #0b6b36; }

        .otp-input-wrapper { position: relative; }
        .otp-input-wrapper .send-otp-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            padding: 6px 16px;
            font-size: 12px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="mfa-container">
        <div class="mfa-header">
            <h1>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-4px;margin-right:6px;"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Set Up Two-Factor Authentication
            </h1>
            <p>Enhance your account security by adding an extra layer of protection.</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="alert alert-info">
            <strong>Why is this required?</strong> As part of our security policy, all administrators must set up two-factor authentication before accessing the system.
        </div>

        <!-- Development workaround: show OTP on-screen during testing -->
        <div class="dev-code" id="setup-dev-code" style="display:none;margin-bottom:16px;">
            <strong style="display:block;margin-bottom:4px;">⚠️ Development Mode — Testing Workaround</strong>
            <div style="font-size:13px;color:#92400e;margin-bottom:4px;">Here's your verification code (since email isn't reachable during testing):</div>
            <div id="setup-dev-code-value" style="font-size:30px;font-weight:800;letter-spacing:8px;color:#b45309;">------</div>
            <div style="font-size:12px;color:#b45309;margin-top:4px;">Shown only while the app is in development. Remove before production.</div>
        </div>

        <!-- Setup Form -->
        <div id="setup-form">
            <div style="text-align:center;margin-bottom:24px;">
                <span style="display:block;margin-bottom:8px;color:#0d7a3e;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </span>
                <p style="font-size:14px;color:#4b5563;">
                    A verification code will be sent to <strong>{{ $admin->email }}</strong>.
                </p>
            </div>

            <form method="POST" action="{{ route('admin.mfa.setup.submit') }}" id="mfa-setup-form">
                @csrf
                <input type="hidden" name="method" value="email">

                <div class="form-group">
                    <label for="email-code">Enter Verification Code</label>
                    <div class="otp-input-wrapper">
                        <input type="text" id="email-code" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]*" placeholder="000000" autocomplete="one-time-code" required>
                    </div>
                    <button type="button" class="resend-link" id="send-email-otp-btn" onclick="sendEmailOtp()">Send Verification Code</button>
                    <div class="timer-text" id="email-otp-status">Click the link above to receive your code.</div>
                </div>

                <p style="font-size:13px;color:#6b7280;margin:0 0 20px 0;text-align:center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    After verification, your session stays verified for <strong>6 hours</strong> before requiring a new code.
                </p>

                <button type="submit" class="btn btn-primary" id="verify-btn">Verify &amp; Enable</button>
            </form>
        </div>
    </div>

    <script>
        let otpSent = false;

        // Auto-send OTP on page load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(sendEmailOtp, 500);
        });

        function sendEmailOtp() {
            const btn = document.getElementById('send-email-otp-btn');
            const status = document.getElementById('email-otp-status');
            btn.disabled = true;
            btn.textContent = 'Sending...';
            status.textContent = 'Sending verification code...';

            fetch('{{ route("admin.mfa.send-setup-otp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    otpSent = true;
                    status.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0d7a3e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><polyline points="20 6 9 17 4 12"/></svg> Verification code sent! Please check your email.';
                    btn.textContent = 'Resend Code';
                    btn.disabled = false;

                    // Dev workaround: show/update the code on-screen if provided.
                    if (data.code) {
                        const devBox = document.getElementById('setup-dev-code');
                        const devValue = document.getElementById('setup-dev-code-value');
                        if (devBox && devValue) {
                            devBox.style.display = 'block';
                            devValue.textContent = data.code;
                        }
                    }
                } else {
                    status.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> ' + (data.message || 'Failed to send code.');
                    btn.textContent = 'Try Again';
                    btn.disabled = false;
                }
            })
            .catch(err => {
                status.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> Failed to send code. Please try again.';
                btn.textContent = 'Try Again';
                btn.disabled = false;
            });
        }

        // Auto-submit prevention and validation
        document.getElementById('mfa-setup-form').addEventListener('submit', function(e) {
            const codeInput = document.getElementById('email-code');
            if (codeInput.value.length !== 6) {
                e.preventDefault();
                codeInput.focus();
                return;
            }
        });
    </script>
</body>
</html>
