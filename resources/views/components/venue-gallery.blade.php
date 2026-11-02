{{--
    Villa gallery dialog, used by the patron Packages page.

    Open it from any button with class "view-venue" and a data-venues JSON payload
    (a list, so the chips can switch between villas):
        [ { name, capacity, description, photos: [url, ...] }, ... ]

    The photos live on the venue record (venue_photos), so every package and page
    that references the same villa shows the same gallery — no duplicate uploads.
--}}
<div id="venueGallery" class="pkg-modal pkg-modal--venue" role="dialog" aria-modal="true" aria-labelledby="venueGalleryTitle">
    <div class="pkg-modal__dialog">
        <button type="button" class="pkg-modal__close" id="venueGalleryClose" aria-label="Close venue gallery">&times;</button>

        <div class="pkg-modal__body">
            <div class="pkg-modal__media">
                <button type="button" class="pkg-modal__zoom" id="venueGalleryZoom" aria-label="Enlarge venue photo">
                    <img id="venueGalleryImage" class="pkg-modal__main-image" src="" alt="Venue photo">
                </button>
                <p class="pkg-modal__media-label" id="venueGalleryCount"></p>
                <div class="pkg-modal__thumbs pkg-modal__thumbs--venue" id="venueGalleryThumbs"></div>
                <p class="pkg-modal__hint" id="venueGalleryHint" hidden></p>
            </div>

            <div class="pkg-modal__info">
                <h2 class="pkg-modal__title" id="venueGalleryTitle"></h2>
                {{-- One chip per venue; only rendered when the package lists several. --}}
                <div class="venue-gallery__choices" id="venueGalleryChoices" hidden></div>
                <p class="venue-modal__capacity" id="venueGalleryCapacity"></p>
                <p class="pkg-modal__desc" id="venueGalleryDesc"></p>
            </div>
        </div>

        <div class="pkg-modal__footer">
            <a href="{{ route('patron.mreserve') }}" class="pkg-modal__cta pkg-modal__cta--primary">Reserve this venue</a>
        </div>
    </div>
</div>

<div id="venueGalleryLightbox" class="pkg-lightbox" role="dialog" aria-modal="true" aria-label="Enlarged venue photo" hidden>
    <div class="pkg-lightbox__dialog">
        <button type="button" class="pkg-lightbox__close" id="venueGalleryLightboxClose">Close</button>
        <button type="button" class="pkg-lightbox__nav pkg-lightbox__nav--prev" id="venueGalleryLightboxPrev" aria-label="Previous photo">&#8249;</button>
        <img id="venueGalleryLightboxImage" class="pkg-lightbox__image" src="" alt="">
        <button type="button" class="pkg-lightbox__nav pkg-lightbox__nav--next" id="venueGalleryLightboxNext" aria-label="Next photo">&#8250;</button>
    </div>
</div>
