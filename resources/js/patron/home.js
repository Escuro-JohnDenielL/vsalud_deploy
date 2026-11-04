// === Package details modal ===
// Each "View Package" button carries its package data as JSON (data-package),
// so the modal can be filled without an extra request.
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("packageModal");
    if (!modal) return;

    const closeBtn = document.getElementById("pkgModalDismiss");
    const titleEl = document.getElementById("pkgModalTitle");
    const descEl = document.getElementById("pkgModalDesc");
    const priceEl = document.getElementById("pkgModalPrice");
    const mainImage = document.getElementById("pkgModalMainImage");
    const inclusionsEl = document.getElementById("pkgModalInclusions");
    const zoomBtn = document.getElementById("pkgModalZoom");
    const lightbox = document.getElementById("pkgLightbox");
    const lightboxImage = document.getElementById("pkgLightboxImage");
    const lightboxClose = document.getElementById("pkgLightboxClose");

    // Venue row inside the package dialog. The villa gallery is opened from the
    // page-level "View venue pictures" button (venue-gallery.js owns it), so this
    // script only fills the venue name/capacity line.
    const pkgVenueInfo = document.getElementById("pkgModalVenueInfo");
    const pkgVenueName = document.getElementById("pkgModalVenueName");

    const DEFAULT_IMAGE = "/images/package_placeholder.svg";
    const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    const formatPrice = function (price) {
        return `₱${Number(price || 0).toLocaleString("en-PH", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })}`;
    };

    let currentImage = DEFAULT_IMAGE;
    let packageName = "Package";
    let lightboxReturnFocus = null;
    let modalReturnFocus = null;

    // A package row can still point at a photo that was never uploaded, so probe
    // the URL once and fall back to the branded placeholder when it fails.
    const probeImage = function (src) {
        return new Promise(function (resolve) {
            const probe = new Image();
            probe.onload = function () { resolve(true); };
            probe.onerror = function () { resolve(false); };
            probe.src = src;
        });
    };

    const updateLightbox = function () {
        if (!lightboxImage) return;
        lightboxImage.src = currentImage;
        lightboxImage.alt = `${packageName} enlarged photo`;
    };

    const openLightbox = function () {
        if (!lightbox) return;
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

    // One bordered row per inclusion, with the tick on the right.
    const addInclusionRow = function (text) {
        const li = document.createElement("li");

        const label = document.createElement("span");
        label.className = "pkg-modal__inclusion-text";
        label.textContent = text;

        const check = document.createElement("span");
        check.className = "pkg-modal__inclusion-check";
        check.setAttribute("aria-hidden", "true");
        check.textContent = "\u2713";

        li.append(label, check);
        inclusionsEl.appendChild(li);
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

            packageName = data.name || "Package";

            titleEl.textContent = packageName;
            descEl.textContent = data.description || "No description available.";
            priceEl.textContent = formatPrice(data.price);

            // Which villa(s) this package is held in.
            const venues = Array.isArray(data.venues) ? data.venues : [];
            const hasVenue = venues.length > 0;

            if (pkgVenueInfo) {
                pkgVenueInfo.hidden = !hasVenue;
            }
            if (pkgVenueName) {
                const names = venues.map(v => v.name).filter(Boolean).join(" · ");
                const only = venues.length === 1 && venues[0].capacity
                    ? ` · Good for up to ${Number(venues[0].capacity).toLocaleString("en-PH")} guests`
                    : "";
                pkgVenueName.textContent = hasVenue ? `${names}${only}` : "";
            }

            inclusionsEl.innerHTML = "";
            const inclusions = Array.isArray(data.inclusions) ? data.inclusions : [];
            if (inclusions.length > 0) {
                inclusions.forEach(addInclusionRow);
            } else {
                addInclusionRow("No inclusions specified");
            }

            // A package carries a single photo. Probe it once so a broken path
            // falls back to the placeholder instead of an empty frame.
            const source = typeof data.image === "string" && data.image.trim() !== ""
                ? data.image.trim()
                : DEFAULT_IMAGE;

            mainImage.onerror = function () {
                this.onerror = null;
                this.src = DEFAULT_IMAGE;
                currentImage = DEFAULT_IMAGE;
            };

            probeImage(source).then(function (loaded) {
                currentImage = loaded ? source : DEFAULT_IMAGE;
                mainImage.src = currentImage;
                mainImage.alt = `${packageName} photo`;
                openModal();
            });
        });
    });

    if (closeBtn) closeBtn.addEventListener("click", closeModal);

    modal.addEventListener("click", function (event) {
        if (event.target === modal) closeModal();
    });

    // Enlarged photo. Click the main photo, then Escape, Close or a backdrop click.
    if (zoomBtn) zoomBtn.addEventListener("click", openLightbox);
    if (lightboxImage) {
        lightboxImage.onerror = function () {
            this.onerror = null;
            this.src = DEFAULT_IMAGE;
        };
    }
    if (lightboxClose) lightboxClose.addEventListener("click", function () { closeLightbox(true); });

    lightbox?.addEventListener("click", function (event) {
        if (event.target === lightbox) closeLightbox(true);
    });

    document.addEventListener("keydown", function (event) {
        if (lightbox && !lightbox.hidden) {
            if (event.key === "Escape") {
                event.preventDefault();
                closeLightbox(true);
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
