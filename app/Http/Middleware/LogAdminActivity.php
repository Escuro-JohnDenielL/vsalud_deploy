<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Writes an audit entry for every state-changing admin request.
 *
 * The audit trail has to cover *everything* an admin does — including pages
 * nobody remembered to instrument — so instead of editing every controller this
 * middleware records the request after it has been handled. Routes that already
 * write a richer entry of their own (a controller calling ActivityLog::create, or
 * the profile page's JS posting to /admin/activities/store) are detected through
 * ActivityLog::wasWritten() and skipped, so no action is ever recorded twice.
 *
 * Registered on the "web" group in bootstrap/app.php; it does nothing unless an
 * admin is signed in and the request can change state.
 */
class LogAdminActivity
{
    /** HTTP methods that can change state. */
    private const MUTATING = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Routes whose action is already written to activity_log elsewhere, or that
     * change nothing at all. Keys are route names with the leading "admin."
     * prefix removed (some routes are named "admin.admin.<x>" — see routes/web.php).
     */
    private const SKIP_ROUTES = [
        'activities.store',       // the profile page's own activity-log writer
        'profile.update',         // profile.js logs the name/email/phone diff itself
        'password.send-code',     // profile.js logs "Password reset code sent/failed"
        'password.change',        // profile.js logs "Password changed successfully"
        'inquiries.draft-reply',  // AI draft — nothing is stored
        'reservations.ai-brief',  // AI brief — nothing is stored
        'mfa.send-setup-otp',     // OTP delivery only, not an admin action
        'mfa.resend-otp',
    ];

    /** Never stored in the trail, and never listed as a changed field. */
    private const SENSITIVE = [
        '_token', '_method', 'password', 'password_confirmation', 'current_password',
        'new_password', 'otp', 'code', 'mfa_secret', 'secret', 'token',
        'remember_token', 'password_reset_code', 'csrf', 'g-recaptcha-response',
    ];

    /**
     * Ordered special cases checked before the generic derivation. Each entry is
     * [fragments that must all appear in the request signature, verb, module].
     */
    private const SPECIAL = [
        [['send-reset-code'], 'Requested', 'Password Reset'],
        [['change-password'], 'Changed', 'Password'],
        [['revoke'], 'Revoked', 'Trusted Device'],
        [['mfa/challenge'], 'Verified', 'MFA Challenge'],
        [['mfa/setup'], 'Enabled', 'MFA'],
        [['mfa/disable'], 'Disabled', 'MFA'],
        [['preset'], 'Applied', 'Page Permission Preset'],
        [['publish'], 'Published', 'Form'],
        [['reorder'], 'Reordered', 'Form Field'],
        [['forms/', 'fields'], 'Edited', 'Form Field'],
        [['update-status'], 'Updated', 'Inquiry Status'],
        [['remove-override'], 'Removed', 'Availability Override'],
    ];

    /** Fallback module labels, keyed by the first route/path segment. */
    private const MODULE_LABELS = [
        'home' => 'Package',
        'packages' => 'Package',
        'inquiry' => 'Inquiry',
        'inquiries' => 'Inquiry',
        'feedback' => 'Feedback',
        'availability' => 'Availability',
        'reserve' => 'Reservation',
        'reservations' => 'Reservation',
        'payments' => 'Payment',
        'payment-settings' => 'Payment Settings',
        'profile' => 'Profile',
        'password' => 'Password',
        'it' => 'Admin Account',
        'permissions' => 'Page Permissions',
        'forms' => 'Form Builder',
        'mfa' => 'MFA',
        'waitlist' => 'Waitlist',
        'cancellations' => 'Cancellation',
        'incident-logs' => 'Incident',
        'send-reply' => 'Email Reply',
        'receipts' => 'Receipt',
        'activities' => 'Activity Log',
    ];

    /** Path segments that describe the action rather than the resource. */
    private const ACTION_SEGMENTS = [
        'store', 'create', 'edit', 'update', 'destroy', 'delete', 'status', 'submit',
        'toggle', 'preset', 'fields', 'reorder', 'publish', 'preview', 'remove-override',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldRecord($request, $response)) {
            try {
                $this->record($request, $response);
            } catch (\Throwable $e) {
                // Auditing must never take the admin's action down with it.
                Log::warning('Could not write admin audit entry: ' . $e->getMessage());
            }
        }

        return $response;
    }

    /**
     * Decide whether this request deserves an entry.
     */
    private function shouldRecord(Request $request, Response $response): bool
    {
        if (! in_array($request->method(), self::MUTATING, true)) {
            return false;
        }

        if (! $request->user('admin')) {
            return false;
        }

        // Something already wrote a richer entry for this request.
        if (ActivityLog::wasWritten($request)) {
            return false;
        }

        if (in_array($this->routeName($request), self::SKIP_ROUTES, true)) {
            return false;
        }

        // A server error is reported by the application log; the action did not finish.
        if ($response->getStatusCode() >= 500) {
            return false;
        }

        return ! $this->wasRejected($response);
    }

    /**
     * True when the request was bounced before the action could happen — the MFA
     * or login gate, or a failed validation. Nothing to audit in those cases.
     */
    private function wasRejected(Response $response): bool
    {
        if (! $response->isRedirect()) {
            return false;
        }

        $target = (string) $response->headers->get('Location');

        foreach (['/admin/login', '/admin/mfa/'] as $gate) {
            if ($target !== '' && str_contains($target, $gate)) {
                return true;
            }
        }

        if (! method_exists($response, 'getSession')) {
            return false;
        }

        $session = $response->getSession();

        if (! $session) {
            return false;
        }

        if ($session->get('error')) {
            return true;
        }

        // Failed validation: the redirect carries the error bag, nothing was saved.
        $errors = $session->get('errors');

        if (! $errors) {
            return false;
        }

        if (is_object($errors) && method_exists($errors, 'any')) {
            return (bool) $errors->any();
        }

        return is_countable($errors) ? count($errors) > 0 : true;
    }

    /**
     * Write the entry.
     */
    private function record(Request $request, Response $response): void
    {
        $admin = $request->user('admin');
        $adminId = $admin->getAuthIdentifier();

        if (! $adminId) {
            return;
        }

        $name = trim((string) ($admin->f_name ?? '') . ' ' . (string) ($admin->l_name ?? ''))
            ?: (string) ($admin->email ?? 'Admin');
        $routeName = (string) $request->route()?->getName();
        $status = $response->getStatusCode();

        if ($status === 403) {
            $this->write([
                'admin_id' => $adminId,
                'activity_type' => 'Access Denied',
                'category' => 'security',
                'route_name' => Str::limit($routeName, 150, ''),
                'http_method' => $request->method(),
                'subject' => null,
                'description' => "Admin {$name} was denied access to {$request->method()} /{$request->path()}",
            ], $request);

            return;
        }

        [$verb, $module] = $this->action($request, $routeName);
        $subject = $this->subject($request, $module);
        $fields = $this->changedFields($request);

        $description = "Admin {$name} " . Str::lower($verb) . ' ' . ($subject ?: $module);

        if ($fields) {
            $description .= ' — fields: ' . $fields;
        }

        $this->write([
            'admin_id' => $adminId,
            'activity_type' => Str::limit("{$verb} {$module}", 50, ''),
            // Derived from the type + route, so the trail is categorised even when
            // a call site writes activity_type directly.
            'category' => ActivityLog::categoryFor("{$verb} {$module}", $routeName),
            'route_name' => Str::limit($routeName, 150, ''),
            'http_method' => $request->method(),
            'subject' => $subject ? Str::limit($subject, 191, '') : null,
            'description' => Str::limit($description, 900, '…'),
        ], $request);
    }

    private function write(array $attributes, Request $request): void
    {
        ActivityLog::create($attributes + [
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);
    }

    /**
     * Resolve the verb and module label describing the action.
     *
     * @return array{0: string, 1: string}
     */
    private function action(Request $request, string $routeName): array
    {
        $signature = Str::lower($request->method() . ' ' . $request->path() . ' ' . $routeName);

        foreach (self::SPECIAL as [$fragments, $verb, $module]) {
            $matches = true;

            foreach ($fragments as $fragment) {
                if (! str_contains($signature, $fragment)) {
                    $matches = false;
                    break;
                }
            }

            if ($matches) {
                // "Edited Form Field" is refined by the HTTP method.
                if ($verb === 'Edited') {
                    $verb = match ($request->method()) {
                        'POST' => 'Added',
                        'PUT', 'PATCH' => 'Updated',
                        'DELETE' => 'Deleted',
                        default => 'Edited',
                    };
                }

                return [$verb, $module];
            }
        }

        return [$this->verb($signature, $request->method()), $this->module($request, $routeName)];
    }

    /**
     * Verb inferred from what the request itself says it is doing.
     */
    private function verb(string $signature, string $method): string
    {
        $checks = [
            'force-delete' => 'Deleted',
            'destroy' => 'Deleted',
            'delete' => 'Deleted',
            'approve' => 'Approved',
            'deny' => 'Denied',
            'restore' => 'Restored',
            'deactivate' => 'Deactivated',
            'disable' => 'Disabled',
            'revoke' => 'Revoked',
            'toggle' => 'Toggled',
            'publish' => 'Published',
            'reorder' => 'Reordered',
            'remove' => 'Removed',
            'password' => 'Changed',
            'challenge' => 'Verified',
            'setup' => 'Enabled',
            'send' => 'Sent',
            'store' => 'Created',
            'create' => 'Created',
            'update' => 'Updated',
            'status' => 'Updated',
            'change' => 'Updated',
            'edit' => 'Updated',
        ];

        foreach ($checks as $needle => $verb) {
            if (str_contains($signature, $needle)) {
                return $verb;
            }
        }

        return match ($method) {
            'DELETE' => 'Deleted',
            'POST' => 'Submitted',
            default => 'Updated',
        };
    }

    /**
     * Human-readable label for the thing being acted on: the route name's first
     * segment when the route is named, otherwise the first meaningful URL segment
     * (packages/{id} → "Package").
     */
    private function module(Request $request, string $routeName): string
    {
        $name = preg_replace('/^(admin\.)+/i', '', $routeName) ?? '';

        if ($name !== '') {
            $key = explode('.', $name)[0];

            if (isset(self::MODULE_LABELS[$key])) {
                return self::MODULE_LABELS[$key];
            }

            if ($key !== '') {
                return Str::headline($key);
            }
        }

        foreach (explode('/', trim($request->path(), '/')) as $position => $segment) {
            if ($position === 0 || $segment === '') {
                continue; // leading "admin"
            }

            if (preg_match('/^\d+$/', $segment) || Str::isUuid($segment)) {
                continue; // resource id
            }

            if (in_array($segment, self::ACTION_SEGMENTS, true)) {
                continue;
            }

            return self::MODULE_LABELS[$segment] ?? Str::headline(str_replace('-', ' ', $segment));
        }

        return 'Admin Panel';
    }

    /**
     * Short label of the affected record, e.g. "Package #4".
     */
    private function subject(Request $request, string $module): ?string
    {
        foreach (array_reverse($request->route()?->parameters() ?? []) as $value) {
            $id = is_object($value) && method_exists($value, 'getKey') ? $value->getKey() : $value;

            if (is_scalar($id) && preg_match('/^[\w\-\.]{1,40}$/', (string) $id)) {
                return $module . ' #' . $id;
            }
        }

        return null;
    }

    /**
     * Names of the submitted fields, so the entry shows what actually changed.
     * Values are never stored — only the field names.
     */
    private function changedFields(Request $request): ?string
    {
        $keys = array_keys($request->except(self::SENSITIVE));

        foreach (array_keys($request->allFiles()) as $file) {
            $keys[] = $file;
        }

        $keys = array_values(array_unique(array_filter(
            $keys,
            fn ($key) => is_string($key) && $key !== '' && ! str_starts_with($key, '_')
        )));

        if (! $keys) {
            return null;
        }

        return Str::limit(implode(', ', array_slice($keys, 0, 12)), 240, '…');
    }

    /**
     * Route name with any leading "admin." prefixes removed.
     */
    private function routeName(Request $request): string
    {
        return preg_replace('/^(admin\.)+/i', '', (string) $request->route()?->getName()) ?? '';
    }
}
