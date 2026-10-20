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
    const inclusionsEl = document.getElementById("pkgModalInclusions");
    const zoomBtn = document.getElementById("pkgModalZoom");
    const lightbox = document.getElementById("pkgLightbox");
    const lightboxImage = document.getElementById("pkgLightboxImage");
    const lightboxClose = document.getElementById("pkgLightboxClose");
    const lightboxPrev = document.getElementById("pkgLightboxPrev");
    const lightboxNext = document.getElementById("pkgLightboxNext");
    const DEFAULT_IMAGE = "/images/package_placeholder.svg";

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

    const showGalleryImage = function (index) {
        if (!gallery.length) return;
        galleryIndex = (index + gallery.length) % gallery.length;
        mainImage.src = gallery[galleryIndex];
        mainImage.alt = `${packageName} photo ${galleryIndex + 1} of ${gallery.length}`;
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
        window.vsModalPrepareOpen(modal);
        modal.style.display = "flex";
        document.body.style.overflow = "hidden";
    };

    const closeModal = function () {
        closeLightbox(false);
        window.vsModalCloseAfterFade(modal, () => {
            modal.style.display = "none";
            document.body.style.overflow = "";
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

            const images = Array.isArray(data.images) && data.images.length
                ? data.images
                : [DEFAULT_IMAGE];

            gallery = images;
            packageName = data.name || "Package";

            titleEl.textContent = data.name || "Package";
            descEl.textContent = data.description || "No description available.";
            priceEl.textContent = formatPrice(data.price);

            mainImage.onerror = function () {
                this.onerror = null;
                this.src = DEFAULT_IMAGE;
            };
            showGalleryImage(0);

            // Thumbnails (only shown when the package has more than one photo)
            thumbsEl.innerHTML = "";
            if (images.length > 1) {
                images.forEach(function (src, index) {
                    const thumb = document.createElement("img");
                    thumb.alt = (data.name || "Package") + " photo " + (index + 1);
                    thumb.onerror = function () {
                        this.remove();
                    };
                    thumb.src = src;
                    thumb.addEventListener("click", function () {
                        showGalleryImage(index);
                    });
                    thumbsEl.appendChild(thumb);
                });
            }

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

            openModal();
        });
    });

    if (closeBtn) closeBtn.addEventListener("click", closeModal);

    modal.addEventListener("click", function (event) {
        if (event.target === modal) closeModal();
    });

    // Enlarged photo. Click the main photo, then Escape, Close, arrows or swipe.
    if (zoomBtn) zoomBtn.addEventListener("click", openLightbox);
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

        if (event.key === "Escape" && modal.style.display === "flex") {
            closeModal();
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
