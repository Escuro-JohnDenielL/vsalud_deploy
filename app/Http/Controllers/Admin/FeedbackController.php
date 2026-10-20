<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FeedbackController extends Controller
{
    /** Largest batch the bulk endpoint accepts in one request. */
    private const MAX_BULK = 100;

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $feedbacks = Feedback::query()
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';
                $query->where(function ($group) use ($like) {
                    $group->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('message', 'like', $like);
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.feedback', compact('feedbacks', 'search'));
    }

    public function destroy($id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->delete();

        return redirect()->route('admin.feedback')->with('success', 'Feedback deleted.');
    }

    /**
     * Delete the feedback entries an admin explicitly selected.
     *
     * The IDs come from the request only, are capped, and are deleted inside one
     * transaction, so nothing the admin did not select is removed. One audit
     * entry records the count and the IDs, never the content.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:' . self::MAX_BULK],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));

        $deleted = DB::transaction(fn () => Feedback::whereIn('id', $ids)->delete());

        ActivityLog::create([
            'admin_id' => $request->user('admin')?->getAuthIdentifier(),
            'activity_type' => 'Deleted Feedback',
            'category' => 'data',
            'route_name' => 'admin.feedback.bulk-destroy',
            'http_method' => 'DELETE',
            'subject' => Str::limit('Deleted ' . $deleted . ' feedback entries', 191, ''),
            'description' => 'Deleted ' . $deleted . ' feedback entries (IDs: ' . implode(', ', $ids) . ')',
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
        ]);

        return redirect()->route('admin.feedback')
            ->with('success', $deleted . ' feedback ' . ($deleted === 1 ? 'entry' : 'entries') . ' deleted.');
    }
}
