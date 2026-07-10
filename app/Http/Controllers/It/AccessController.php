<?php

namespace App\Http\Controllers\It;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

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
        $recentAdmins = Admin::latest('admin_id')->take(5)->get();

        return view('it.dashboard', compact('adminCount', 'recentAdmins'));
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
            'password' => ['required', 'min:8', 'confirmed', 'regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};:"\\|,.<>\/?]).+$/'],
            // 'profile_picture' => 'nullable|in:default.png,boy.png,boy1.png,boy2.png,girl.png,girl1.png,girl2.png', // temporarily removed
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
            // 'profile_picture' => $request->input('profile_picture', 'default.png'), // temporarily removed
        ]);

        return back()->with('success', 'Admin account created.');
    }
}
