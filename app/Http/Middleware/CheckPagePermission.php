<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPagePermission
{
    /**
     * Page slug mapping: route name → permission slug.
     * Add new admin routes here as they are created.
     */
    const ROUTE_PAGE_MAP = [
        'admin.home'           => 'packages',
        'admin.reserve.create' => 'reservations',
        'admin.reserve.store'  => 'reservations',
        'admin.inquiry'        => 'inquiries',
        'admin.reserve-logs'   => 'reserve-logs',
        'admin.report'         => 'reports',
        'admin.feedback'       => 'feedback',
        // Package CRUD
        'admin.packages.destroy' => 'packages',
        'admin.packages.store'   => 'packages',
        // Inquiry sub-routes
        'admin.inquiry.store'  => 'inquiries',
        // Reports data endpoints
        'admin.inquiry.data'     => 'reports',
        'admin.reservation.data' => 'reports',
        'admin.theme.data'       => 'reports',
        'admin.event-type.data'  => 'reports',
        'admin.activities.all'   => 'reports',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin');

        if (!$admin) {
            abort(403, 'Unauthenticated.');
        }

        // Super admin bypasses all permission checks
        if ($admin->role === 'super_admin') {
            return $next($request);
        }

        // Profile page is always accessible
        if ($request->route()?->getName() === 'admin.profile') {
            return $next($request);
        }

        // IT management is super_admin only (already gated by role middleware)
        if (str_starts_with($request->path(), 'admin/it')) {
            return $next($request);
        }

        // Look up the page slug for this route
        $routeName = $request->route()?->getName();
        $pageSlug = self::ROUTE_PAGE_MAP[$routeName] ?? null;

        if (!$pageSlug) {
            // Route not in the map — allow (or log warning)
            return $next($request);
        }

        // Check if this admin has explicit permission for this page
        $hasPermission = \App\Models\AdminPagePermission::where('admin_id', $admin->admin_id)
            ->where('page_slug', $pageSlug)
            ->exists();

        if (!$hasPermission) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. You do not have permission for this page.',
                ], 403);
            }

            return redirect()->route('admin.profile')
                ->with('error', 'You do not have permission to access that page.');
        }

        return $next($request);
    }
}
