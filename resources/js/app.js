import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    // Flag that JS is available so the responsive CSS can collapse the navbar
    // into a hamburger menu (graceful fallback to stacked links without JS).
    document.body.classList.add('nav-js');

    const nav = document.querySelector('.nav-collapsible');
    if (!nav) return;

    const toggle = nav.querySelector('.nav-toggle');
    const links = nav.querySelector('.nav-links');
    if (!toggle) return;

    const setOpen = (open) => {
        nav.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (links) links.setAttribute('aria-hidden', open ? 'false' : 'true');
    };

    toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        setOpen(!nav.classList.contains('open'));
    });

    // Close the menu after picking a destination link.
    if (links) {
        links.addEventListener('click', (e) => {
            if (e.target.closest('a')) setOpen(false);
        });
    }

    // Close when tapping anywhere outside the menu.
    document.addEventListener('click', (e) => {
        if (nav.classList.contains('open') && !nav.contains(e.target)) setOpen(false);
    });

    // Close on Escape for accessibility.
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && nav.classList.contains('open')) setOpen(false);
    });
});