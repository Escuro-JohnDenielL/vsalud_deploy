<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\AdminTrustedDevice;
use App\Mail\MfaOtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MfaService
{
    /**
     * How long the trusted device cookie lasts (in days).
     */
    const TRUSTED_DEVICE_DAYS = 30;

    /**
     * How long the OTP code is valid (in minutes).
     */
    const OTP_EXPIRY_MINUTES = 10;

    /**
     * Generate a 6-digit OTP code and store it in the admin's session.
     * Returns the generated code.
     *
     * @param bool $sendEmail whether to also email the code (set false when the
     *                        code is only shown on-screen in dev mode).
     */
    public function generateAndSendOtp(Admin $admin, bool $sendEmail = true): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store code in session (not in DB for security — single-use, short-lived)
        session([
            'mfa_otp_code' => $code,
            'mfa_otp_expires_at' => now()->addMinutes(self::OTP_EXPIRY_MINUTES)->timestamp,
        ]);

        // Send email
        if ($sendEmail) {
            try {
                Mail::to($admin->email)->send(new MfaOtpMail([
                    'name' => $admin->name,
                    'code' => $code,
                    'expiry_minutes' => self::OTP_EXPIRY_MINUTES,
                ]));
                Log::info('MFA OTP sent to admin: ' . $admin->email);
            } catch (\Exception $e) {
                Log::error('Failed to send MFA OTP email: ' . $e->getMessage());
                throw $e;
            }
        }

        return $code;
    }

    /**
     * DEVELOPMENT-ONLY workaround: whether the OTP code should be shown on-screen.
     * Controlled by config('app.mfa_show_code_dev') (e.g. MFA_SHOW_CODE_DEV=true).
     */
    public function showCodeOnPage(): bool
    {
        return (bool) config('app.mfa_show_code_dev');
    }

    /**
     * Get the currently active OTP code from session (for on-screen display in dev).
     * Returns null if none is active or it has expired.
     */
    public function currentOtpCode(): ?string
    {
        $code = session('mfa_otp_code');
        $expiresAt = session('mfa_otp_expires_at');

        if (!$code || !$expiresAt || now()->timestamp > $expiresAt) {
            return null;
        }

        return (string) $code;
    }

    /**
     * Generate the current TOTP code for a stored secret (for on-screen display in dev).
     */
    public function currentTotpCode(string $secret): ?string
    {
        $decodedSecret = $this->base32Decode($secret);
        if ($decodedSecret === false || strlen($decodedSecret) === 0) {
            return null;
        }

        $counter = intdiv(time(), 30);
        return $this->generateTotpCode($decodedSecret, $counter);
    }

    /**
     * Verify the OTP code entered by the user against the session-stored code.
     */
    public function verifyOtp(string $userCode): bool
    {
        $storedCode = session('mfa_otp_code');
        $expiresAt = session('mfa_otp_expires_at');

        if (!$storedCode || !$expiresAt) {
            return false;
        }

        // Check expiry
        if (now()->timestamp > $expiresAt) {
            $this->clearOtpSession();
            return false;
        }

        // Constant-time comparison
        if (hash_equals((string) $storedCode, (string) $userCode)) {
            $this->clearOtpSession();
            return true;
        }

        return false;
    }

    /**
     * Clear OTP data from session.
     */
    public function clearOtpSession(): void
    {
        session()->forget(['mfa_otp_code', 'mfa_otp_expires_at']);
    }

    /**
     * Generate a TOTP-compatible shared secret (base32 encoded).
     */
    public function generateTotpSecret(): string
    {
        // Generate 20 random bytes (160 bits) for HMAC-SHA1 based TOTP
        $random = random_bytes(20);
        return $this->base32Encode($random);
    }

    /**
     * Generate an otpauth:// URI for QR code generation.
     */
    public function generateTotpUri(Admin $admin, string $secret): string
    {
        $label = rawurlencode($admin->email);
        $issuer = rawurlencode('Villa Salud');
        return "otpauth://totp/{$issuer}:{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Verify a TOTP code from an authenticator app.
     */
    public function verifyTotp(string $secret, string $userCode): bool
    {
        // TOTP is time-based, check current and adjacent time steps
        // (allow 1 step before/after for clock drift)
        $decodedSecret = $this->base32Decode($secret);
        if ($decodedSecret === false || strlen($decodedSecret) === 0) {
            return false;
        }

        $timeStep = 30;
        $timestamp = time();
        $counter = intdiv($timestamp, $timeStep);

        // Check current, previous, and next time step
        for ($i = -1; $i <= 1; $i++) {
            $generated = $this->generateTotpCode($decodedSecret, $counter + $i);
            if (hash_equals($generated, $userCode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a 6-digit TOTP code from a decoded secret and counter.
     */
    private function generateTotpCode(string $decodedSecret, int $counter): string
    {
        // Pack counter as 8-byte big-endian
        $counterBin = pack('J', $counter); // PHP 8+ unsigned 64-bit big-endian

        // HMAC-SHA1
        $hash = hash_hmac('sha1', $counterBin, $decodedSecret, true);

        // Dynamic truncation (RFC 4226)
        $offset = ord($hash[19]) & 0x0f;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a unique device token for "trust this device" feature.
     */
    public function generateDeviceToken(): string
    {
        return Str::random(64);
    }

    /**
     * Trust the current device for the admin.
     */
    public function trustDevice(Admin $admin, string $ipAddress, ?string $userAgent = null): AdminTrustedDevice
    {
        $token = $this->generateDeviceToken();

        // Build a human-readable device name
        $deviceName = $this->guessDeviceName($userAgent);

        $device = AdminTrustedDevice::create([
            'admin_id' => $admin->admin_id,
            'device_token' => $token,
            'device_name' => $deviceName,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'expires_at' => now()->addDays(self::TRUSTED_DEVICE_DAYS),
        ]);

        return $device;
    }

    /**
     * Check if a device token is valid (not expired and belongs to admin).
     */
    public function isDeviceTrusted(Admin $admin, string $token): bool
    {
        return AdminTrustedDevice::where('admin_id', $admin->admin_id)
            ->where('device_token', $token)
            ->where('expires_at', '>', now())
            ->exists();
    }

    /**
     * Revoke a specific trusted device.
     */
    public function revokeDevice(AdminTrustedDevice $device): bool
    {
        return $device->delete();
    }

    /**
     * Revoke all trusted devices for an admin (e.g., after security concern).
     */
    public function revokeAllDevices(Admin $admin): bool
    {
        return AdminTrustedDevice::where('admin_id', $admin->admin_id)->delete() > 0;
    }

    /**
     * Clean up expired trusted devices.
     */
    public function cleanExpiredDevices(): int
    {
        return AdminTrustedDevice::where('expires_at', '<=', now())->delete();
    }

    /**
     * Guess device name from user agent.
     */
    private function guessDeviceName(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Unknown Device';
        }

        $ua = strtolower($userAgent);

        $browser = 'Unknown';
        if (str_contains($ua, 'edg/') || str_contains($ua, 'edge/')) $browser = 'Edge';
        elseif (str_contains($ua, 'chrome/')) $browser = 'Chrome';
        elseif (str_contains($ua, 'firefox/')) $browser = 'Firefox';
        elseif (str_contains($ua, 'safari/')) $browser = 'Safari';
        elseif (str_contains($ua, 'opera/') || str_contains($ua, 'opr/')) $browser = 'Opera';

        $os = 'Unknown';
        if (str_contains($ua, 'windows nt')) $os = 'Windows';
        elseif (str_contains($ua, 'macintosh') || str_contains($ua, 'mac os')) $os = 'macOS';
        elseif (str_contains($ua, 'linux')) $os = 'Linux';
        elseif (str_contains($ua, 'android')) $os = 'Android';
        elseif (str_contains($ua, 'iphone') || str_contains($ua, 'ipad')) $os = 'iOS';

        return "{$browser} on {$os}";
    }

    /**
     * Base32 encode (RFC 4648).
     */
    private function base32Encode(string $data): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $result = '';
        $bits = 0;
        $value = 0;

        for ($i = 0; $i < strlen($data); $i++) {
            $value = ($value << 8) | ord($data[$i]);
            $bits += 8;

            while ($bits >= 5) {
                $bits -= 5;
                $result .= $alphabet[($value >> $bits) & 0x1f];
            }
        }

        if ($bits > 0) {
            $result .= $alphabet[($value << (5 - $bits)) & 0x1f];
        }

        return $result;
    }

    /**
     * Base32 decode (RFC 4648).
     */
    private function base32Decode(string $data): string|false
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data = strtoupper($data);
        $data = str_replace('=', '', $data); // Remove padding

        $result = '';
        $bits = 0;
        $value = 0;

        for ($i = 0; $i < strlen($data); $i++) {
            $char = $data[$i];
            $pos = strpos($alphabet, $char);

            if ($pos === false) {
                return false; // Invalid character
            }

            $value = ($value << 5) | $pos;
            $bits += 5;

            if ($bits >= 8) {
                $bits -= 8;
                $result .= chr(($value >> $bits) & 0xff);
            }
        }

        return $result;
    }
}
