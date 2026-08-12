<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminTrustedDevice;
use App\Services\MfaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MfaController extends Controller
{
    protected MfaService $mfaService;

    public function __construct(MfaService $mfaService)
    {
        $this->mfaService = $mfaService;
    }

    /**
     * Show the MFA setup page.
     * Accessible to authenticated admins who haven't set up MFA yet.
     */
    public function showSetup()
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return redirect()->route('admin.login');
        }

        // If MFA is already set up, redirect to challenge or home
        if ($admin->hasMfaEnabled()) {
            return redirect()->route('admin.home');
        }

        // Generate a TOTP secret for the authenticator option
        $totpSecret = $this->mfaService->generateTotpSecret();
        $totpUri = $this->mfaService->generateTotpUri($admin, $totpSecret);

        // Store the generated secret in session for verification
        session(['mfa_pending_totp_secret' => $totpSecret]);

        // DEVELOPMENT-ONLY workaround: generate the email OTP and show it on-screen
        // immediately so testers who can't reach their email can still finish setup.
        // (Email itself is sent by the page's auto "Send Verification Code" call.)
        $devOtpCode = null;
        if ($this->mfaService->showCodeOnPage()) {
            try {
                $this->mfaService->generateAndSendOtp($admin, false);
            } catch (\Exception $e) {
                Log::warning('Dev workaround: could not prepare OTP for setup page. ' . $e->getMessage());
            }
            $devOtpCode = $this->mfaService->currentOtpCode();
        }

        return view('admin.mfa-setup', compact('admin', 'totpSecret', 'totpUri', 'devOtpCode'));
    }

    /**
     * Process MFA setup.
     */
    public function setup(Request $request)
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return redirect()->route('admin.login');
        }

        if ($admin->hasMfaEnabled()) {
            return redirect()->route('admin.home');
        }

        $validator = Validator::make($request->all(), [
            'method' => 'required|in:email,authenticator',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $method = $request->input('method');
        $code = $request->input('code');

        if ($method === 'email') {
            // Verify the OTP sent during setup
            if (!$this->mfaService->verifyOtp($code)) {
                return back()->withErrors(['code' => 'The verification code is invalid or has expired.'])->withInput();
            }

            $secret = null; // No secret needed for email method
        } elseif ($method === 'authenticator') {
            $pendingSecret = session('mfa_pending_totp_secret');

            if (!$pendingSecret) {
                return back()->withErrors(['code' => 'Session expired. Please try setting up again.']);
            }

            // Verify the TOTP code against the pending secret
            if (!$this->mfaService->verifyTotp($pendingSecret, $code)) {
                return back()->withErrors(['code' => 'The code is invalid. Please try again.'])->withInput();
            }

            $secret = $pendingSecret;
        } else {
            return back()->withErrors(['method' => 'Invalid MFA method selected.']);
        }

        // Save MFA configuration
        $admin->mfa_secret = $secret;
        $admin->mfa_method = $method;
        $admin->mfa_setup_completed_at = now();
        $admin->save();

        // Clear session data
        session()->forget('mfa_pending_totp_secret');
        session()->forget('mfa_redirect_intended');
        $this->mfaService->clearOtpSession();

        // Set MFA as verified — stored in both session AND DB
        $admin->mfa_verified_at = now();
        $admin->save();
        session(['mfa_verified_at' => $admin->mfa_verified_at]);

        Log::info("MFA '{$method}' enabled for admin: {$admin->email}");

        // Redirect to intended page or home
        $intended = session('mfa_redirect_intended');
        if ($intended) {
            session()->forget('mfa_redirect_intended');
            return redirect($intended)->with('success', 'Two-factor authentication has been enabled successfully.');
        }

        return redirect()->route('admin.home')->with('success', 'Two-factor authentication has been enabled successfully.');
    }

    /**
     * Show the MFA challenge page (for login verification).
     */
    public function showChallenge()
    {
        // This page is accessed when an admin is authenticated but MFA is required
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return redirect()->route('admin.login');
        }

        // If MFA is not set up, redirect to setup
        if (!$admin->hasMfaEnabled()) {
            return redirect()->route('admin.mfa.setup');
        }

        // If already verified within the last 6 hours, go to home
        $mfaVerifiedAt = session('mfa_verified_at');
        if ($mfaVerifiedAt && \Carbon\Carbon::parse($mfaVerifiedAt)->gt(now()->subHours(6))) {
            return redirect()->intended('/admin/home');
        }

        // DEVELOPMENT-ONLY workaround: show the code on-screen since testers
        // may not be able to reach their email / authenticator app.
        $devOtpCode = null;
        if ($this->mfaService->showCodeOnPage()) {
            if ($admin->mfa_method === 'email') {
                try {
                    $this->mfaService->generateAndSendOtp($admin);
                } catch (\Exception $e) {
                    Log::warning('Dev workaround: could not email OTP, showing on page instead. ' . $e->getMessage());
                }
                $devOtpCode = $this->mfaService->currentOtpCode();
            } elseif ($admin->mfa_method === 'authenticator' && $admin->mfa_secret) {
                $devOtpCode = $this->mfaService->currentTotpCode($admin->mfa_secret);
            }
        }

        return view('admin.mfa-challenge', compact('admin', 'devOtpCode'));
    }

    /**
     * Process MFA challenge (verify code during login).
     */
    public function challenge(Request $request)
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return redirect()->route('admin.login');
        }

        if (!$admin->hasMfaEnabled()) {
            return redirect()->route('admin.mfa.setup');
        }

        $mfaVerifiedAt = session('mfa_verified_at');
        if ($mfaVerifiedAt && \Carbon\Carbon::parse($mfaVerifiedAt)->gt(now()->subHours(6))) {
            return redirect()->intended('/admin/home');
        }

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $code = $request->input('code');
        $isValid = false;

        if ($admin->mfa_method === 'email') {
            $isValid = $this->mfaService->verifyOtp($code);
        } elseif ($admin->mfa_method === 'authenticator' && $admin->mfa_secret) {
            $isValid = $this->mfaService->verifyTotp($admin->mfa_secret, $code);
        }

        if (!$isValid) {
            // Log failed MFA attempt
            Log::warning("Failed MFA attempt for admin: {$admin->email} from IP {$request->ip()}");

            return back()->withErrors(['code' => 'The verification code is invalid or has expired. Please try again.']);
        }

        // Mark MFA as verified — stored in both session AND DB for cross-login persistence
        $now = now();
        session(['mfa_verified_at' => $now]);
        $admin->mfa_verified_at = $now;

        // Update last login info
        $admin->last_login_ip = $request->ip();
        $admin->last_login_at = $now;
        $admin->save();

        Log::info("MFA challenge passed for admin: {$admin->email} — 6-hour session started");

        return redirect()->intended('/admin/home');
    }

    /**
     * Disable MFA for the current admin.
     */
    public function disable(Request $request)
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if (!$admin->hasMfaEnabled()) {
            return response()->json(['success' => false, 'message' => 'MFA is not enabled.'], 422);
        }

        // Require current password to disable MFA
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Current password is required.'], 422);
        }

        if (!\Illuminate\Support\Facades\Hash::check($request->input('current_password'), $admin->password)) {
            return response()->json(['success' => false, 'message' => 'Current password is incorrect.'], 422);
        }

        $admin->mfa_secret = null;
        $admin->mfa_method = null;
        $admin->mfa_setup_completed_at = null;
        $admin->mfa_verified_at = null;
        $admin->save();

        // Revoke all trusted devices
        $this->mfaService->revokeAllDevices($admin);

        // Clear MFA session
        session()->forget('mfa_verified_at');

        Log::info("MFA disabled for admin: {$admin->email}");

        return response()->json(['success' => true, 'message' => 'Two-factor authentication has been disabled.']);
    }

    /**
     * Resend OTP during challenge.
     */
    public function resendOtp(Request $request)
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        if (!$admin || $admin->mfa_method !== 'email') {
            return response()->json(['success' => false, 'message' => 'Invalid request.'], 422);
        }

        try {
            $this->mfaService->generateAndSendOtp($admin);

            $response = ['success' => true, 'message' => 'A new verification code has been sent to your email.'];

            // Dev workaround: include the code so it can be shown on-screen.
            if ($this->mfaService->showCodeOnPage()) {
                $response['code'] = $this->mfaService->currentOtpCode();
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send verification code. Please try again.'], 500);
        }
    }

    /**
     * Send initial OTP during MFA setup (email method).
     */
    public function sendSetupOtp(Request $request)
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        if ($admin->hasMfaEnabled()) {
            return response()->json(['success' => false, 'message' => 'MFA already enabled.'], 422);
        }

        try {
            $this->mfaService->generateAndSendOtp($admin);

            $response = ['success' => true, 'message' => 'A verification code has been sent to your email.'];

            // Dev workaround: include the code so it can be shown on-screen.
            if ($this->mfaService->showCodeOnPage()) {
                $response['code'] = $this->mfaService->currentOtpCode();
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send verification code. Please try again.'], 500);
        }
    }

    /**
     * List trusted devices for the current admin.
     */
    public function trustedDevices()
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $devices = $admin->activeTrustedDevices()->orderBy('created_at', 'desc')->get()->map(function ($device) {
            return [
                'id' => $device->id,
                'device_name' => $device->device_name,
                'ip_address' => $device->ip_address,
                'created_at' => $device->created_at->toDateTimeString(),
                'expires_at' => $device->expires_at->toDateTimeString(),
            ];
        });

        return response()->json(['success' => true, 'devices' => $devices]);
    }

    /**
     * Revoke a specific trusted device.
     */
    public function revokeDevice(Request $request, $deviceId)
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return response()->json(['success' => false, 'message' => 'Not authenticated'], 401);
        }

        $device = AdminTrustedDevice::where('id', $deviceId)
            ->where('admin_id', $admin->admin_id)
            ->first();

        if (!$device) {
            return response()->json(['success' => false, 'message' => 'Device not found.'], 404);
        }

        $device->delete();

        return response()->json(['success' => true, 'message' => 'Trusted device revoked.']);
    }
}
