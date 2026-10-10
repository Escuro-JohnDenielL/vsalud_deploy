{{--
    Shared table empty state (renders a full-width row).
    Usage: <x-ui.empty-row :colspan="9" message="No cancellation requests found." />
    Optional action: pass :action-url and :action-label.
--}}
@props(['colspan', 'message' => 'Nothing to show yet.', 'actionUrl' => null, 'actionLabel' => null])

<tr class="vs-empty-row">
    <td colspan="{{ $colspan }}">
        <div class="vs-empty">
            <p class="vs-empty__msg">{{ $message }}</p>
            @if ($actionUrl && $actionLabel)
                <div class="vs-empty__action">
                    <a href="{{ $actionUrl }}" class="admin-btn admin-btn-primary">{{ $actionLabel }}</a>
                </div>
            @endif
        </div>
    </td>
</tr>
