<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;

class AdminProfileController extends Controller
{
    public function update(Request $request)
    {
        try {
            /** @var \App\Models\Admin $admin */
            $admin = Auth::guard('admin')->user();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:admin,email,' . $admin->admin_id . ',admin_id',
                'phone' => 'nullable|string|max:20',
                'username' => 'nullable|string|max:255',
            ]);

            if (isset($validated['name'])) {
                $nameParts = explode(' ', $validated['name'], 2);
                $admin->f_name = $nameParts[0];
                $admin->l_name = isset($nameParts[1]) ? $nameParts[1] : '';
            }

            $admin->email = $validated['email'];

            if (isset($validated['phone'])) {
                $admin->phone = $validated['phone'];
            }

            $admin->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Admin profile update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the profile. Please try again.'
            ], 500);
        }
    }

    /**
     * Step 1: Validate current password and send a 6-digit code to the admin's email.
     */
    public function sendPasswordResetCode(Request $request)
    {
        try {
            /** @var \App\Models\Admin $admin */
            $admin = Auth::guard('admin')->user();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }

            $validated = $request->validate([
                'current_password' => 'required|string',
            ]);

            // Check if current password is correct
            if (!Hash::check($validated['current_password'], $admin->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect.'
                ], 422);
            }

            // Generate a 6-digit code
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Save code and expiration (10 minutes from now). The code column
            // holds 6 characters, so the code is stored as sent rather than
            // hashed; a hash would need a wider column (proposal, not code).
            $admin->password_reset_code = $code;
            $admin->password_reset_code_expires_at = now()->addMinutes(self::CODE_TTL_MINUTES);
            $admin->save();

            // A brand new code starts with a clean set of guesses.
            $this->clearFailedCodeAttempts($admin->admin_id);

            // Send email with the code
            Mail::to($admin->email)->send(new PasswordResetCode([
                'name' => $admin->name,
                'code' => $code,
            ]));

            Log::info('Password reset code sent to admin: ' . $admin->email);

            return response()->json([
                'success' => true,
                'message' => 'A verification code has been sent to your email. Please check your inbox.',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Send password reset code error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while sending the verification code. Please try again.'
            ], 500);
        }
    }

    /**
     * Step 2: Verify the reset code and update the password.
     */
    public function changePassword(Request $request)
    {
        try {
            /** @var \App\Models\Admin $admin */
            $admin = Auth::guard('admin')->user();

            if (!$admin) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not authenticated'
                ], 401);
            }

            // Validate the request
            $validated = $request->validate([
                'current_password' => 'required|string',
                'reset_code' => 'required|string|size:6',
                'new_password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};:"\\|,.<>\/?]).+$/'],
                'new_password_confirmation' => 'required|string|same:new_password',
            ]);

            // Check if current password is correct
            if (!Hash::check($validated['current_password'], $admin->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect.'
                ], 422);
            }

            // Verify the reset code
            if (!$admin->password_reset_code || !$admin->password_reset_code_expires_at) {
                return response()->json([
                    'success' => false,
                    'message' => 'No verification code was requested. Please request a new code.'
                ], 422);
            }

            if ($admin->password_reset_code !== $validated['reset_code']) {
                // Count the miss in the cache, and burn the code once the
                // guesses run out so a stolen session cannot keep grinding
                // through a six-digit space for the whole ten minutes.
                $attempts = $this->registerFailedCodeAttempt($admin->admin_id);

                if ($attempts >= self::MAX_CODE_ATTEMPTS) {
                    $admin->password_reset_code = null;
                    $admin->password_reset_code_expires_at = null;
                    $admin->save();

                    $this->clearFailedCodeAttempts($admin->admin_id);

                    Log::warning('Admin password reset code invalidated after repeated wrong guesses: ' . $admin->email);

                    return response()->json([
                        'success' => false,
                        'message' => 'Too many incorrect codes. The code has been invalidated. Please request a new one.',
                    ], 422);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'The verification code you entered is incorrect.'
                ], 422);
            }

            if (now()->isAfter($admin->password_reset_code_expires_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The verification code has expired. Please request a new code.'
                ], 422);
            }

            // Update password
            $admin->password = Hash::make($validated['new_password']);
            $admin->password_reset_code = null;
            $admin->password_reset_code_expires_at = null;
            $admin->save();

            $this->clearFailedCodeAttempts($admin->admin_id);

            // Invalidate other active sessions
            Auth::guard('admin')->logoutOtherDevices($validated['new_password']);

            Log::info('Password changed successfully for admin: ' . $admin->email);

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully.'
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Password change error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while changing password. Please try again.'
            ], 500);
        }
    }

    /**
     * Record one wrong code against an admin and return the running total.
     *
     * The counter lives in the cache rather than the database: the code it
     * guards only lives for ten minutes, and adding a column for it would be a
     * schema change.
     */
    private function registerFailedCodeAttempt(int $adminId): int
    {
        $key = $this->codeAttemptsKey($adminId);

        // Seed the key so the first increment has something to count on.
        Cache::add($key, 0, now()->addMinutes(self::ATTEMPT_WINDOW_MINUTES));

        return (int) Cache::increment($key);
    }

    /**
     * Forget the wrong-code count for an admin.
     */
    private function clearFailedCodeAttempts(int $adminId): void
    {
        Cache::forget($this->codeAttemptsKey($adminId));
    }

    /**
     * Cache key for the wrong-code counter of one admin.
     */
    private function codeAttemptsKey(int $adminId): string
    {
        return 'admin-password-code-attempts:' . $adminId;
    }
}