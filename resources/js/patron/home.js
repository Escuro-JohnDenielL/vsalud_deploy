// === Package details modal ===
// Each "View Package" button carries its package data as JSON (data-package),
// so the modal can be filled without an extra request.
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("packageModal");
    if (!modal) return;

    const closeBtn = document.getElementById("pkgModalClose");
    const ratesModal = document.getElementById("pkgVenueRatesModal");
    const ratesOpenBtn = document.getElementById("pkgModalRatesOpen");
    const ratesCloseBtn = document.getElementById("pkgModalRatesClose");
    const ratesDoneBtn = document.getElementById("pkgModalRatesDone");
    const titleEl = document.getElementById("pkgModalTitle");
    const descEl = document.getElementById("pkgModalDesc");
    const priceRangeEl = document.getElementById("pkgModalPriceRange");
    const venueRatesEl = document.getElementById("pkgModalVenueRates");
    const venueTable = document.getElementById("pkgModalVenueTable");
    const venueRatesEmpty = document.getElementById("pkgModalVenueRatesEmpty");
    const venueRatesPanel = document.getElementById("pkgModalVenueRatesPanel");
    const venueTabs = document.getElementById("pkgModalVenueTabs");
    const venueSelect = document.getElementById("pkgModalVenueSelect");
    const mainImage = document.getElementById("pkgModalMainImage");
    const inclusionsEl = document.getElementById("pkgModalInclusions");
    const inclusionsSection = document.getElementById("pkgModalInclusionsSection");
    const zoomBtn = document.getElementById("pkgModalZoom");
    const lightbox = document.getElementById("pkgLightbox");
    const lightboxImage = document.getElementById("pkgLightboxImage");
    const lightboxClose = document.getElementById("pkgLightboxClose");

    const formatPrice = function (amount) {
        return `₱${Number(amount).toLocaleString("en-PH", {
            minimumFractionDigits: Number.isInteger(Number(amount)) ? 0 : 2,
            maximumFractionDigits: 2,
        })}`;
    };

    const DEFAULT_IMAGE = "/images/package_placeholder.svg";
    const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    let currentImage = DEFAULT_IMAGE;
    let packageName = "Package";
    let currentVenues = [];
    let selectedVenueIndex = -1;
    let packageWideEstimate = "Contact us for venue pricing";
    let packageWideHasPrice = false;
    let lightboxReturnFocus = null;
    let modalReturnFocus = null;
    let ratesReturnFocus = null;

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

    const openRatesModal = function () {
        if (!ratesModal || ratesOpenBtn?.hidden) return;
        ratesReturnFocus = document.activeElement;
        window.vsModalPrepareOpen(ratesModal);
        ratesModal.hidden = false;
        ratesModal.style.display = "flex";
        ratesCloseBtn?.focus();
    };

    const closeRatesModal = function (restoreFocus = true) {
        if (!ratesModal || ratesModal.hidden || ratesModal.style.display === "none") return;
        window.vsModalCloseAfterFade(ratesModal, () => {
            ratesModal.style.display = "none";
            ratesModal.hidden = true;
            if (restoreFocus && ratesReturnFocus?.focus) ratesReturnFocus.focus();
        });
    };

    const closeModal = function () {
        closeRatesModal(false);
        resetVenueDisplay({ clearAll: true });
        closeLightbox(false);
        window.vsModalCloseAfterFade(modal, () => {
            modal.style.display = "none";
            document.body.style.overflow = "";
            if (modalReturnFocus && typeof modalReturnFocus.focus === "function") {
                modalReturnFocus.focus();
            }
        });
    };

    function resetVenueDisplay({ clearAll = false } = {}) {
        priceRangeEl.textContent = packageWideEstimate;
        priceRangeEl.classList.toggle("pkg-modal__pricing-range--contact", !packageWideHasPrice);
        venueRatesEl.replaceChildren();
        venueTable.hidden = true;
        venueRatesEmpty.textContent = "";
        venueRatesEmpty.hidden = true;
        venueRatesPanel.removeAttribute("aria-labelledby");
        selectedVenueIndex = -1;

        if (clearAll) {
            venueTabs.replaceChildren();
            venueTabs.hidden = true;
            venueSelect.replaceChildren();
            venueSelect.disabled = true;
            currentVenues = [];
        }
    }

    const formatPriceRange = function (rates) {
        const prices = (Array.isArray(rates) ? rates : [])
            .map(rate => Number(rate.price))
            .filter(Number.isFinite);
        if (!prices.length) return "Contact us for venue pricing";
        const minimum = Math.min(...prices);
        const maximum = Math.max(...prices);
        return minimum === maximum
            ? formatPrice(minimum)
            : `${formatPrice(minimum)}–${formatPrice(maximum)}`;
    };

    const renderVenueRates = function (venue) {
        venueRatesEl.replaceChildren();
        venueRatesEmpty.textContent = "";
        venueRatesEmpty.hidden = true;
        venueTable.hidden = true;

        const rates = Array.isArray(venue?.rates) ? venue.rates : [];
        if (!rates.length) {
            venueRatesEmpty.textContent = "Contact us for this venue's rates.";
            venueRatesEmpty.hidden = false;
            return;
        }

        // Keep the package estimate consistent with the card; venue-specific
        // prices are shown in the rates table below.
        const periods = {
            AM: { nonAir: "Not listed", air: "Not listed" },
            PM: { nonAir: "Not listed", air: "Not listed" },
        };
        rates.forEach(rate => {
            const label = String(rate.label || "").toLowerCase();
            const periodText = String(rate.period || "").toUpperCase();
            const periodMatch = periodText.match(/\b(AM|PM)\b/) || label.match(/\b(AM|PM)\b/);
            const period = periodMatch?.[1];
            if (!period || !periods[period]) return;

            if (/non[\s-]*air/.test(label)) {
                periods[period].nonAir = formatPrice(rate.price);
            } else if (/air[\s-]*condition/.test(label)) {
                periods[period].air = formatPrice(rate.price);
            }
        });

        ["AM", "PM"].forEach(period => {
            const row = document.createElement("tr");
            [period, periods[period].nonAir, periods[period].air].forEach(value => {
                const cell = document.createElement("td");
                cell.textContent = value;
                row.appendChild(cell);
            });
            venueRatesEl.appendChild(row);
        });
        venueTable.hidden = false;
    };

    const selectVenue = function (venueIndex, focusTab) {
        if (!Number.isInteger(venueIndex) || venueIndex < 0 || venueIndex >= currentVenues.length) return;

        selectedVenueIndex = venueIndex;
        const tabs = Array.from(venueTabs.querySelectorAll('[role="tab"]'));
        tabs.forEach(function (tab, index) {
            const selected = index === venueIndex;
            tab.setAttribute("aria-selected", String(selected));
            tab.tabIndex = selected ? 0 : -1;
        });

        const selectedTab = tabs[venueIndex];
        if (selectedTab) {
            venueRatesPanel.setAttribute("aria-labelledby", selectedTab.id);
            if (focusTab) selectedTab.focus();
        }

        venueSelect.value = String(venueIndex);
        renderVenueRates(currentVenues[venueIndex]);
    };

    const populateVenueControls = function (venues) {
        venueTabs.replaceChildren();
        venueSelect.replaceChildren();
        ratesOpenBtn.hidden = !venues.some(venue => Array.isArray(venue.rates) && venue.rates.length > 0);
        if (!venues.length) {
            const option = document.createElement("option");
            option.value = "";
            option.textContent = "No venues linked";
            venueSelect.appendChild(option);
            venueSelect.disabled = true;
            venueTabs.hidden = true;
            venueRatesEmpty.textContent = "No venues linked.";
            venueRatesEmpty.hidden = false;
            return;
        }

        venueTabs.hidden = false;
        venueSelect.disabled = false;
        venues.forEach(function (venue, index) {
            const option = document.createElement("option");
            option.value = String(index);
            option.textContent = venue.name;
            venueSelect.appendChild(option);

            const tab = document.createElement("button");
            tab.type = "button";
            tab.className = "pkg-modal__venue-tab";
            tab.id = `pkgModalVenueTab-${index}`;
            tab.setAttribute("role", "tab");
            tab.setAttribute("aria-selected", "false");
            tab.setAttribute("aria-controls", venueRatesPanel.id);
            tab.tabIndex = -1;
            tab.textContent = venue.name;
            tab.addEventListener("click", function () {
                selectVenue(index, false);
            });
            venueTabs.appendChild(tab);
        });

        selectVenue(0, false);
    };

    venueSelect.addEventListener("change", function () {
        selectVenue(Number(this.value), false);
    });

    venueTabs.addEventListener("keydown", function (event) {
        const tabs = Array.from(venueTabs.querySelectorAll('[role="tab"]'));
        if (!tabs.length) return;

        let nextIndex = selectedVenueIndex;
        if (event.key === "ArrowRight") nextIndex = (selectedVenueIndex + 1) % tabs.length;
        else if (event.key === "ArrowLeft") nextIndex = (selectedVenueIndex - 1 + tabs.length) % tabs.length;
        else if (event.key === "Home") nextIndex = 0;
        else if (event.key === "End") nextIndex = tabs.length - 1;
        else return;

        event.preventDefault();
        selectVenue(nextIndex, true);
    });

    const addInclusionRow = function (text) {
        const li = document.createElement("li");

        const label = document.createElement("span");
        label.className = "pkg-modal__inclusion-text";
        label.textContent = text;

        const check = document.createElementNS("http://www.w3.org/2000/svg", "svg");
        check.classList.add("pkg-modal__inclusion-check");
        check.setAttribute("viewBox", "0 0 16 16");
        check.setAttribute("aria-hidden", "true");
        check.setAttribute("focusable", "false");

        const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
        path.setAttribute("d", "m3 8 3.2 3.2L13 4.5");
        path.setAttribute("fill", "none");
        path.setAttribute("stroke", "currentColor");
        path.setAttribute("stroke-linecap", "round");
        path.setAttribute("stroke-linejoin", "round");
        path.setAttribute("stroke-width", "1.8");
        check.appendChild(path);

        li.append(check, label);
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

            resetVenueDisplay({ clearAll: true });
            packageName = data.name || "Package";

            titleEl.textContent = packageName;
            descEl.textContent = data.description || "No description available.";
            const seenVenues = new Set();
            const venues = (Array.isArray(data.venues) ? data.venues : []).filter((venue) => {
                const key = venue?.id != null ? `id:${venue.id}` : `name:${String(venue?.name || "").trim().toLowerCase()}`;
                if (!venue?.name || seenVenues.has(key)) return false;
                seenVenues.add(key);
                return true;
            });
            currentVenues = venues;
            const packageRates = venues.flatMap(venue => (
                Array.isArray(venue.rates) ? venue.rates : []
            ));
            const packagePrices = packageRates
                .map(rate => Number(rate.price))
                .filter(Number.isFinite);
            packageWideHasPrice = packagePrices.length > 0;
            packageWideEstimate = formatPriceRange(packageRates);
            resetVenueDisplay();
            populateVenueControls(venues);

            inclusionsEl.innerHTML = "";
            const inclusions = Array.isArray(data.inclusions) ? data.inclusions : [];
            inclusionsEl.classList.toggle("pkg-modal__inclusions-list--columns", inclusions.length > 6);
            if (inclusions.length > 0) {
                inclusionsSection.hidden = false;
                inclusionsEl.hidden = false;
                inclusions.forEach(addInclusionRow);
            } else {
                inclusionsSection.hidden = true;
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
    ratesOpenBtn?.addEventListener("click", openRatesModal);
    ratesCloseBtn?.addEventListener("click", function () { closeRatesModal(); });
    ratesDoneBtn?.addEventListener("click", function () { closeRatesModal(); });

    modal.addEventListener("click", function (event) {
        if (event.target === modal) closeModal();
    });

    ratesModal?.addEventListener("click", function (event) {
        if (event.target === ratesModal) closeRatesModal();
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

        if (ratesModal && !ratesModal.hidden) {
            if (event.key === "Escape") {
                event.preventDefault();
                closeRatesModal();
                return;
            }

            if (event.key === "Tab") {
                const items = Array.from(ratesModal.querySelectorAll(FOCUSABLE)).filter(function (el) {
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
