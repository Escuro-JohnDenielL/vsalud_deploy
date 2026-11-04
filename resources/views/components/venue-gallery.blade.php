{{--
    Villa gallery dialog, used by the patron Packages page.

    Open it from any button with class "view-venue" and a data-venues JSON payload
    (a list, so the venue rows can switch between villas):
        [ { name, capacity, description, photos: [url, ...] }, ... ]

    The photos live on the venue record (venue_photos), so every package and page
    that references the same villa shows the same gallery — no duplicate uploads.
    "Make reservation" carries the highlighted venue to the booking form as
    ?venue=<name>, where resources/js/patron/mreserve.js selects it.

    The photo fills the left pane edge to edge; the photo counter and the
    thumbnails hover over its bottom-left corner.
--}}
<div id="venueGallery" class="pkg-modal pkg-modal--venue" role="dialog" aria-modal="true" aria-labelledby="venueGalleryTitle">
    <div class="pkg-modal__dialog">
        <div class="pkg-modal__body">
            <div class="pkg-modal__media">
                {{-- No zoom overlay: a thumbnail or arrow swaps this photo. --}}
                <div class="pkg-modal__stage">
                    <img id="venueGalleryImage" class="pkg-modal__main-image" src="" alt="Venue photo">
                    <button type="button" class="pkg-modal__nav pkg-modal__nav--prev" id="venueGalleryPrev" aria-label="Previous photo" hidden>&#8249;</button>
                    <button type="button" class="pkg-modal__nav pkg-modal__nav--next" id="venueGalleryNext" aria-label="Next photo" hidden>&#8250;</button>

                    <div class="venue-gallery__overlay">
                        <p class="pkg-modal__media-label" id="venueGalleryCount"></p>
                        <div class="pkg-modal__thumbs pkg-modal__thumbs--venue" id="venueGalleryThumbs"></div>
                    </div>
                </div>
            </div>

            <div class="pkg-modal__info">
                <h2 class="pkg-modal__title" id="venueGalleryTitle"></h2>
                <p class="venue-modal__capacity" id="venueGalleryCapacity"></p>
                {{-- One row per venue; only rendered when the page lists several. --}}
                <p class="venue-gallery__label" id="venueGalleryChoicesLabel" hidden>Choose a venue</p>
                <div class="venue-gallery__choices" id="venueGalleryChoices" hidden></div>
                <p class="pkg-modal__desc" id="venueGalleryDesc"></p>

                <div class="pkg-modal__actions pkg-modal__actions--venue">
                    <a href="{{ route('patron.mreserve') }}" class="pkg-modal__cta pkg-modal__cta--primary" id="venueGalleryReserve">Make reservation</a>
                    <button type="button" class="pkg-modal__cta pkg-modal__cta--secondary" id="venueGalleryDismiss">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
