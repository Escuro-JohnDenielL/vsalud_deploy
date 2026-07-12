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
