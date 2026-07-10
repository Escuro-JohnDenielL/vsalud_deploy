<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReCaptchaService
{
    /**
     * Minimum score threshold for reCAPTCHA v3 (0.0 to 1.0).
     */
    const MIN_SCORE = 0.5;

    /**
     * Verify the reCAPTCHA v3 response token with Google.
     */
    public static function verify(string $token, ?string $remoteIp = null): bool
    {
        $secret = config('services.recaptcha.secret_key');

        if (empty($secret) || str_starts_with($secret, 'your_recaptcha')) {
            // If keys are not configured, skip verification in local/dev environments
            Log::warning('reCAPTCHA secret key not configured — skipping verification.');
            return true;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => $remoteIp,
            ]);

            $body = $response->json();

            if (!empty($body['success']) && ($body['score'] ?? 0) >= self::MIN_SCORE) {
                return true;
            }

            Log::warning('reCAPTCHA v3 verification failed', [
                'score'       => $body['score'] ?? 'N/A',
                'error-codes' => $body['error-codes'] ?? 'unknown',
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('reCAPTCHA v3 verification exception: ' . $e->getMessage());
            return false;
        }
    }
}
