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

            // Save code and expiration (10 minutes from now)
            $admin->password_reset_code = $code;
            $admin->password_reset_code_expires_at = now()->addMinutes(10);
            $admin->save();

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
}