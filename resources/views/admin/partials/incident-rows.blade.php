{{--
    Incident table rows.

    Shared by the initial page render (admin/incident-logs.blade.php) AND by the
    JSON responses in Admin\IncidentLogController, so logging / resolving /
    deleting an incident can redraw the table in place without a page reload.
    Keep every column, badge class and button in sync with the table header
    (8 columns) and with the row buttons handled in resources/js/admin/incident-logs.js.

    @param \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection|array $incidents
--}}
@forelse($incidents as $incident)
    <tr data-id="{{ $incident->id }}">
        <td>#{{ $incident->id }}</td>
        <td class="incident-title">{{ \Illuminate\Support\Str::limit($incident->title, 60) }}</td>
        <td>
            @php
                $severityClass = match($incident->severity) {
                    'critical' => 'danger',
                    'high'     => 'warning',
                    'medium'   => 'info',
                    'low'      => 'success',
                    default    => 'info',
                };
            @endphp
            <span class="badge-modern {{ $severityClass }}">
                {{ ucfirst($incident->severity) }}
            </span>
        </td>
        <td>
            @php
                $statusClass = match($incident->status) {
                    'open'           => 'danger',
                    'investigating'  => 'warning',
                    'resolved'       => 'success',
                    'closed'         => 'info',
                    default          => 'info',
                };
            @endphp
            <span class="badge-modern {{ $statusClass }}">
                {{ ucfirst($incident->status) }}
            </span>
        </td>
        <td>{{ $incident->detected_at?->format('M d, Y g:i A') ?? '—' }}</td>
        <td>{{ $incident->resolved_at ? $incident->resolved_at->format('M d, Y g:i A') : '—' }}</td>
        <td>{{ $incident->reported_by ?? '—' }}</td>
        <td>
            <div class="action-buttons">
                <button class="admin-btn admin-btn-primary admin-btn-sm view-incident-btn"
                    data-id="{{ $incident->id }}">View</button>
                @if(!in_array($incident->status, ['resolved', 'closed']))
                    <button class="admin-btn admin-btn-ghost admin-btn-sm resolve-incident-btn"
                        data-id="{{ $incident->id }}">Resolve</button>
                @endif
                <button class="admin-btn admin-btn-danger admin-btn-sm delete-incident-btn"
                    data-id="{{ $incident->id }}"
                    data-title="{{ $incident->title }}">Delete</button>
            </div>
        </td>
    </tr>
@empty
    <tr data-empty>
        <td colspan="8" class="text-center">No incidents logged yet.</td>
    </tr>
@endforelse
