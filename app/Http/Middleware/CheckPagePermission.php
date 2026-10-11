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

    /**
     * Landing pages in sidebar order. The first one an admin may open is where
     * they land after login, and where a denied page sends them.
     */
    public const LANDING_ORDER = [
        'reports'       => 'admin.report',
        'reservations'  => 'admin.reserve.create',
        'cancellations' => 'admin.cancellations',
        'waitlist'      => 'admin.waitlist',
        'payment-logs'  => 'admin.payment-logs',
        'packages'      => 'admin.home',
        'inquiries'     => 'admin.inquiry',
        'feedback'      => 'admin.feedback',
        'reserve-logs'  => 'admin.reserve-logs',
        'audit-logs'    => 'admin.audit-logs',
        'incident-logs' => 'admin.incident-logs',
    ];

    /**
     * True when the admin holds the page slug. Super admin holds everything.
     */
    public static function adminHas($admin, string $slug): bool
    {
        if ($admin && $admin->role === 'super_admin') {
            return true;
        }

        return $admin
            ? \App\Models\AdminPagePermission::where('admin_id', $admin->admin_id)
                ->where('page_slug', $slug)
                ->exists()
            : false;
    }

    /**
     * The first page this admin may open, or the profile when they hold none.
     */
    public static function landingUrl($admin): string
    {
        foreach (self::LANDING_ORDER as $slug => $routeName) {
            if (self::adminHas($admin, $slug)) {
                return route($routeName);
            }
        }

        return route('admin.profile');
    }

    /**
     * Post-login destination. The intended URL is used only when it resolves to
     * a route the admin is permitted to see, and the answer is rebuilt from the
     * route, so a tampered intended value is never followed as a URL.
     */
    public static function postLoginUrl($admin, ?string $intended): string
    {
        if ($intended) {
            try {
                $route = \Illuminate\Support\Facades\Route::getRoutes()->match(
                    \Illuminate\Http\Request::create($intended)
                );
            } catch (\Throwable $e) {
                $route = null;
            }

            if ($route && count($route->parameterNames()) === 0) {
                $name = $route->getName();

                if ($name && in_array($name, self::SELF_SERVICE_ROUTES, true)) {
                    return route($name);
                }

                $slug = $name ? (self::ROUTE_PAGE_MAP[$name] ?? null) : null;

                if ($slug && self::adminHas($admin, $slug)) {
                    return route($name);
                }
            }
        }

        return self::landingUrl($admin);
    }

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
        // Self-service routes are always available to the signed-in admin.
        if (in_array($routeName, self::SELF_SERVICE_ROUTES, true)) {
            return $next($request);
        }

        $pageSlug = self::ROUTE_PAGE_MAP[$routeName] ?? null;

        // Deny by default. An unmapped protected route used to be allowed, which
        // left every unlisted admin route open to all admins. Falling through to
        // the check below refuses it, and the route coverage test guarantees no
        // legitimate route lands here.
        if (! $pageSlug) {
            $pageSlug = $routeName ?: 'unmapped';
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

            // Send them to the first page they may open instead of a page they
            // cannot, so a denied landing page is not a dead end.
            return redirect()->to(self::landingUrl($admin))
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
