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
// Shown on EVERY page load of the Patron homepage. There is intentionally no
// session/localStorage tracking: the notice reappears each time the page is
// loaded or reloaded, and only stays hidden until the next load.
document.addEventListener("DOMContentLoaded", function () {
    const privacyModal = document.getElementById("privacyModal");
    if (!privacyModal) return;

    const privacyClose = document.getElementById("privacyClose");
    const privacyAgree = document.getElementById("privacyAgree");

    const showPrivacyModal = function () {
        privacyModal.style.display = "flex";
        document.body.style.overflow = "hidden";
    };

    const hidePrivacyModal = function () {
        privacyModal.style.display = "none";
        document.body.style.overflow = "";
    };

    showPrivacyModal();

    if (privacyClose) privacyClose.addEventListener("click", hidePrivacyModal);
    if (privacyAgree) privacyAgree.addEventListener("click", hidePrivacyModal);

    // Close when clicking the dimmed backdrop
    privacyModal.addEventListener("click", function (event) {
        if (event.target === privacyModal) hidePrivacyModal();
    });
});
