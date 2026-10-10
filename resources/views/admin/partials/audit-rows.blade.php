{{--
    Audit trail rows.

    Rendered by the initial page load in admin/audit-logs.blade.php. Keep the
    column count (7) and the `.audit-view-btn` hook in sync with the table header
    and with resources/js/admin/audit-logs.js, which loads the detail modal from
    the button's data-id.

    @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $logs
--}}
@forelse($logs as $log)
    @php
        $auditBadgeVariant = match ($log->category_key) {
            'security' => 'danger',
            'auth' => 'info',
            default => 'neutral',
        };
    @endphp
    <tr class="vs-table__row">
        <td class="audit-when vs-cell-nowrap">
            <span class="audit-date">{{ vs_date($log->created_at) }}</span>
            <span class="audit-clock">{{ vs_time($log->created_at) }}</span>
        </td>
        <td>
            <div class="audit-admin">
                <span class="audit-avatar">{{ $log->admin_initials }}</span>
                <span class="audit-admin-name">{{ $log->admin_name }}</span>
            </div>
        </td>
        <td>
            <x-ui.badge :variant="$auditBadgeVariant" class="audit-cat audit-cat-{{ $log->category_key }}">{{ $log->category_label }}</x-ui.badge>
        </td>
        <td class="audit-activity">{{ $log->event_label }}</td>
        <td class="audit-details" title="{{ $log->description }}">
            {{ \Illuminate\Support\Str::limit($log->description, 110) ?: 'None' }}
        </td>
        <td class="audit-ip vs-cell-nowrap">{{ $log->ip_address ?? 'None' }}</td>
        <td class="vs-cell-actions">
            <button type="button"
                class="vs-row-actions__view audit-view-btn"
                data-id="{{ $log->log_id }}">View</button>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7">
            <div class="vs-empty">
                <p class="vs-empty__msg">No audit entries match these filters. Try widening the date range, or clear the search and type.</p>
            </div>
        </td>
    </tr>
@endforelse
