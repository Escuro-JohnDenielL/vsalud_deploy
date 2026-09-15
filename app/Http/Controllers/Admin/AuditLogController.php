<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Audit Logs — a read-only, cross-admin view of everything that happened in the
 * admin panel: sign-ins, incidents, permission changes, records created and
 * deleted, configuration edits, and refused access attempts.
 *
 * The page is deliberately append-only: nothing here can edit or delete an
 * entry, so the trail stays trustworthy.
 */
class AuditLogController extends Controller
{
    /** Entries per page. */
    private const PER_PAGE = 25;

    /** Upper bound for CSV exports so a huge range cannot exhaust memory. */
    private const MAX_EXPORT = 10000;

    public function index(Request $request)
    {
        $filters = $this->filters($request);

        $logs = $this->filtered($filters)
            ->with(['admin' => fn ($query) => $query->withTrashed()])
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('admin.audit-logs', [
            'logs' => $logs,
            'filters' => $filters,
            'stats' => $this->stats(),
            'categories' => ActivityLog::CATEGORIES,
            'admins' => $this->adminOptions(),
        ]);
    }

    /**
     * Full detail for one entry — used by the page's detail modal.
     */
    public function show(string $log)
    {
        $entry = ActivityLog::with(['admin' => fn ($query) => $query->withTrashed()])
            ->find($log);

        if (! $entry) {
            return response()->json(['message' => 'Audit entry not found.'], 404);
        }

        return response()->json([
            'log_id' => $entry->log_id,
            'activity_type' => $entry->activity_type,
            'event_label' => $entry->event_label,
            'description' => $entry->description,
            'category_key' => $entry->category_key,
            'category_label' => $entry->category_label,
            'admin_name' => $entry->admin_name,
            'admin_email' => optional($entry->admin)->email,
            'admin_role' => optional($entry->admin)->role,
            'ip_address' => $entry->ip_address,
            'user_agent' => $entry->user_agent,
            'route_name' => $entry->route_name,
            'http_method' => $entry->http_method,
            'subject' => $entry->subject,
            'inquiry_id' => $entry->inquiry_id,
            'reserve_id' => $entry->reserve_id,
            'created_at' => $entry->created_at?->format('M d, Y g:i:s A'),
            'created_at_iso' => $entry->created_at?->toIso8601String(),
        ]);
    }

    /**
     * Download the currently filtered view as CSV (thesis / compliance evidence).
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $filename = 'audit-log-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');

            // BOM so Excel opens the file as UTF-8.
            fwrite($out, "\xEF\xBB\xBF");

            $this->putCsvRow($out, [
                'Timestamp', 'Category', 'Activity', 'Description', 'Admin', 'Email', 'Role',
                'Method', 'Route', 'Target', 'IP address', 'Related inquiry', 'Related reservation',
                'User agent',
            ]);

            $this->filtered($filters)
                ->with(['admin' => fn ($query) => $query->withTrashed()])
                ->limit(self::MAX_EXPORT)
                ->cursor()
                ->each(function (ActivityLog $entry) use ($out) {
                    $this->putCsvRow($out, [
                        $entry->created_at?->format('Y-m-d H:i:s'),
                        $entry->category_label,
                        $entry->activity_type,
                        $entry->description,
                        $entry->admin_name,
                        optional($entry->admin)->email,
                        optional($entry->admin)->role,
                        $entry->http_method,
                        $entry->route_name,
                        $entry->subject,
                        $entry->ip_address,
                        $entry->inquiry_id,
                        $entry->reserve_id,
                        $entry->user_agent,
                    ]);
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ------------------------------------------------------------------
    // Query building
    // ------------------------------------------------------------------

    /**
     * Newest first; log_id breaks ties so pagination stays stable.
     */
    private function filtered(array $filters)
    {
        $query = ActivityLog::query()
            ->search($filters['q'])
            ->category($filters['category'])
            ->orderByDesc('created_at')
            ->orderByDesc('log_id');

        if ($filters['admin_id'] !== null) {
            $query->where('admin_id', $filters['admin_id']);
        }

        if ($filters['from']) {
            $query->where('created_at', '>=', $filters['from'] . ' 00:00:00');
        }

        if ($filters['to']) {
            $query->where('created_at', '<=', $filters['to'] . ' 23:59:59');
        }

        return $query;
    }

    /**
     * Validated, trimmed filter values coming from the query string.
     */
    private function filters(Request $request): array
    {
        $category = (string) $request->input('category', 'all');

        if ($category !== 'all' && ! isset(ActivityLog::CATEGORIES[$category])) {
            $category = 'all';
        }

        return [
            'q' => Str::limit(trim((string) $request->input('q', '')), 100, ''),
            'category' => $category,
            'admin_id' => $request->filled('admin_id') ? (int) $request->input('admin_id') : null,
            'from' => $this->date($request->input('from')),
            'to' => $this->date($request->input('to')),
        ];
    }

    /**
     * Summary counters for the stat cards.
     */
    private function stats(): array
    {
        return [
            'total' => ActivityLog::count(),
            'today' => ActivityLog::where('created_at', '>=', Carbon::today())->count(),
            'sensitive' => ActivityLog::whereIn('category', ['auth', 'security'])->count(),
            'admins' => ActivityLog::where('admin_id', '>', 0)->distinct()->count('admin_id'),
        ];
    }

    /**
     * Admins that appear in the filter dropdown, including deactivated accounts.
     */
    private function adminOptions(): array
    {
        $options = Admin::withTrashed()
            ->orderBy('f_name')
            ->orderBy('l_name')
            ->get(['admin_id', 'f_name', 'l_name', 'email'])
            ->map(fn (Admin $admin) => [
                'id' => $admin->admin_id,
                'label' => trim($admin->f_name . ' ' . $admin->l_name) ?: $admin->email,
                'deactivated' => $admin->trashed(),
            ])
            ->all();

        // Failed sign-ins are stored with admin_id 0 because no account matched.
        if (ActivityLog::where('admin_id', 0)->exists()) {
            array_unshift($options, [
                'id' => 0,
                'label' => 'Not signed in (failed sign-ins)',
                'deactivated' => false,
            ]);
        }

        return $options;
    }

    private function date(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * fputcsv with explicit delimiter/enclosure/escape, so PHP 8.4 does not warn.
     */
    private function putCsvRow($handle, array $row): void
    {
        fputcsv($handle, $row, ',', '"', '');
    }
}
