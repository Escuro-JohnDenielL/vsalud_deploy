<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminPagePermission;
use Illuminate\Http\Request;

class AdminPermissionController extends Controller
{
    /**
     * Show the permission management screen for a given admin.
     */
    public function edit($id)
    {
        $admin = Admin::findOrFail($id);

        // Prevent editing permissions of super_admin
        if ($admin->role === 'super_admin') {
            return redirect()->route('admin.it.dashboard')
                ->with('error', 'Super admin permissions cannot be modified.');
        }

        $availablePages = AdminPagePermission::availablePages();
        $grantedPages = AdminPagePermission::where('admin_id', $admin->admin_id)
            ->pluck('page_slug')
            ->toArray();
        $presets = AdminPagePermission::presets();

        return view('admin.it.permissions', compact(
            'admin',
            'availablePages',
            'grantedPages',
            'presets'
        ));
    }

    /**
     * Update permissions for a given admin.
     */
    public function update(Request $request, $id)
    {
        $admin = Admin::findOrFail($id);

        if ($admin->role === 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Super admin permissions cannot be modified.',
            ], 422);
        }

        $validated = $request->validate([
            'pages' => 'nullable|array',
            'pages.*' => 'string|in:' . implode(',', array_keys(AdminPagePermission::availablePages())),
        ]);

        // Sync permissions: delete existing, insert new
        AdminPagePermission::where('admin_id', $admin->admin_id)->delete();

        $pages = $validated['pages'] ?? [];
        $data = [];
        foreach ($pages as $slug) {
            $data[] = [
                'admin_id'  => $admin->admin_id,
                'page_slug' => $slug,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($data)) {
            AdminPagePermission::insert($data);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permissions updated successfully for ' . $admin->f_name . ' ' . $admin->l_name . '.',
        ]);
    }

    /**
     * Apply a preset to an admin's permissions.
     */
    public function applyPreset(Request $request, $id)
    {
        $admin = Admin::findOrFail($id);

        if ($admin->role === 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Super admin permissions cannot be modified.',
            ], 422);
        }

        $validated = $request->validate([
            'preset' => 'required|string|in:' . implode(',', array_keys(AdminPagePermission::presets())),
        ]);

        $preset = AdminPagePermission::presets()[$validated['preset']];

        // Replace all permissions with preset pages
        AdminPagePermission::where('admin_id', $admin->admin_id)->delete();

        $data = [];
        foreach ($preset['pages'] as $slug) {
            $data[] = [
                'admin_id'  => $admin->admin_id,
                'page_slug' => $slug,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($data)) {
            AdminPagePermission::insert($data);
        }

        return response()->json([
            'success' => true,
            'message' => "Preset '{$preset['label']}' applied to {$admin->f_name} {$admin->l_name}.",
            'pages'   => $preset['pages'],
        ]);
    }
}
