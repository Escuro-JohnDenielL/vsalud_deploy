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
    <tr class="vs-table__row" data-id="{{ $incident->id }}">
        <td class="vs-cell-nowrap">#{{ $incident->id }}</td>
        <td class="incident-title">{{ \Illuminate\Support\Str::limit($incident->title, 60) }}</td>
        <td>
            @php
                $severityVariant = match($incident->severity) {
                    'critical' => 'danger',
                    'high'     => 'warning',
                    'medium'   => 'info',
                    default    => 'neutral',
                };
            @endphp
            <x-ui.badge :variant="$severityVariant">{{ ucfirst($incident->severity) }}</x-ui.badge>
        </td>
        <td>
            @php
                $statusVariant = match($incident->status) {
                    'open'           => 'warning',
                    'investigating'  => 'info',
                    'resolved'       => 'success',
                    'closed'         => 'neutral',
                    default          => 'neutral',
                };
            @endphp
            <x-ui.badge :variant="$statusVariant">{{ ucfirst($incident->status) }}</x-ui.badge>
        </td>
        <td class="vs-cell-nowrap">{{ $incident->detected_at ? vs_datetime($incident->detected_at) : 'None' }}</td>
        <td class="vs-cell-nowrap">{{ $incident->resolved_at ? vs_datetime($incident->resolved_at) : 'None' }}</td>
        <td>{{ $incident->reported_by ?? 'None' }}</td>
        <td class="vs-cell-actions">
            <x-ui.row-actions>
                <x-slot:view>
                    <button type="button" class="vs-row-actions__view view-incident-btn"
                        data-id="{{ $incident->id }}">View</button>
                </x-slot:view>
                <x-slot:menu>
                    @if(!in_array($incident->status, ['resolved', 'closed']))
                        <button type="button" class="vs-overflow__item resolve-incident-btn"
                            data-id="{{ $incident->id }}">Resolve</button>
                        {{-- The divider belongs to Resolve; without it there is
                             nothing above Delete, so it must not render. --}}
                        <div class="vs-overflow__separator"></div>
                    @endif
                    <button type="button" class="vs-overflow__item vs-overflow__item--danger delete-incident-btn"
                        data-id="{{ $incident->id }}"
                        data-title="{{ $incident->title }}">Delete</button>
                </x-slot:menu>
            </x-ui.row-actions>
        </td>
    </tr>
@empty
    <tr data-empty>
        <td colspan="8">
            <div class="vs-empty">
                <p class="vs-empty__msg">No incidents logged yet.</p>
            </div>
        </td>
    </tr>
@endforelse
