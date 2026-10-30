{{--
    Shared "How this works" guide button and dialog, used by admin pages and
    by the patron Make Reservation page.

    Base styling: resources/css/app.css (.vs-info*).
    Behaviour: resources/js/app.js (data-info-open / data-info-close).
    Usage:
        <x-ui.page-info id="packagesInfo" title="How packages work">
            <ol>
                <li>Select Add New Package to create a package.</li>
            </ol>
        </x-ui.page-info>
--}}
@props(['id', 'title', 'label' => 'How this works'])

<div class="vs-info">
    <button type="button" class="vs-info__button"
        data-info-open="{{ $id }}"
        aria-label="How this works: {{ $title }}"
        aria-haspopup="dialog"
        aria-expanded="false"
        aria-controls="{{ $id }}">
        <span class="vs-info__icon" aria-hidden="true">i</span>
        <span class="vs-info__label">{{ $label }}</span>
    </button>

    <div id="{{ $id }}" class="modal vs-info__modal" aria-hidden="true">
        <div class="modal-content vs-info__dialog" role="dialog" aria-modal="true"
            aria-labelledby="{{ $id }}-title" tabindex="-1">
            <h2 id="{{ $id }}-title" class="vs-info__title">{{ $title }}</h2>
            <div class="vs-info__body">{{ $slot }}</div>
            <div class="vs-info__actions">
                <button type="button" class="vs-info__close" data-info-close>Close</button>
            </div>
        </div>
    </div>
</div>
