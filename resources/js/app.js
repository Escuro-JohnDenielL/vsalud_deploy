import './bootstrap';

const vsModalCloseTimers = new WeakMap();

window.vsModalPrepareOpen = function (modal) {
    if (!modal) return;
    const closeTimer = vsModalCloseTimers.get(modal);
    if (closeTimer) window.clearTimeout(closeTimer);
    vsModalCloseTimers.delete(modal);
    modal.classList.remove('vs-modal--closing');
};

window.vsModalCloseAfterFade = function (modal, finishClose) {
    if (!modal || typeof finishClose !== 'function') return;
    if (modal.classList.contains('vs-modal--closing')) return;

    const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
    if (reduceMotion) {
        finishClose();
        return;
    }

    const durationValue = getComputedStyle(document.documentElement)
        .getPropertyValue('--vs-duration-base')
        .trim();
    const duration = Number.parseFloat(durationValue) || 200;
    const durationMs = durationValue.endsWith('ms') ? duration : duration * 1000;

    modal.classList.add('vs-modal--closing');
    const closeTimer = window.setTimeout(() => {
        if (vsModalCloseTimers.get(modal) !== closeTimer) return;
        modal.classList.remove('vs-modal--closing');
        vsModalCloseTimers.delete(modal);
        finishClose();
    }, durationMs);
    vsModalCloseTimers.set(modal, closeTimer);
};

document.addEventListener('DOMContentLoaded', () => {
    // Flag that JS is available so the responsive CSS can collapse the navbar
    // into a hamburger menu (graceful fallback to stacked links without JS).
    document.body.classList.add('nav-js');

    /* ----------------------------------------------------------
       Navigation dropdown groups (shared by the admin and patron
       navigation). A click or keyboard activation opens a group,
       opening one closes the others, Escape closes every group,
       and focus moving outside a group closes it.
       ---------------------------------------------------------- */
    const groups = Array.from(document.querySelectorAll('.nav-dropdown'));

    const setGroupOpen = (group, open) => {
        group.classList.toggle('open', open);
        const button = group.querySelector('.nav-dropdown-toggle');
        if (button) button.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    const closeAllGroups = (except) => {
        groups.forEach((group) => {
            if (group !== except) setGroupOpen(group, false);
        });
    };

    const toggleGroup = (group) => {
        const willOpen = !group.classList.contains('open');
        closeAllGroups(group);
        setGroupOpen(group, willOpen);
    };

    document.addEventListener('click', (e) => {
        const button = e.target.closest('.nav-dropdown-toggle');
        if (button) {
            const group = button.closest('.nav-dropdown');
            if (group) {
                e.preventDefault();
                toggleGroup(group);
                return;
            }
        }

        // Choosing a destination inside an open group closes it.
        if (e.target.closest('.nav-dropdown-menu a')) {
            closeAllGroups();
            return;
        }

        // Clicking anywhere else closes every open group.
        if (!e.target.closest('.nav-dropdown')) closeAllGroups();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAllGroups();
            return;
        }

        // Anchor toggles do not activate on Space, so handle it here.
        if (e.key === ' ' || e.key === 'Spacebar') {
            const button = e.target.closest('.nav-dropdown-toggle');
            const group = button ? button.closest('.nav-dropdown') : null;
            if (group) {
                e.preventDefault();
                toggleGroup(group);
            }
        }
    });

    // Tabbing out of a group closes it.
    groups.forEach((group) => {
        group.addEventListener('focusout', (e) => {
            if (!group.contains(e.relatedTarget)) setGroupOpen(group, false);
        });
    });

    /* ----------------------------------------------------------
       Patron collapsible (hamburger) menu.
       ---------------------------------------------------------- */
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

    // Close the panel after choosing a destination, but keep it open while the
    // patron is expanding a dropdown group (that toggle is a link too).
    if (links) {
        links.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            if (link && !link.classList.contains('nav-dropdown-toggle')) setOpen(false);
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
