<?php

namespace App\Http\Controllers\It;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\GuestConsent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AccessController extends Controller
{
    public function showLoginForm()
    {
        return view('it.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $key = strtolower($request->input('email')) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Too many login attempts. Try again in ' . RateLimiter::availableIn($key) . ' seconds.',
                ]);
        }

        $credentials = $request->only('email', 'password');
        $user = User::where('email', $credentials['email'])->first();

        if ($user && $user->role === 'it' && Auth::guard('web')->attempt($credentials)) {
            RateLimiter::clear($key);
            $request->session()->regenerate();

            return redirect()->route('it.dashboard');
        }

        RateLimiter::hit($key, 60);

        return back()
            ->withInput($request->only('email'))
            ->withErrors([
                'email' => 'Invalid IT credentials.',
            ]);
    }

    public function dashboard(Request $request)
    {
        $adminCount = Admin::count();
        $guestCount = GuestConsent::count();

        $recentAdmins = Admin::latest('admin_id')->take(5)->get();
        $recentGuests = GuestConsent::latest('created_at')->take(5)->get();

        return view('it.dashboard', compact('adminCount', 'guestCount', 'recentAdmins', 'recentGuests'));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('it.login');
    }

    public function storeAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:admin,email',
            'f_name' => 'required|string|max:50',
            'l_name' => 'required|string|max:50',
            'phone' => 'required|digits:11',
            'password' => 'required|min:6|confirmed',
            'profile_picture' => 'nullable|in:default.png,boy.png,boy1.png,boy2.png,girl.png,girl1.png,girl2.png',
        ]);

        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first())->withInput();
        }

        Admin::create([
            'email' => $request->email,
            'f_name' => $request->f_name,
            'l_name' => $request->l_name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'profile_picture' => $request->input('profile_picture', 'default.png'),
        ]);

        return back()->with('success', 'Admin account created.');
    }

    public function storeGuest(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'valid_days' => 'required|integer|min:1|max:365',
        ]);

        $guestToken = Str::random(40);
        $expiresAt = now()->addDays((int) $validated['valid_days']);

        GuestConsent::create([
            'guest_token' => $guestToken,
            'label' => $validated['label'],
            'email' => $validated['email'] ?? null,
            'consented_at' => now(),
            'expires_at' => $expiresAt,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return back()->with('guest_token', $guestToken)->with('success', 'Guest pass created.');
    }
}
