{{--
    Audit trail rows.

    Rendered by the initial page load in admin/audit-logs.blade.php. Keep the
    column count (7) and the `.audit-view-btn` hook in sync with the table header
    and with resources/js/admin/audit-logs.js, which loads the detail modal from
    the button's data-id.

    @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $logs
--}}
@forelse($logs as $log)
    <tr>
        <td class="audit-when">
            <span class="audit-date">{{ $log->created_at?->format('M d, Y') ?? '—' }}</span>
            <span class="audit-clock">{{ $log->created_at?->format('g:i:s A') ?? '' }}</span>
        </td>
        <td>
            <div class="audit-admin">
                <span class="audit-avatar">{{ $log->admin_initials }}</span>
                <span class="audit-admin-name">{{ $log->admin_name }}</span>
            </div>
        </td>
        <td>
            <span class="audit-cat audit-cat-{{ $log->category_key }}">{{ $log->category_label }}</span>
        </td>
        <td class="audit-activity">{{ $log->event_label }}</td>
        <td class="audit-details" title="{{ $log->description }}">
            {{ \Illuminate\Support\Str::limit($log->description, 110) ?: '—' }}
        </td>
        <td class="audit-ip">{{ $log->ip_address ?? '—' }}</td>
        <td>
            <button type="button"
                class="admin-btn admin-btn-primary admin-btn-sm audit-view-btn"
                data-id="{{ $log->log_id }}">View</button>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="audit-empty">
            <strong>No audit entries match these filters.</strong>
            <span>Try widening the date range, or clear the search and type.</span>
        </td>
    </tr>
@endforelse
