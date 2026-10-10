<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Check your email - Villa Salud</title>
    <link rel="icon" type="image/png" href="{{ asset('images/vs_logo.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    @vite('resources/css/login.css')
</head>
<body class="vs-layout--admin-auth">
    @php
        $email = $admin->email ?? '';
        $emailParts = explode('@', $email);
        $maskedEmail = (count($emailParts) === 2 && $emailParts[0] !== '')
            ? mb_substr($emailParts[0], 0, 1) . '***@' . $emailParts[1]
            : $email;
        $isEmailMethod = $admin->mfa_method === 'email';
    @endphp

    <x-auth-card>
        <div class="auth-head">
            <h1 class="auth-title">{{ $isEmailMethod ? 'Check your email' : 'Enter your code' }}</h1>
            <p class="auth-sub">
                @if ($isEmailMethod)
                    We sent a 6-digit code to <strong>{{ $maskedEmail }}</strong>
                @else
                    Enter the 6-digit code from your authenticator app
                @endif
            </p>
        </div>

        @if (!empty($devOtpCode))
        <div class="auth-dev" id="dev-code-box">
            <strong class="auth-dev-title">Development mode: testing workaround</strong>
            <div class="vs-inline-dev-copy">Since email isn't reachable during testing, here's your code:</div>
            <div id="dev-code-value" class="vs-inline-auth-code vs-inline-auth-code--large">{{ $devOtpCode }}</div>
            <div class="vs-inline-dev-note">Shown only while the app is in development. Remove before production.</div>
        </div>
        @endif

        <div id="mfa-message" class="auth-alert auth-alert--error" role="alert" aria-live="assertive"
             @if (!$errors->any()) hidden @endif>{{ $errors->first() }}</div>

        <form method="POST" action="{{ route('admin.mfa.challenge.submit') }}" id="challenge-form" class="auth-form">
            @csrf

            <div class="auth-field">
                <label class="auth-label" for="code">Verification code</label>
                <input class="auth-code" type="text" id="code" name="code" maxlength="6" inputmode="numeric"
                       pattern="[0-9]*" placeholder="000000" autocomplete="one-time-code" required autofocus
                       @if ($errors->any()) aria-invalid="true" aria-describedby="mfa-message" @endif>
            </div>

            <button type="submit" class="auth-btn" id="verify-btn">Verify</button>
        </form>

        <p class="auth-note">After verification, you won't need to re-verify for 6 hours.</p>

        @if ($isEmailMethod)
        <div class="auth-actions">
            <button type="button" class="auth-textlink" id="send-code-btn" onclick="sendCode()">Resend code</button>
            <span class="auth-note" id="send-status" role="status" aria-live="polite"></span>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.logout') }}" class="auth-account-form">
            @csrf
            <button type="submit" class="auth-textlink">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Use a different account
            </button>
        </form>
    </x-auth-card>

    <script>
        (function () {
            var isEmailMethod = @json($isEmailMethod);
            var verifyBtn = document.getElementById('verify-btn');
            var codeInput = document.getElementById('code');
            var form = document.getElementById('challenge-form');

            // Digits only, and auto-submit once 6 digits are entered.
            codeInput.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '').slice(0, 6);
                if (this.value.length === 6) {
                    setTimeout(function () {
                        verifyBtn.disabled = true;
                        verifyBtn.textContent = 'Verifying...';
                        form.submit();
                    }, 300);
                }
            });

            @if ($isEmailMethod)
            var resendBtn = document.getElementById('send-code-btn');
            var status = document.getElementById('send-status');
            var resendTimer = null;

            function renderResend(remaining, lead) {
                if (remaining <= 0) {
                    clearInterval(resendTimer);
                    resendTimer = null;
                    resendBtn.disabled = false;
                    resendBtn.textContent = 'Resend code';
                    status.textContent = lead || 'You can now request a new code.';
                    return;
                }
                var m = Math.floor(remaining / 60);
                var s = String(remaining % 60).padStart(2, '0');
                resendBtn.disabled = true;
                resendBtn.textContent = 'Resend code in ' + m + ':' + s;
                status.textContent = lead || '';
            }

            function startResendCountdown(seconds, lead) {
                var remaining = Math.max(0, parseInt(seconds, 10) || 0);
                clearInterval(resendTimer);
                renderResend(remaining, lead);
                remaining--;
                resendTimer = setInterval(function () {
                    renderResend(remaining, lead);
                    remaining--;
                }, 1000);
            }

            window.sendCode = function () {
                resendBtn.disabled = true;
                resendBtn.textContent = 'Sending...';
                status.textContent = 'Sending verification code...';

                fetch('{{ route("admin.mfa.resend-otp") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.success) {
                        startResendCountdown(data.cooldown_seconds || 600, 'Code sent. Check your email.');
                        // Dev workaround: show the code on-screen when provided.
                        if (data.code) {
                            var devBox = document.getElementById('dev-code-box');
                            var devValue = document.getElementById('dev-code-value');
                            if (devBox && devValue) {
                                devBox.style.display = 'block';
                                devValue.textContent = data.code;
                            }
                        }
                    } else if (data.retry_after) {
                        startResendCountdown(data.retry_after, 'A code was already sent.');
                    } else {
                        status.textContent = data.message || 'Failed to send code.';
                        resendBtn.textContent = 'Try again';
                        resendBtn.disabled = false;
                    }
                })
                .catch(function () {
                    status.textContent = 'Failed to send code. Please try again.';
                    resendBtn.textContent = 'Try again';
                    resendBtn.disabled = false;
                });
            };

            // Keep the resend link locked while the existing cooldown is running.
            var initialWait = {{ (int) ($resendAvailableIn ?? 0) }};
            if (initialWait > 0) {
                startResendCountdown(initialWait, 'A code was already sent.');
            }
            @endif
        })();
    </script>
</body>
</html>
