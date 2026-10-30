// === Package details modal ===
// Each "View Package" button carries its package data as JSON (data-package),
// so the modal can be filled without an extra request.
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("packageModal");
    if (!modal) return;

    const closeBtn = document.getElementById("pkgModalClose");
    const titleEl = document.getElementById("pkgModalTitle");
    const descEl = document.getElementById("pkgModalDesc");
    const priceEl = document.getElementById("pkgModalPrice");
    const mainImage = document.getElementById("pkgModalMainImage");
    const thumbsEl = document.getElementById("pkgModalThumbs");
    const hintEl = modal.querySelector(".pkg-modal__hint");
    const inclusionsEl = document.getElementById("pkgModalInclusions");
    const zoomBtn = document.getElementById("pkgModalZoom");
    const lightbox = document.getElementById("pkgLightbox");
    const lightboxImage = document.getElementById("pkgLightboxImage");
    const lightboxClose = document.getElementById("pkgLightboxClose");
    const lightboxPrev = document.getElementById("pkgLightboxPrev");
    const lightboxNext = document.getElementById("pkgLightboxNext");
    const DEFAULT_IMAGE = "/images/package_placeholder.svg";
    // Three boxed thumbs are always visible; extra photos collapse into a +N tile.
    const MAX_THUMBS = 3;
    const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    const formatPrice = function (price) {
        return `₱${Number(price || 0).toLocaleString("en-PH", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })}`;
    };

    let gallery = [];
    let galleryIndex = 0;
    let packageName = "Package";
    let lightboxReturnFocus = null;
    let modalReturnFocus = null;

    // Some package rows can still point at photos that were never uploaded.
    // Probe each URL once and drop the ones that fail, so the gallery never
    // cycles into a broken/empty slide (and arrows only show when there is
    // genuinely more than one photo).
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
        const thumbs = Array.from(thumbsEl.querySelectorAll(".pkg-modal__thumb"));
        thumbs.forEach(function (thumb, index) {
            const isMore = thumb.classList.contains("pkg-modal__thumb--more");
            const active = isMore ? galleryIndex >= MAX_THUMBS : galleryIndex === index;
            thumb.classList.toggle("is-active", active);
        });
    };

    const showGalleryImage = function (index) {
        if (!gallery.length) return;
        galleryIndex = (index + gallery.length) % gallery.length;
        mainImage.src = gallery[galleryIndex];
        mainImage.alt = `${packageName} photo ${galleryIndex + 1} of ${gallery.length}`;
        syncThumbs();
    };

    const updateLightbox = function () {
        if (!lightboxImage) return;
        lightboxImage.src = gallery[galleryIndex];
        lightboxImage.alt = `${packageName} enlarged photo ${galleryIndex + 1} of ${gallery.length}`;
        const multiple = gallery.length > 1;
        if (lightboxPrev) lightboxPrev.hidden = !multiple;
        if (lightboxNext) lightboxNext.hidden = !multiple;
    };

    const openLightbox = function () {
        if (!lightbox || !gallery.length) return;
        lightboxReturnFocus = document.activeElement;
        lightbox.hidden = false;
        updateLightbox();
        document.body.style.overflow = "hidden";
        lightboxClose?.focus();
    };

    const closeLightbox = function (restoreFocus) {
        if (!lightbox || lightbox.hidden) return;
        lightbox.hidden = true;
        document.body.style.overflow = modal.style.display === "flex" ? "hidden" : "";
        if (restoreFocus !== false && lightboxReturnFocus?.focus) {
            lightboxReturnFocus.focus();
        }
    };

    const openModal = function () {
        modalReturnFocus = document.activeElement;
        window.vsModalPrepareOpen(modal);
        modal.style.display = "flex";
        document.body.style.overflow = "hidden";
        closeBtn?.focus();
    };

    const closeModal = function () {
        closeLightbox(false);
        window.vsModalCloseAfterFade(modal, () => {
            modal.style.display = "none";
            document.body.style.overflow = "";
            if (modalReturnFocus && typeof modalReturnFocus.focus === "function") {
                modalReturnFocus.focus();
            }
        });
    };

    document.querySelectorAll(".view-package").forEach(function (button) {
        button.addEventListener("click", function () {
            let data = null;
            try {
                data = JSON.parse(this.dataset.package || "{}");
            } catch (e) {
                data = null;
            }
            if (!data) return;

            const images = (Array.isArray(data.images) ? data.images : [])
                .filter(function (src) {
                    return typeof src === "string" && src.trim() !== "";
                });
            if (!images.length) images.push(DEFAULT_IMAGE);

            packageName = data.name || "Package";

            titleEl.textContent = data.name || "Package";
            descEl.textContent = data.description || "No description available.";
            priceEl.textContent = formatPrice(data.price);

            mainImage.onerror = function () {
                this.onerror = null;
                this.src = DEFAULT_IMAGE;
            };

            inclusionsEl.innerHTML = "";
            const inclusions = Array.isArray(data.inclusions) ? data.inclusions : [];
            if (inclusions.length > 0) {
                inclusions.forEach(function (item) {
                    const li = document.createElement("li");
                    li.textContent = item;
                    inclusionsEl.appendChild(li);
                });
            } else {
                const li = document.createElement("li");
                li.textContent = "No inclusions specified.";
                inclusionsEl.appendChild(li);
            }

            // Keep only the photos that really load. A package with a single real
            // photo ends up with a one-item gallery: no thumbnails, no arrows, and
            // the counter reads "photo 1 of 1" instead of cycling into 404s.
            Promise.all(images.map(probeImage)).then(function (loaded) {
                const usable = images.filter(function (src, index) {
                    return loaded[index];
                });

                gallery = usable.length ? usable : [DEFAULT_IMAGE];
                showGalleryImage(0);

                if (hintEl) {
                    hintEl.textContent = gallery.length > 1
                        ? "Click a photo to enlarge. Swipe or use arrows to browse."
                        : "Click the photo to enlarge it.";
                }

                // Thumbnails (only shown when the package has more than one photo).
                // Three boxed squares stay visible; extras collapse into a +N tile.
                thumbsEl.innerHTML = "";
                if (gallery.length > 1) {
                    const shown = Math.min(gallery.length, MAX_THUMBS);
                    for (let index = 0; index < shown; index += 1) {
                        const thumb = document.createElement("button");
                        thumb.type = "button";
                        thumb.className = "pkg-modal__thumb";
                        thumb.setAttribute("aria-label", `${packageName} photo ${index + 1} of ${gallery.length}`);

                        const image = document.createElement("img");
                        image.src = gallery[index];
                        image.alt = `${packageName} photo ${index + 1}`;
                        image.onerror = function () {
                            thumb.remove();
                        };
                        thumb.appendChild(image);

                        thumb.addEventListener("click", function () {
                            showGalleryImage(index);
                            openLightbox();
                        });
                        thumbsEl.appendChild(thumb);
                    }

                    if (gallery.length > MAX_THUMBS) {
                        const more = document.createElement("button");
                        more.type = "button";
                        more.className = "pkg-modal__thumb pkg-modal__thumb--more";
                        more.textContent = `+${gallery.length - MAX_THUMBS}`;
                        more.setAttribute("aria-label", `View ${gallery.length - MAX_THUMBS} more photos`);
                        more.addEventListener("click", function () {
                            showGalleryImage(MAX_THUMBS);
                            openLightbox();
                        });
                        thumbsEl.appendChild(more);
                    }

                    syncThumbs();
                }

                openModal();
            });
        });
    });

    if (closeBtn) closeBtn.addEventListener("click", closeModal);

    modal.addEventListener("click", function (event) {
        if (event.target === modal) closeModal();
    });

    // Enlarged photo. Click the main photo, then Escape, Close, arrows or swipe.
    if (zoomBtn) zoomBtn.addEventListener("click", openLightbox);
    if (lightboxImage) {
        lightboxImage.onerror = function () {
            this.onerror = null;
            this.src = DEFAULT_IMAGE;
        };
    }
    if (lightboxClose) lightboxClose.addEventListener("click", function () { closeLightbox(true); });
    if (lightboxPrev) lightboxPrev.addEventListener("click", function () {
        showGalleryImage(galleryIndex - 1);
        updateLightbox();
    });
    if (lightboxNext) lightboxNext.addEventListener("click", function () {
        showGalleryImage(galleryIndex + 1);
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
        showGalleryImage(galleryIndex + (delta < 0 ? 1 : -1));
        updateLightbox();
    }, { passive: true });

    document.addEventListener("keydown", function (event) {
        if (lightbox && !lightbox.hidden) {
            if (event.key === "Escape") {
                event.preventDefault();
                closeLightbox(true);
            } else if (event.key === "ArrowLeft" && gallery.length > 1) {
                event.preventDefault();
                showGalleryImage(galleryIndex - 1);
                updateLightbox();
            } else if (event.key === "ArrowRight" && gallery.length > 1) {
                event.preventDefault();
                showGalleryImage(galleryIndex + 1);
                updateLightbox();
            }
            return;
        }

        if (modal.style.display !== "flex") return;

        if (event.key === "Escape") {
            event.preventDefault();
            closeModal();
            return;
        }

        // Keep keyboard focus inside the open package dialog.
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

// === Data Privacy Act (RA 10173) notice ===
// Shows on the Patron homepage when the patron first enters the site in this
// tab (e.g. clicking "Patrons") and again on a real browser reload (Ctrl+R /
// refresh button). It does NOT reappear when navigating between pages inside
// the site (e.g. Packages -> Make Reservation -> Packages).
//
// Detection: sessionStorage remembers the notice was acknowledged for this tab
// (cleared automatically when the tab is closed), while the Navigation Timing
// API tells us whether the current page load is an actual reload.
document.addEventListener("DOMContentLoaded", function () {
    const privacyModal = document.getElementById("privacyModal");
    if (!privacyModal) return;

    const privacyAgree = document.getElementById("privacyAgree");
    const privacyDecline = document.getElementById("privacyDecline");
    const ACK_KEY = "vs_privacy_ack";

    // What kind of load is this? "reload" = Ctrl+R / refresh button.
    let navType = "navigate";
    try {
        const navEntries = performance.getEntriesByType("navigation");
        if (navEntries.length) navType = navEntries[0].type; // reload | navigate | back_forward
    } catch (e) { /* ignore */ }

    // Was the notice already acknowledged earlier in this tab session?
    let acknowledged = false;
    try {
        acknowledged = sessionStorage.getItem(ACK_KEY) === "1";
    } catch (e) { /* ignore */ }

    // Only show on a real reload, the first time the patron enters this tab,
    // or when the reservation form links here with #privacy.
    const wantsFullNotice = window.location.hash === "#privacy";
    if (!wantsFullNotice && navType !== "reload" && acknowledged) return;

    let lastFocus = null;
    let isOpen = false;

    const focusables = function () {
        return Array.from(privacyModal.querySelectorAll(
            'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        )).filter(function (el) {
            return el.offsetParent !== null;
        });
    };

    const onKeydown = function (event) {
        if (!isOpen) return;

        if (event.key === "Escape") {
            event.preventDefault();
            closePrivacyModal({ recordConsent: false });
            return;
        }

        if (event.key !== "Tab") return;

        const items = focusables();
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
    };

    const showPrivacyModal = function () {
        lastFocus = document.activeElement;
        window.vsModalPrepareOpen(privacyModal);
        privacyModal.style.display = "flex";
        document.body.style.overflow = "hidden";
        isOpen = true;
        document.addEventListener("keydown", onKeydown, true);
        privacyModal.querySelector(".privacy-modal")?.focus();
    };

    // Consent is recorded only by "I Agree". Escape, the backdrop and Decline
    // close without writing the acknowledgement, so closing never counts as
    // consent and never unlocks anything.
    const closePrivacyModal = function (options) {
        const settings = options || {};
        isOpen = false;
        document.removeEventListener("keydown", onKeydown, true);

        if (settings.recordConsent) {
            try {
                sessionStorage.setItem(ACK_KEY, "1");
            } catch (e) { /* ignore */ }
        }

        window.vsModalCloseAfterFade(privacyModal, function () {
            privacyModal.style.display = "none";
            document.body.style.overflow = "";

            if (settings.leave) {
                window.location.assign(privacyModal.dataset.declineUrl || "/");
                return;
            }

            // Drop the deep link so closing and reloading does not reopen it.
            if (window.location.hash === "#privacy" && window.history.replaceState) {
                window.history.replaceState(null, "", window.location.pathname + window.location.search);
            }

            const target = lastFocus && lastFocus !== document.body && lastFocus.focus
                ? lastFocus
                : document.querySelector(".vs-nav-cta");
            target?.focus?.();
        });
    };

    showPrivacyModal();

    privacyAgree?.addEventListener("click", function () {
        closePrivacyModal({ recordConsent: true });
    });

    privacyDecline?.addEventListener("click", function () {
        closePrivacyModal({ recordConsent: false, leave: true });
    });

    // Backdrop click closes without recording consent.
    privacyModal.addEventListener("click", function (event) {
        if (event.target === privacyModal) {
            closePrivacyModal({ recordConsent: false });
        }
    });
});
