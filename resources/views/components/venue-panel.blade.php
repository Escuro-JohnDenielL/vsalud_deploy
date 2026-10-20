{{--
    Shared "Venues on <date>" panel for both Make Reservation pages.
    Markup only: resources/js/venue-availability.js fills it and keeps the
    Venue select in sync. No booking rule or daily-cap logic lives here.
--}}
@props(['endpoint'])

<section id="venueAvailability"
         class="vs-venue-panel"
         data-endpoint="{{ $endpoint }}"
         aria-labelledby="venueAvailabilityTitle"
         hidden>
    <h3 class="vs-venue-panel__title" id="venueAvailabilityTitle" data-vs-venue-title>Venues</h3>
    <p class="vs-venue-panel__status" data-vs-venue-status role="status" aria-live="polite" hidden></p>
    <button type="button" class="vs-venue-panel__retry" data-vs-venue-retry hidden>Retry</button>
    <p class="vs-venue-panel__message" data-vs-venue-message hidden></p>
    <ul class="vs-venue-panel__list" data-vs-venue-list></ul>
</section>
