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
     * Display a paginated list of incidents.
     */
    public function index()
    {
        $incidents = IncidentLog::orderBy('detected_at', 'desc')->paginate(15);

        $stats = [
            'open'    => IncidentLog::whereIn('status', ['open', 'investigating'])->count(),
            'resolved' => IncidentLog::whereIn('status', ['resolved', 'closed'])->count(),
            'critical' => IncidentLog::where('severity', 'critical')->where('status', '!=', 'closed')->count(),
            'total'   => IncidentLog::count(),
        ];

        return view('admin.incident-logs', compact('incidents', 'stats'));
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
            'success' => true,
            'message' => 'Incident logged successfully.',
            'incident' => $incident,
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
            if ($validated['resolved_at']) {
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

        return response()->json([
            'success'  => true,
            'message'  => 'Incident updated successfully.',
            'incident' => $incident->fresh(),
        ]);
    }

    /**
     * Delete an incident log entry.
     */
    public function destroy($id)
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
        ]);
    }
}
