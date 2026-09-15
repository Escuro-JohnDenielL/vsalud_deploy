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
    const DEFAULT_IMAGE = "/images/default_package.jpg";

    const formatPrice = function (price) {
        return `₱${Number(price || 0).toLocaleString("en-PH", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        })}`;
    };

    const openModal = function () {
        modal.style.display = "flex";
        document.body.style.overflow = "hidden";
    };

    const closeModal = function () {
        modal.style.display = "none";
        document.body.style.overflow = "";
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

            titleEl.textContent = data.name || "Package";
            descEl.textContent = data.description || "No description available.";
            priceEl.textContent = formatPrice(data.price);

            mainImage.onerror = function () {
                this.onerror = null;
                this.src = DEFAULT_IMAGE;
            };
            mainImage.src = images[0];
            mainImage.alt = (data.name || "Package") + " image";

            // Thumbnails (only shown when the package has more than one photo)
            thumbsEl.innerHTML = "";
            if (images.length > 1) {
                images.forEach(function (src) {
                    const thumb = document.createElement("img");
                    thumb.alt = (data.name || "Package") + " photo";
                    thumb.onerror = function () {
                        this.remove();
                    };
                    thumb.src = src;
                    thumb.addEventListener("click", function () {
                        mainImage.src = src;
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

    document.addEventListener("keydown", function (event) {
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

    const privacyClose = document.getElementById("privacyClose");
    const privacyAgree = document.getElementById("privacyAgree");
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

    // Only show on a real reload, or the first time the patron enters this tab.
    if (navType !== "reload" && acknowledged) return;

    const showPrivacyModal = function () {
        privacyModal.style.display = "flex";
        document.body.style.overflow = "hidden";
    };

    const hidePrivacyModal = function () {
        privacyModal.style.display = "none";
        document.body.style.overflow = "";
        try {
            sessionStorage.setItem(ACK_KEY, "1");
        } catch (e) { /* ignore */ }
    };

    showPrivacyModal();

    if (privacyClose) privacyClose.addEventListener("click", hidePrivacyModal);
    if (privacyAgree) privacyAgree.addEventListener("click", hidePrivacyModal);

    // Close when clicking the dimmed backdrop
    privacyModal.addEventListener("click", function (event) {
        if (event.target === privacyModal) hidePrivacyModal();
    });
});
