// === Villa galleries ===
// Every button with class "view-venue" carries its venue list as JSON:
//   data-venues='[ { name, capacity, description, photos: [url, ...] }, ... ]'
// A package can be held in several venues, so the dialog shows a chip per venue
// and swapping the chip swaps the photos. The photos live on the venue record,
// so the same villa upload serves every package that lists it.
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("venueGallery");
    if (!modal) return;

    const closeBtn = document.getElementById("venueGalleryClose");
    const zoomBtn = document.getElementById("venueGalleryZoom");
    const mainImage = document.getElementById("venueGalleryImage");
    const countEl = document.getElementById("venueGalleryCount");
    const thumbsEl = document.getElementById("venueGalleryThumbs");
    const hintEl = document.getElementById("venueGalleryHint");
    const titleEl = document.getElementById("venueGalleryTitle");
    const capacityEl = document.getElementById("venueGalleryCapacity");
    const descEl = document.getElementById("venueGalleryDesc");
    const choicesEl = document.getElementById("venueGalleryChoices");

    const lightbox = document.getElementById("venueGalleryLightbox");
    const lightboxImage = document.getElementById("venueGalleryLightboxImage");
    const lightboxClose = document.getElementById("venueGalleryLightboxClose");
    const lightboxPrev = document.getElementById("venueGalleryLightboxPrev");
    const lightboxNext = document.getElementById("venueGalleryLightboxNext");

    const DEFAULT_IMAGE = "/images/package_placeholder.svg";
    const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    let venues = [];
    let activeVenue = 0;
    let gallery = [];
    let galleryIndex = 0;
    let hasPhotos = false;
    let returnFocus = null;
    let lightboxReturnFocus = null;

    // A photo can be missing from disk; probe once and drop the broken URLs so
    // the gallery never cycles into an empty slide.
    const imageStatus = new Map();
    const probeImage = function (src) {
        if (imageStatus.has(src)) return Promise.resolve(imageStatus.get(src));
        return new Promise(function (resolve) {
            const probe = new Image();
            probe.onload = function () {
                imageStatus.set(src, true);
                resolve(true);
            };
            probe.onerror = function () {
                imageStatus.set(src, false);
                resolve(false);
            };
            probe.src = src;
        });
    };

    const syncThumbs = function () {
        if (!thumbsEl) return;
        Array.from(thumbsEl.querySelectorAll(".pkg-modal__thumb")).forEach(function (thumb, index) {
            thumb.classList.toggle("is-active", index === galleryIndex);
        });
    };

    const showImage = function (index) {
        if (!gallery.length) return;
        galleryIndex = (index + gallery.length) % gallery.length;

        if (mainImage) {
            mainImage.src = gallery[galleryIndex];
            mainImage.alt = `${venues[activeVenue]?.name || "Venue"} photo ${galleryIndex + 1} of ${gallery.length}`;
        }
        if (countEl && hasPhotos) {
            countEl.textContent = `Photo ${galleryIndex + 1} of ${gallery.length}`;
        }
        syncThumbs();
    };

    const syncChoices = function () {
        if (!choicesEl) return;
        Array.from(choicesEl.querySelectorAll(".venue-gallery__choice")).forEach(function (chip, index) {
            const active = index === activeVenue;
            chip.classList.toggle("is-active", active);
            chip.setAttribute("aria-pressed", active ? "true" : "false");
        });
    };

    const renderChoices = function () {
        if (!choicesEl) return;

        if (venues.length < 2) {
            choicesEl.hidden = true;
            choicesEl.innerHTML = "";
            return;
        }

        choicesEl.hidden = false;
        choicesEl.innerHTML = "";

        venues.forEach(function (venue, index) {
            const chip = document.createElement("button");
            chip.type = "button";
            chip.className = "venue-gallery__choice";
            chip.textContent = venue.name || `Venue ${index + 1}`;
            chip.addEventListener("click", function () {
                selectVenue(index);
            });
            choicesEl.appendChild(chip);
        });

        syncChoices();
    };

    const updateLightbox = function () {
        if (!lightboxImage) return;
        lightboxImage.src = gallery[galleryIndex];
        lightboxImage.alt = `${venues[activeVenue]?.name || "Venue"} enlarged photo ${galleryIndex + 1} of ${gallery.length}`;
        const multiple = gallery.length > 1;
        if (lightboxPrev) lightboxPrev.hidden = !multiple;
        if (lightboxNext) lightboxNext.hidden = !multiple;
    };

    const openLightbox = function () {
        if (!lightbox || !gallery.length || !hasPhotos) return;
        lightboxReturnFocus = document.activeElement;
        lightbox.hidden = false;
        updateLightbox();
        document.body.style.overflow = "hidden";
        lightboxClose?.focus();
    };

    const closeLightbox = function (restoreFocus) {
        if (!lightbox || lightbox.hidden) return;
        lightbox.hidden = true;
        document.body.style.overflow = "hidden"; // the gallery dialog is still open
        if (restoreFocus !== false && lightboxReturnFocus?.focus) {
            lightboxReturnFocus.focus();
        }
    };

    const open = function () {
        returnFocus = document.activeElement;
        window.vsModalPrepareOpen(modal);
        modal.style.display = "flex";
        document.body.style.overflow = "hidden";
        closeBtn?.focus();
    };

    const close = function () {
        closeLightbox(false);
        window.vsModalCloseAfterFade(modal, function () {
            modal.style.display = "none";

            // Keep the page locked while another dialog (e.g. package details)
            // is still open behind this one.
            const otherOpen = Array.from(document.querySelectorAll(".pkg-modal")).some(function (el) {
                return el !== modal && el.style.display === "flex";
            });
            document.body.style.overflow = otherOpen ? "hidden" : "";

            if (returnFocus && typeof returnFocus.focus === "function") {
                returnFocus.focus();
            }
        });
    };

    /**
     * Show one venue's gallery. Called when the dialog opens and when a chip is
     * clicked, so switching villas keeps the dialog open.
     */
    const selectVenue = function (index) {
        if (!venues.length) return;

        activeVenue = (index + venues.length) % venues.length;
        const venue = venues[activeVenue] || {};
        const photos = (Array.isArray(venue.photos) ? venue.photos : []).filter(function (src) {
            return typeof src === "string" && src.trim() !== "";
        });

        if (titleEl) titleEl.textContent = venue.name || "Venue";
        if (capacityEl) {
            capacityEl.textContent = venue.capacity
                ? `Good for up to ${Number(venue.capacity).toLocaleString("en-PH")} guests`
                : "";
        }
        if (descEl) descEl.textContent = venue.description || "";
        if (mainImage) {
            mainImage.onerror = function () {
                this.onerror = null;
                this.src = DEFAULT_IMAGE;
            };
        }

        syncChoices();

        // Linked venue with no uploads yet: explain it instead of showing an
        // empty carousel.
        if (!photos.length) {
            gallery = [DEFAULT_IMAGE];
            galleryIndex = 0;
            hasPhotos = false;

            if (mainImage) {
                mainImage.src = DEFAULT_IMAGE;
                mainImage.alt = `${venue.name || "Venue"} placeholder`;
            }
            if (countEl) countEl.textContent = "";
            if (hintEl) hintEl.hidden = true;
            if (thumbsEl) {
                thumbsEl.innerHTML = "";
                thumbsEl.style.display = "none";
            }
            if (descEl) {
                const parts = [];
                if (venue.description) parts.push(venue.description);
                parts.push(`Photos for ${venue.name || "this venue"} haven't been uploaded yet.`);
                descEl.textContent = parts.join(" ");
            }
            return;
        }

        Promise.all(photos.map(probeImage)).then(function (loaded) {
            const usable = photos.filter(function (src, index) {
                return loaded[index];
            });
            const finalPhotos = usable.length ? usable : [DEFAULT_IMAGE];
            hasPhotos = usable.length > 0;

            gallery = finalPhotos;
            galleryIndex = 0;

            if (thumbsEl) {
                thumbsEl.innerHTML = "";
                finalPhotos.forEach(function (src, index) {
                    const thumb = document.createElement("button");
                    thumb.type = "button";
                    thumb.className = "pkg-modal__thumb";
                    thumb.setAttribute("aria-label", `${venue.name || "Venue"} photo ${index + 1} of ${finalPhotos.length}`);

                    const image = document.createElement("img");
                    image.src = src;
                    image.alt = `${venue.name || "Venue"} photo ${index + 1}`;
                    thumb.appendChild(image);

                    thumb.addEventListener("click", function () {
                        showImage(index);
                        openLightbox();
                    });
                    thumbsEl.appendChild(thumb);
                });
                thumbsEl.style.display = hasPhotos ? "" : "none";
            }

            if (hintEl) {
                hintEl.hidden = !hasPhotos;
                hintEl.textContent = finalPhotos.length > 1
                    ? "Click a photo to enlarge. Swipe or use arrows to browse."
                    : "Click the photo to enlarge it.";
            }

            showImage(0);
        });
    };

    const readVenues = function (button) {
        let list = [];

        try {
            list = JSON.parse(button.dataset.venues || "[]");
        } catch (e) {
            list = [];
        }

        if (!Array.isArray(list) || !list.length) {
            // Backwards compatibility with the old single-venue attribute.
            try {
                const single = JSON.parse(button.dataset.venue || "{}");
                list = single && single.name ? [single] : [];
            } catch (e) {
                list = [];
            }
        }

        return list.filter(function (venue) {
            return venue && typeof venue.name === "string" && venue.name !== "";
        });
    };

    document.querySelectorAll(".view-venue").forEach(function (button) {
        button.addEventListener("click", function () {
            const list = readVenues(this);
            if (!list.length) return;

            venues = list;
            renderChoices();
            selectVenue(0);
            open();
        });
    });

    if (closeBtn) closeBtn.addEventListener("click", close);

    modal.addEventListener("click", function (event) {
        if (event.target === modal) close();
    });

    if (zoomBtn) zoomBtn.addEventListener("click", openLightbox);
    if (lightboxClose) lightboxClose.addEventListener("click", function () { closeLightbox(true); });
    if (lightboxPrev) lightboxPrev.addEventListener("click", function () {
        showImage(galleryIndex - 1);
        updateLightbox();
    });
    if (lightboxNext) lightboxNext.addEventListener("click", function () {
        showImage(galleryIndex + 1);
        updateLightbox();
    });

    lightbox?.addEventListener("click", function (event) {
        if (event.target === lightbox) closeLightbox(true);
    });

    let touchStartX = null;
    lightbox?.addEventListener("touchstart", function (event) {
        touchStartX = event.changedTouches[0]?.clientX ?? null;
    }, { passive: true });
    lightbox?.addEventListener("touchend", function (event) {
        if (touchStartX === null || gallery.length < 2) return;
        const delta = (event.changedTouches[0]?.clientX ?? touchStartX) - touchStartX;
        touchStartX = null;
        if (Math.abs(delta) < 40) return;
        showImage(galleryIndex + (delta < 0 ? 1 : -1));
        updateLightbox();
    }, { passive: true });

    document.addEventListener("keydown", function (event) {
        if (lightbox && !lightbox.hidden) {
            if (event.key === "Escape") {
                event.preventDefault();
                closeLightbox(true);
            } else if (event.key === "ArrowLeft" && gallery.length > 1) {
                event.preventDefault();
                showImage(galleryIndex - 1);
                updateLightbox();
            } else if (event.key === "ArrowRight" && gallery.length > 1) {
                event.preventDefault();
                showImage(galleryIndex + 1);
                updateLightbox();
            }
            return;
        }

        if (modal.style.display !== "flex") return;

        if (event.key === "Escape") {
            event.preventDefault();
            close();
            return;
        }

        if (event.key !== "Tab") return;
        const items = Array.from(modal.querySelectorAll(FOCUSABLE)).filter(function (el) {
            return el.offsetParent !== null;
        });
        if (!items.length) return;

        const first = items[0];
        const last = items[items.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
});
