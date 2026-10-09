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
