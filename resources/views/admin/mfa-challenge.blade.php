<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Verify Your Identity - Villa Salud</title>
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
        .challenge-container {
            max-width: 440px;
            width: 100%;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
        }
        .lock-icon {
            font-size: 56px;
            margin-bottom: 16px;
            display: block;
        }
        h1 {
            font-size: 22px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }
        .subtitle {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 28px;
            line-height: 1.5;
        }
        .subtitle strong { color: #1f2937; }

        .method-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #0d7a3e;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 24px;
        }

        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; font-weight: 600; font-size: 14px; color: #374151; margin-bottom: 8px; }
        .form-group input[type="text"] {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 24px;
            font-family: 'Inter', sans-serif;
            text-align: center;
            letter-spacing: 8px;
            transition: border-color 0.2s;
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
            padding: 14px 24px;
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

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 16px;
            text-align: left;
        }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #0d7a3e; }

        .resend-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .resend-link {
            font-size: 14px;
            color: #0d7a3e;
            cursor: pointer;
            text-decoration: underline;
            background: none;
            border: none;
            font-family: 'Inter', sans-serif;
        }
        .resend-link:hover { color: #0b6b36; }
        .resend-link:disabled { color: #9ca3af; cursor: not-allowed; text-decoration: none; }

        .timer-text { font-size: 13px; color: #6b7280; margin-top: 12px; }

        .logout-link {
            display: inline-block;
            margin-top: 16px;
            font-size: 13px;
            color: #9ca3af;
            text-decoration: none;
        }
        .logout-link:hover { color: #6b7280; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="challenge-container">
        <span class="lock-icon">🔐</span>
        <h1>Verify Your Identity</h1>
        <p class="subtitle">
            Please enter the verification code to complete your login.
        </p>

        <div class="method-badge">
            @if ($admin->mfa_method === 'email')
                📧 Email OTP
            @else
                📱 Authenticator App
            @endif
        </div>

        @if ($errors->any())
            <div class="alert alert-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.mfa.challenge.submit') }}" id="challenge-form">
            @csrf

            <div class="form-group">
                <label for="code">
                    @if ($admin->mfa_method === 'email')
                        Enter the 6-digit code sent to your email
                    @else
                        Enter the 6-digit code from your authenticator app
                    @endif
                </label>
                <input type="text" id="code" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]*"
                       placeholder="000000" autocomplete="one-time-code" required autofocus>
            </div>

            <p style="font-size:13px;color:#6b7280;margin-bottom:20px;text-align:center;">
                ⏱️ After verification, you won't need to re-verify for <strong>6 hours</strong>.
            </p>

            <button type="submit" class="btn btn-primary" id="verify-btn" style="display:none;">Verify &amp; Login</button>
        </form>

        @if ($admin->mfa_method === 'email')
        <div class="resend-section">
            <button class="btn btn-primary" id="send-code-btn" onclick="sendCode()" style="margin-bottom:12px;">📧 Send Verification Code</button>
            <div class="timer-text" id="send-status"></div>
        </div>
        @endif

        <div>
            <form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="logout-link">← Use a different account</button>
            </form>
        </div>
    </div>

    <script>
        @if ($admin->mfa_method === 'email')
        function sendCode() {
            const btn = document.getElementById('send-code-btn');
            const status = document.getElementById('send-status');
            const verifyBtn = document.getElementById('verify-btn');
            btn.disabled = true;
            btn.textContent = 'Sending...';
            status.textContent = 'Sending verification code...';

            fetch('{{ route("admin.mfa.resend-otp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    status.textContent = '✅ Code sent! Please check your email.';
                    btn.textContent = 'Resend Code';
                    btn.disabled = false;
                    verifyBtn.style.display = 'block';
                } else {
                    status.textContent = '❌ ' + (data.message || 'Failed to send code.');
                    btn.textContent = 'Try Again';
                    btn.disabled = false;
                }
            })
            .catch(err => {
                status.textContent = '❌ Failed to send code. Please try again.';
                btn.textContent = 'Try Again';
                btn.disabled = false;
            });
        }
        @endif

        // Auto-submit when 6 digits entered
        document.getElementById('code')?.addEventListener('input', function() {
            if (this.value.length === 6) {
                setTimeout(() => {
                    document.getElementById('verify-btn').disabled = true;
                    document.getElementById('verify-btn').textContent = 'Verifying...';
                    document.getElementById('challenge-form').submit();
                }, 300);
            }
        });
    </script>
</body>
</html>
