<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Audit entries written on behalf of a patron.
 *
 * The audit table has no actor column: activity_log.admin_id is a non-nullable
 * integer with no foreign key, and 0 is already the value used for a failed
 * admin sign-in, so a patron entry is stored with admin_id = 0 and the marker
 * "Patron:" at the front of the description. Changing that would need a new
 * actor column, which is a schema change and therefore a proposal, not code.
 *
 * What goes in is deliberately narrow: the tracking code and the request IP,
 * plus a short statement of what happened. Never a name, email address, phone
 * number, message body or payment detail.
 *
 * Failed and throttled attempts are not written here at all; those belong to
 * the security log (SecurityLog), which is fed by the guards themselves.
 */
class PatronAudit
{
    /** The actor id used for every patron-originated entry. */
    public const ACTOR_ID = 0;

    /** Prefix that makes the actor obvious in the log description. */
    public const PREFIX = 'Patron:';

    /**
     * Write one patron entry. Never throws: auditing must not take the patron's
     * action down with it.
     *
     * @param  string  $activityType  short label, e.g. "Patron Inquiry Submitted"
     * @param  string  $summary       what happened, without any personal detail
     * @param  string|null  $trackingCode  the reservation tracking code, when there is one
     */
    public static function log(
        string $activityType,
        Request $request,
        ?string $trackingCode,
        string $summary,
        ?int $inquiryId = null,
        ?int $reserveId = null
    ): void {
        try {
            $trackingCode = self::cleanTrackingCode($trackingCode);

            ActivityLog::create([
                'admin_id'      => self::ACTOR_ID,
                'activity_type' => $activityType,
                'category'      => 'data',
                'route_name'    => $request->route()?->getName(),
                'http_method'   => $request->method(),
                'subject'       => $trackingCode ? 'Tracking ' . $trackingCode : null,
                'description'   => self::describe($summary, $trackingCode),
                'inquiry_id'    => $inquiryId,
                'reserve_id'    => $reserveId,
                'ip_address'    => $request->ip(),
                'user_agent'    => substr((string) $request->userAgent(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Could not write patron audit entry: ' . $e->getMessage());
        }
    }

    /**
     * The description as it is stored: the "Patron:" marker, the statement of
     * what happened, and the tracking code when there is one. Pure, so the
     * shape can be unit tested without a database.
     */
    public static function describe(string $summary, ?string $trackingCode = null): string
    {
        $trackingCode = self::cleanTrackingCode($trackingCode);

        return self::PREFIX . ' ' . trim($summary)
            . ($trackingCode !== null ? ' [tracking ' . $trackingCode . ']' : '');
    }

    /**
     * A trimmed tracking code, or null when there is none.
     */
    private static function cleanTrackingCode(?string $trackingCode): ?string
    {
        $trackingCode = trim((string) $trackingCode);

        return $trackingCode === '' ? null : $trackingCode;
    }
}
