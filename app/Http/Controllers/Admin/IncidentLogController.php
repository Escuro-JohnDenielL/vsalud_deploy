<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IncidentLog;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IncidentLogController extends Controller
{
    /**
     * Rows shown per page.
     */
    private const PER_PAGE = 15;

    /**
     * Display a paginated list of incidents.
     */
    public function index()
    {
        $incidents = $this->orderedQuery()->paginate(self::PER_PAGE);

        $stats = $this->stats();

        return view('admin.incident-logs', compact('incidents', 'stats'));
    }

    /**
     * Newest first. `id` breaks ties so that two incidents sharing the same
     * `detected_at` (the form's default is minute-precision) still show the most
     * recently logged one on top.
     */
    private function orderedQuery()
    {
        return IncidentLog::orderByDesc('detected_at')->orderByDesc('id');
    }

    /**
     * Summary counters shown in the stat cards.
     */
    private function stats(): array
    {
        return [
            'open'     => IncidentLog::whereIn('status', ['open', 'investigating'])->count(),
            'resolved' => IncidentLog::whereIn('status', ['resolved', 'closed'])->count(),
            'critical' => IncidentLog::where('severity', 'critical')->where('status', '!=', 'closed')->count(),
            'total'    => IncidentLog::count(),
        ];
    }

    /**
     * Fresh stats + table rows + pagination links.
     *
     * Returned with every write so the client can redraw the table in place
     * instead of reloading the whole page just to see the change it made.
     */
    private function tablePayload(int $page = 1): array
    {
        $incidents = $this->orderedQuery()->paginate(self::PER_PAGE, ['*'], 'page', max(1, $page));

        // Deleting the last row of the last page would otherwise leave the client
        // showing an empty table — fall back to the new last page.
        if ($incidents->isEmpty() && $incidents->currentPage() > 1) {
            $incidents = $this->orderedQuery()->paginate(self::PER_PAGE, ['*'], 'page', $incidents->lastPage());
        }

        return [
            'stats'      => $this->stats(),
            'rows'       => view('admin.partials.incident-rows', ['incidents' => $incidents])->render(),
            'pagination' => $incidents->links()->toHtml(),
            'page'       => $incidents->currentPage(),
        ];
    }

    /**
     * Store a new incident log entry.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'severity'    => 'required|in:low,medium,high,critical',
            'detected_at' => 'required|date',
            'reported_by' => 'nullable|string|max:100',
        ]);

        $incident = IncidentLog::create([
            'title'       => $validated['title'],
            'description' => $validated['description'],
            'severity'    => $validated['severity'],
            'status'      => 'open',
            'detected_at' => $validated['detected_at'],
            'reported_by' => $validated['reported_by'] ?? null,
        ]);

        $admin = Auth::guard('admin')->user();
        ActivityLog::create([
            'admin_id'      => $admin->admin_id,
            'activity_type' => 'Incident Logged',
            'description'   => "{$admin->f_name} {$admin->l_name} logged incident #{$incident->id}: {$incident->title}",
        ]);

        return response()->json([
            'success'  => true,
            'message'  => 'Incident logged successfully.',
            'incident' => $incident,
            'focus_id' => $incident->id,
            // Always return page 1: the incident just logged sorts to the top of it.
            'payload'  => $this->tablePayload(1),
        ]);
    }

    /**
     * Get a single incident's details (for AJAX).
     */
    public function show($id)
    {
        $incident = IncidentLog::findOrFail($id);
        return response()->json($incident);
    }

    /**
     * Update an incident (resolve, change status, add resolution notes).
     */
    public function update(Request $request, $id)
    {
        $incident = IncidentLog::findOrFail($id);

        $validated = $request->validate([
            'status'      => 'required|in:open,investigating,resolved,closed',
            'resolution'  => 'nullable|string',
            'resolved_at' => 'nullable|date',
        ]);

        $incident->status = $validated['status'];

        if ($validated['status'] === 'resolved' || $validated['status'] === 'closed') {
            if (!empty($validated['resolved_at'])) {
                $incident->resolved_at = $validated['resolved_at'];
            } elseif (!$incident->resolved_at) {
                $incident->resolved_at = now();
            }
        }

        if ($request->has('resolution')) {
            $incident->resolution = $validated['resolution'];
        }

        $incident->save();

        $admin = Auth::guard('admin')->user();
        ActivityLog::create([
            'admin_id'      => $admin->admin_id,
            'activity_type' => 'Incident Updated',
            'description'   => "{$admin->f_name} {$admin->l_name} updated incident #{$incident->id} to status: {$incident->status}",
        ]);

        $page = (int) $request->query('page', 1);

        return response()->json([
            'success'  => true,
            'message'  => 'Incident updated successfully.',
            'incident' => $incident->fresh(),
            'focus_id' => $incident->id,
            'payload'  => $this->tablePayload($page),
        ]);
    }

    /**
     * Delete an incident log entry.
     */
    public function destroy(Request $request, $id)
    {
        $incident = IncidentLog::findOrFail($id);

        $admin = Auth::guard('admin')->user();
        ActivityLog::create([
            'admin_id'      => $admin->admin_id,
            'activity_type' => 'Incident Deleted',
            'description'   => "{$admin->f_name} {$admin->l_name} deleted incident #{$incident->id}: {$incident->title}",
        ]);

        $incident->delete();

        return response()->json([
            'success' => true,
            'message' => 'Incident deleted successfully.',
            'payload' => $this->tablePayload((int) $request->query('page', 1)),
        ]);
    }
}
