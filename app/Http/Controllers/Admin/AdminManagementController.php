<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
class AdminManagementController extends Controller
{
    /**
     * IT Dashboard — show stats, create form, and full admin list.
     */
    public function index()
    {
        $adminCount = Admin::count();
        $activeAdminCount = Admin::whereNull('deleted_at')->count();
        $deletedAdminCount = Admin::onlyTrashed()->count();

        $admins = Admin::withTrashed()->latest('admin_id')->paginate(20);

        return view('admin.it.dashboard', compact(
            'adminCount',
            'activeAdminCount',
            'deletedAdminCount',
            'admins',
        ));
    }

    /**
     * Store a new admin account (always regular 'admin' role — super_admin via DB only).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'           => 'required|email|unique:admin,email',
            'f_name'          => 'required|string|max:50',
            'l_name'          => 'required|string|max:50',
            'phone'           => 'required|digits:11',
            'password'        => 'required|min:8|confirmed|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};:"\\|,.<>\/?]).+$/',
            // 'profile_picture' => 'nullable|in:default.png,boy.png,boy1.png,boy2.png,girl.png,girl1.png,girl2.png', // temporarily removed
        ]);

        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first())->withInput();
        }

        Admin::create([
            'email'           => $request->email,
            'f_name'          => $request->f_name,
            'l_name'          => $request->l_name,
            'phone'           => $request->phone,
            'password'        => Hash::make($request->password),
            // 'profile_picture' => $request->input('profile_picture', 'default.png'), // temporarily removed
            'role'            => 'admin', // always regular admin via UI
        ]);

        return back()->with('success', 'Admin account created successfully.');
    }

    /**
     * Show the edit form for a specific admin.
     */
    public function edit($id)
    {
        $admin = Admin::withTrashed()->findOrFail($id);
        return view('admin.it.edit', compact('admin'));
    }

    /**
     * Update an admin account.
     */
    public function update(Request $request, $id)
    {
        $admin = Admin::withTrashed()->findOrFail($id);

        $rules = [
            'f_name' => 'required|string|max:50',
            'l_name' => 'required|string|max:50',
            'phone'  => 'required|digits:11',
            // 'profile_picture' => 'nullable|in:default.png,boy.png,boy1.png,boy2.png,girl.png,girl1.png,girl2.png', // temporarily removed
        ];

        // Only validate email uniqueness if it changed
        if ($request->email !== $admin->email) {
            $rules['email'] = 'required|email|unique:admin,email';
        } else {
            $rules['email'] = 'required|email';
        }

        // Password is optional on update
        if ($request->filled('password')) {
            $rules['password'] = 'min:8|confirmed|regex:/^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};:"\\|,.<>\/?]).+$/';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return back()->with('error', $validator->errors()->first())->withInput();
        }

        $data = [
            'email'           => $request->email,
            'f_name'          => $request->f_name,
            'l_name'          => $request->l_name,
            'phone'           => $request->phone,
            // 'profile_picture' => $request->input('profile_picture', $admin->profile_picture), // temporarily removed
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // Never change role via this update — super_admin is DB-only
        $admin->update($data);

        return redirect()->route('admin.it.dashboard')->with('success', 'Admin account updated successfully.');
    }

    /**
     * Soft-delete (deactivate) an admin account.
     */
    public function deactivate($id)
    {
        $admin = Admin::findOrFail($id);

        // Prevent self-deactivation
        if ((int) $admin->admin_id === (int) auth('admin')->id()) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        // Prevent deactivating super_admin
        if ($admin->role === 'super_admin') {
            return back()->with('error', 'Super admin accounts cannot be deactivated.');
        }

        $admin->delete();

        return back()->with('success', "Admin '{$admin->f_name} {$admin->l_name}' has been deactivated.");
    }

    /**
     * Restore a soft-deleted admin account.
     */
    public function restore($id)
    {
        $admin = Admin::onlyTrashed()->findOrFail($id);
        $admin->restore();

        return back()->with('success', "Admin '{$admin->f_name} {$admin->l_name}' has been restored.");
    }

    /**
     * Force-delete a soft-deleted admin account.
     */
    public function forceDelete($id)
    {
        $admin = Admin::onlyTrashed()->findOrFail($id);

        // Prevent deleting super_admin
        if ($admin->role === 'super_admin') {
            return back()->with('error', 'Super admin accounts cannot be permanently deleted.');
        }

        $admin->forceDelete();

        return back()->with('success', 'Admin account has been permanently deleted.');
    }

}
