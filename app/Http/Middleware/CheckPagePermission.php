<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CheckPagePermission
{
    /**
     * Page slug mapping: route name → permission slug.
     * Add new admin routes here as they are created.
     */
    /**
     * Routes an admin may always use on their own account, regardless of the
     * pages granted to them: profile, password, own activity log and the
     * authentication entry points.
     */
    public const SELF_SERVICE_ROUTES = [
        'admin.profile',
        'admin.profile.update',
        'admin.password.send-code',
        'admin.password.change',
        'admin.activities',
        'admin.activities.store',
        'admin.login',
        'admin.login.submit',
        'admin.logout',
    ];

    const ROUTE_PAGE_MAP = [
        'admin.home'           => 'packages',
        'admin.reserve.create' => 'reservations',
        'admin.reserve.store'  => 'reservations',
        'admin.inquiry'        => 'inquiries',
        'admin.reserve-logs'   => 'reserve-logs',
        'admin.report'         => 'reports',
        'admin.feedback'       => 'feedback',
        'admin.feedback.destroy' => 'feedback',
        'admin.feedback.bulk-destroy' => 'feedback',
        // Packages (the real names carry the "admin." group prefix twice)
        'admin.admin.packages.store'   => 'packages',
        'admin.admin.packages.destroy' => 'packages',
        'admin.packages.index'         => 'packages',
        'admin.packages.update'        => 'packages',
        // Inquiry sub-routes
        'admin.inquiry.store'  => 'inquiries',
        'admin.inquiries.draft-reply' => 'inquiries',
        'admin.inquiries.update-status' => 'inquiries',
        'admin.send-reply'     => 'inquiries',
        // Reservations
        'admin.reservations.show'      => 'reservations',
        'admin.reservations.total'     => 'reservations',
        'admin.reservations.status'    => 'reservations',
        'admin.reservations.delete'    => 'reservations',
        'admin.reservations.ai-brief'  => 'reservations',
        // Reports data endpoints
        'admin.inquiry.data'     => 'reports',
        'admin.reservation.data' => 'reports',
        'admin.theme.data'       => 'reports',
        'admin.event-type.data'  => 'reports',
        'admin.activities.all'   => 'reports',
        // Availability
        'admin.availability.index'           => 'availability',
        'admin.availability.toggle'          => 'availability',
        'admin.availability.remove-override' => 'availability',
        // Payments
        'admin.payment-logs'            => 'payment-logs',
        'admin.payments.status'         => 'payment-logs',
        'admin.receipts.show'           => 'payment-logs',
        'admin.payment-settings'        => 'payment-settings',
        'admin.payment-settings.update' => 'payment-settings',
        // Cancellations
        'admin.cancellations'         => 'cancellations',
        'admin.cancellations.approve' => 'cancellations',
        'admin.cancellations.deny'    => 'cancellations',
        // Incident logs
        'admin.incident-logs'         => 'incident-logs',
        'admin.incident-logs.store'   => 'incident-logs',
        'admin.incident-logs.show'    => 'incident-logs',
        'admin.incident-logs.update'  => 'incident-logs',
        'admin.incident-logs.destroy' => 'incident-logs',
        // Waitlist
        'admin.waitlist'         => 'waitlist',
        // Audit trail (every action of every admin)
        'admin.audit-logs'        => 'audit-logs',
        'admin.audit-logs.export' => 'audit-logs',
        'admin.audit-logs.show'   => 'audit-logs',
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
            $this->logDeniedAccess($request, $admin, $pageSlug);

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

    /**
     * Being refused a page is security-relevant, so it is written to the audit
     * trail even though the page itself was never reached. Also stops
     * LogAdminActivity from recording the redirect as a successful action.
     */
    private function logDeniedAccess(Request $request, $admin, string $pageSlug): void
    {
        try {
            $routeName = $request->route()?->getName();

            ActivityLog::create([
                'admin_id' => $admin->admin_id,
                'activity_type' => 'Access Denied',
                'category' => 'security',
                'route_name' => $routeName ? Str::limit($routeName, 150, '') : null,
                'http_method' => $request->method(),
                'subject' => Str::limit('Page: ' . $pageSlug, 191, ''),
                'description' => sprintf(
                    'Admin %s %s was denied access to the "%s" page (%s /%s)',
                    $admin->f_name,
                    $admin->l_name,
                    $pageSlug,
                    $request->method(),
                    $request->path()
                ),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
