{{--
    Shared row actions: a quiet "View" control plus a "..." overflow menu.
    Destructive actions belong in the menu with class
    "vs-overflow__item vs-overflow__item--danger" (keeps their own confirmation).

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

<div class="vs-row-actions">
    @isset($view)
        {{ $view }}
    @endisset

    @isset($menu)
        <div class="vs-overflow" data-overflow>
            <button type="button" class="vs-overflow__toggle" data-overflow-toggle
                    aria-haspopup="menu" aria-expanded="false" aria-label="{{ $label }}">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">
                    <circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/>
                </svg>
            </button>
            <div class="vs-overflow__menu" data-overflow-menu role="menu" hidden>
                {{ $menu }}
            </div>
        </div>
    @endisset
</div>
