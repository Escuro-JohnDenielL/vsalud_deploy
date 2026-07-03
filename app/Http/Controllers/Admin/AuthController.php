<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\Admin;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showSignupForm()
    {
        return view('admin.signup');
    }

    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:admin,email',
            'f_name' => 'required|string|max:50',
            'l_name' => 'required|string|max:50',
            'phone' => 'required|digits:11',
            'password' => 'required|min:6',
            'confirm_password' => 'required|same:password',
            // 'profile_picture' => 'nullable|in:default.png,boy.png,boy1.png,boy2.png,girl.png,girl1.png,girl2.png', // temporarily removed
        ]);

        if ($validator->fails()) {
            return redirect()->back()->with('error', $validator->errors()->first())->withInput();
        }

        Admin::create([
            'email' => $request->email,
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            // 'profile_picture' => $request->input('profile_picture', 'default.png'), // temporarily removed
        ]);

        return redirect()->route('admin.login')->with('success', 'Account created successfully. Please log in.');
    }

    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');
        $key = strtolower($request->input('email')).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => "Too many login attempts. Try again in {$seconds} seconds.",
                ]);
        }

        if (Auth::guard('admin')->attempt($credentials)) {
            Log::info('nag true');
            RateLimiter::clear($key);
            
            $admin = Auth::guard('admin')->user();

            // Store login time in session for logout duration calculation
            session(['admin_login_time' => now()]);

            // Log the login activity
            ActivityLog::create([
                'admin_id' => $admin->admin_id,
                'activity_type' => 'login',
                'description' => "Admin '{$admin->f_name} {$admin->l_name}' logged in successfully",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Log::info($request); //dito wala akong na re-receive na request


            return redirect()->intended('/admin/home');
        }

        RateLimiter::hit($key, 60);

        $remaining = 5 - RateLimiter::attempts($key);

        return back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => $remaining > 0
                    ? 'Invalid login credentials.'
                    : 'Too many login attempts. Try again in 60 seconds.',
            ]);
    }

    public function logout(Request $request)
    {
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();

            // Calculate session duration
            $loginTime = session('admin_login_time');
            $sessionDuration = '00:00:00'; // Default if no login time found

            if ($loginTime) {
                $duration = Carbon::parse($loginTime)->diff(now());
                $sessionDuration = sprintf(
                    '%02d:%02d:%02d',
                    $duration->h + ($duration->days * 24), // Include days in hours
                    $duration->i,
                    $duration->s
                );
            }

            // Log the logout activity
            ActivityLog::create([
                'admin_id' => $admin->admin_id,
                'activity_type' => 'logout',
                'description' => "Admin '{$admin->f_name} {$admin->l_name}' logged out. Session duration: {$sessionDuration}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
