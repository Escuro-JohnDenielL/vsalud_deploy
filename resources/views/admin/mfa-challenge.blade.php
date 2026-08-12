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

        .btn-outline {
            background: #ffffff;
            color: #0d7a3e;
            border: 1px solid #0d7a3e;
        }
        .btn-outline:hover { background: #f0fdf4; }
        .btn-outline:disabled {
            background: #ffffff;
            color: #9ca3af;
            border-color: #d1d5db;
            cursor: not-allowed;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 16px;
            text-align: left;
        }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #0d7a3e; }

        .dev-code {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 20px;
            text-align: center;
        }

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
        <span class="lock-icon" style="color:#0d7a3e;">
            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </span>
        <h1>Verify Your Identity</h1>
        <p class="subtitle">
            Please enter the verification code to complete your login.
        </p>

        <div class="method-badge">
            @if ($admin->mfa_method === 'email')
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Email OTP
            @else
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/></svg>
                Authenticator App
            @endif
        </div>

        @if (!empty($devOtpCode))
        <div class="dev-code" id="dev-code-box">
            <strong style="display:block;margin-bottom:4px;">⚠️ Development Mode — Testing Workaround</strong>
            <div style="font-size:13px;color:#92400e;margin-bottom:4px;">Since email isn't reachable during testing, here's your code:</div>
            <div id="dev-code-value" style="font-size:34px;font-weight:800;letter-spacing:8px;color:#b45309;">{{ $devOtpCode }}</div>
            <div style="font-size:12px;color:#b45309;margin-top:4px;">Shown only while the app is in development. Remove before production.</div>
        </div>
        @endif

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
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                After verification, you won't need to re-verify for <strong>6 hours</strong>.
            </p>

            <button type="submit" class="btn btn-primary" id="verify-btn" style="display:none;">Verify &amp; Login</button>
        </form>

        @if ($admin->mfa_method === 'email')
        <div class="resend-section">
            <button class="btn btn-outline" id="send-code-btn" onclick="sendCode()" style="margin-bottom:12px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                Send Verification Code
            </button>
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
                    status.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#0d7a3e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px;"><polyline points="20 6 9 17 4 12"/></svg> Code sent! Please check your email.';
                    btn.textContent = 'Resend Code';
                    btn.disabled = false;
                    verifyBtn.style.display = 'block';

                    // Dev workaround: show/update the code on-screen if provided.
                    if (data.code) {
                        const devBox = document.getElementById('dev-code-box');
                        const devValue = document.getElementById('dev-code-value');
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

        // Dev workaround: reveal the verify button immediately since the code is shown on-screen.
        @if (!empty($devOtpCode))
        document.getElementById('verify-btn').style.display = 'block';
        @endif
    </script>
</body>
</html>
