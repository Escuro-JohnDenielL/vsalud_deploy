document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("packageModal");
    const closeModal = document.querySelector(".close");

    const packages = {
        "Baptism Package": [
            "../assets/baptismal1.jpg",
            "../assets/baptismal2.jpg",
            "../assets/baptismal3.jpg"
        ],
        "Debut Package": [
            "../assets/debut1.jpg",
            "../assets/debut2.jpg",
            "../assets/debut3.jpg"
        ],
        "Wedding Package": [
            "../assets/wedding1.jpg",
            "../assets/wedding2.jpg",
            "../assets/wedding3.jpg"
        ],
        "Kiddie Package": [
            "../assets/kiddie1.jpg",
            "../assets/kiddie2.jpg",
            "../assets/kiddie3.jpg"
        ]
    };

    function openModal(packageName) {
        if (!packages[packageName]) {
            console.error("Package not found:", packageName);
            return;
        }

        document.getElementById("modalTitle").textContent = packageName;
        document.getElementById("modalImg1").src = packages[packageName][0];
        document.getElementById("modalImg2").src = packages[packageName][1];
        document.getElementById("modalImg3").src = packages[packageName][2];

        modal.style.display = "flex";
    }

    closeModal.addEventListener("click", () => {
        modal.style.display = "none";
    });

    window.addEventListener("click", (event) => {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });

    document.querySelectorAll(".view-package").forEach(button => {
        button.addEventListener("click", function () {
            const packageName = this.closest(".package-card").dataset.package;
            openModal(packageName);
        });
    });
});
