// === Villa galleries ===
// Every button with class "view-venue" carries its venue list as JSON:
//   data-venues='[ { name, capacity, description, photos: [url, ...] }, ... ]'
// A package can be held in several venues, so the dialog shows a row per venue
// and swapping the row swaps the photos. The photos live on the venue record,
// so the same villa upload serves every package that lists it. "Make
// reservation" opens the booking form with the highlighted venue preselected.
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("venueGallery");
    if (!modal) return;

    const mainImage = document.getElementById("venueGalleryImage");
    const countEl = document.getElementById("venueGalleryCount");
    const thumbsEl = document.getElementById("venueGalleryThumbs");
    const titleEl = document.getElementById("venueGalleryTitle");
    const capacityEl = document.getElementById("venueGalleryCapacity");
    const descEl = document.getElementById("venueGalleryDesc");
    const choicesEl = document.getElementById("venueGalleryChoices");
    const choicesLabelEl = document.getElementById("venueGalleryChoicesLabel");
    const prevBtn = document.getElementById("venueGalleryPrev");
    const nextBtn = document.getElementById("venueGalleryNext");
    const reserveEl = document.getElementById("venueGalleryReserve");
    const dismissBtn = document.getElementById("venueGalleryDismiss");
    const stage = modal.querySelector(".pkg-modal__stage");

    // Original booking-page URL; each venue is appended to it as ?venue=<name>.
    const reserveBase = reserveEl ? reserveEl.getAttribute("href") : "";

    const DEFAULT_IMAGE = "/images/package_placeholder.svg";
    const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    let venues = [];
    let activeVenue = 0;
    let gallery = [];
    let galleryIndex = 0;
    let hasPhotos = false;
    let returnFocus = null;
    let touchStart = null;

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

    // The arrows only make sense when there is something to page through.
    const syncNav = function () {
        const show = hasPhotos && gallery.length > 1;
        if (prevBtn) prevBtn.hidden = !show;
        if (nextBtn) nextBtn.hidden = !show;
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
        syncNav();
    };

    // Carry the highlighted venue to the booking form so it opens preselected.
    const syncReserveLink = function (venue) {
        if (!reserveEl || !reserveBase) return;

        const name = venue && venue.name ? venue.name : "";
        if (!name) {
            reserveEl.setAttribute("href", reserveBase);
            return;
        }

        const url = new URL(reserveBase, window.location.origin);
        url.searchParams.set("venue", name);
        reserveEl.setAttribute("href", url.pathname + url.search);
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

        if (choicesLabelEl) choicesLabelEl.hidden = venues.length < 2;

        if (venues.length < 2) {
            choicesEl.hidden = true;
            choicesEl.innerHTML = "";
            return;
        }

        choicesEl.hidden = false;
        choicesEl.innerHTML = "";

        venues.forEach(function (venue, index) {
            const choice = document.createElement("button");
            choice.type = "button";
            choice.className = "venue-gallery__choice";

            const nameEl = document.createElement("span");
            nameEl.className = "venue-gallery__choice-name";
            nameEl.textContent = venue.name || `Venue ${index + 1}`;
            choice.appendChild(nameEl);

            if (venue.capacity) {
                const guestsEl = document.createElement("span");
                guestsEl.className = "venue-gallery__choice-capacity";
                guestsEl.textContent = `${Number(venue.capacity).toLocaleString("en-PH")} guests`;
                choice.appendChild(guestsEl);
            }

            const checkEl = document.createElement("span");
            checkEl.className = "venue-gallery__choice-check";
            checkEl.setAttribute("aria-hidden", "true");
            checkEl.textContent = "\u2713";
            choice.appendChild(checkEl);

            choice.addEventListener("click", function () {
                selectVenue(index);
            });
            choicesEl.appendChild(choice);
        });

        syncChoices();
    };

    const open = function () {
        returnFocus = document.activeElement;
        window.vsModalPrepareOpen(modal);
        modal.style.display = "flex";
        document.body.style.overflow = "hidden";
        dismissBtn?.focus();
    };

    const close = function () {
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
        syncReserveLink(venue);

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
            if (thumbsEl) {
                thumbsEl.innerHTML = "";
                thumbsEl.style.display = "none";
            }
            syncNav();
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
                    });
                    thumbsEl.appendChild(thumb);
                });
                thumbsEl.style.display = hasPhotos ? "" : "none";
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

    if (dismissBtn) dismissBtn.addEventListener("click", close);
    if (prevBtn) prevBtn.addEventListener("click", function () {
        showImage(galleryIndex - 1);
    });
    if (nextBtn) nextBtn.addEventListener("click", function () {
        showImage(galleryIndex + 1);
    });

    stage?.addEventListener("touchstart", function (event) {
        if (event.touches.length !== 1 || event.target.closest(".pkg-modal__thumbs")) {
            touchStart = null;
            return;
        }

        const touch = event.touches[0];
        touchStart = { x: touch.clientX, y: touch.clientY };
    }, { passive: true });

    stage?.addEventListener("touchend", function (event) {
        if (!touchStart || !hasPhotos || gallery.length < 2) {
            touchStart = null;
            return;
        }

        const touch = event.changedTouches[0];
        const deltaX = touch.clientX - touchStart.x;
        const deltaY = touch.clientY - touchStart.y;
        touchStart = null;

        if (Math.abs(deltaX) < 48 || Math.abs(deltaX) < Math.abs(deltaY) * 1.2) return;
        showImage(galleryIndex + (deltaX < 0 ? 1 : -1));
    }, { passive: true });

    stage?.addEventListener("touchcancel", function () {
        touchStart = null;
    }, { passive: true });

    modal.addEventListener("click", function (event) {
        if (event.target === modal) close();
    });

    document.addEventListener("keydown", function (event) {
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
