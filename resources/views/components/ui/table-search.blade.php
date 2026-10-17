{{--
    Shared table search toolbar (admin list pages).
    Server-side search: submits ?search= to the given action and keeps the
    value after the page reloads.
    Usage:
        <x-ui.table-search
            :action="route('admin.inquiry')"
            :value="$search"
            label="Search inquiries"
            placeholder="Name, email, or tracking code"
            input-id="inquirySearch" />
--}}
@props([
    'action',
    'value' => '',
    'label' => 'Search',
    'placeholder' => 'Name, email, or code',
    'inputId' => 'tableSearch',
])

<form class="vs-toolbar vs-table-search" method="GET" action="{{ $action }}" role="search">
    <div class="vs-field">
        <label class="vs-label" for="{{ $inputId }}">{{ $label }}</label>
        <input
            type="search"
            id="{{ $inputId }}"
            name="search"
            value="{{ $value }}"
            class="vs-input vs-table-search__input"
            placeholder="{{ $placeholder }}">
    </div>
    <div class="vs-table-search__actions">
        <button type="submit" class="admin-btn admin-btn-primary">Search</button>
        @if ($value !== '')
            <a href="{{ $action }}" class="admin-btn admin-btn-ghost">Clear</a>
        @endif
    </div>
</form>
