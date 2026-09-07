document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("packageModal");
    const closeModal = document.querySelector(".close");

    // Safely attach listener - closeModal may not exist
    if (closeModal) {
        closeModal.addEventListener("click", () => {
            if (modal) modal.style.display = "none";
        });
    }

    if (modal) {
        window.addEventListener("click", (event) => {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });
    }

    // Safely handle package view buttons - they may not exist on this page
    document.querySelectorAll(".view-package").forEach(button => {
        button.addEventListener("click", function () {
            const packageCard = this.closest(".package-card");
            if (!packageCard) return;
            const packageName = packageCard.dataset.package;
            if (modal) {
                modal.style.display = "flex";
            }
        });
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
