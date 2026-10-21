{{--
    Shared row actions: a quiet "View" control plus a "..." overflow menu.
    Destructive actions belong in the menu with class
    "vs-overflow__item vs-overflow__item--danger" (keeps their own confirmation).
    If the menu slot renders nothing (every item is behind a failing
    condition), the "..." toggle is not rendered at all.

    Usage:
        <x-ui.row-actions>
            <x-slot:view>
                <button type="button" class="vs-row-actions__view" ...>View</button>
            </x-slot:view>
            <x-slot:menu>
                <button type="button" class="vs-overflow__item">Reply</button>
                <div class="vs-overflow__separator"></div>
                <button type="button" class="vs-overflow__item vs-overflow__item--danger">Delete</button>
            </x-slot:menu>
        </x-ui.row-actions>
--}}
@props(['label' => 'More actions'])

@php
    // Render the menu once so an empty slot (all items hidden by conditions)
    // does not leave a "..." button that opens an empty white box.
    $menuHtml = isset($menu) ? trim((string) $menu) : '';
@endphp

<div class="vs-row-actions">
    @isset($view)
        {{ $view }}
    @endisset

    @if ($menuHtml !== '')
        <div class="vs-overflow" data-overflow>
            <button type="button" class="vs-overflow__toggle" data-overflow-toggle
                    aria-haspopup="menu" aria-expanded="false" aria-label="{{ $label }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                    <circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/>
                </svg>
            </button>
            <div class="vs-overflow__menu" data-overflow-menu role="menu" hidden>
                {!! $menuHtml !!}
            </div>
        </div>
    @endif
</div>
