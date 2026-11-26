const navMenu = document.getElementById("navMenu");
const menuButton = document.getElementById("menuButton");
const navLinks = document.querySelectorAll(".landing-nav__links a");
const landingNav = document.querySelector(".landing-nav");

if (landingNav) {
    const updateNav = () => {
        landingNav.classList.toggle("is-scrolled", window.scrollY > 24);
    };

    updateNav();
    window.addEventListener("scroll", updateNav, { passive: true });
}

const venueSection = document.querySelector(".landing-venues");
const venueCards = document.querySelectorAll(".landing-venue-card[data-venue-image]");
const venueBackdropImages = venueSection?.querySelectorAll(".landing-venues__backdrop-image") || [];

if (venueSection && venueCards.length && venueBackdropImages.length === 2) {
    let activeLayer = -1;
    let activeImage = "";
    let requestedImage = "";
    let currentCard = null;
    let requestId = 0;

    const showVenueBackdrop = (card) => {
        const image = card.dataset.venueImage;
        if (!image) return;
        currentCard = card;
        if (image === activeImage) {
            requestId++;
            requestedImage = "";
            return;
        }
        if (image === requestedImage) return;

        requestedImage = image;
        const thisRequest = ++requestId;
        const preload = new Image();
        preload.onload = () => {
            if (thisRequest !== requestId) return;
            if (currentCard !== card) {
                requestedImage = "";
                return;
            }

            const nextLayer = activeLayer === 0 ? 1 : 0;
            venueBackdropImages[nextLayer].src = image;
            venueBackdropImages[nextLayer].classList.add("is-active");
            if (activeLayer >= 0) venueBackdropImages[activeLayer].classList.remove("is-active");
            activeLayer = nextLayer;
            activeImage = image;
            requestedImage = "";
        };
        preload.onerror = () => {
            if (thisRequest === requestId) requestedImage = "";
        };
        preload.src = image;
    };

    const clearVenueBackdrop = () => {
        requestAnimationFrame(() => {
            if (venueSection.querySelector(".landing-venue-card:hover, .landing-venue-card:focus-within")) return;
            currentCard = null;
            requestedImage = "";
            requestId++;
            activeImage = "";
            venueBackdropImages.forEach((layer) => layer.classList.remove("is-active"));
        });
    };

    venueCards.forEach((card) => {
        card.addEventListener("pointerenter", () => showVenueBackdrop(card));
        card.addEventListener("pointerleave", clearVenueBackdrop);
        card.addEventListener("focusin", () => showVenueBackdrop(card));
        card.addEventListener("focusout", clearVenueBackdrop);
    });
}

if (navMenu && menuButton) {
    const closeMenu = () => {
        navMenu.classList.remove("show");
        menuButton.setAttribute("aria-expanded", "false");
    };

    menuButton.addEventListener("click", () => {
        const isOpen = navMenu.classList.toggle("show");
        menuButton.setAttribute("aria-expanded", String(isOpen));
    });

    navLinks.forEach((link) => {
        link.addEventListener("click", () => {
            navLinks.forEach((navLink) => navLink.classList.remove("active"));
            link.classList.add("active");
            closeMenu();
        });
    });

    document.addEventListener("click", (event) => {
        if (!navMenu.contains(event.target) && !menuButton.contains(event.target)) {
            closeMenu();
        }
    });

    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") closeMenu();
    });
}
