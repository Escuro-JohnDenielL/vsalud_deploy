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
       Sliding nav pill (patron + admin). Active by default, follows
       hover and keyboard focus, then returns to the active item.
       Null-safe: a nav without a pill or link list is ignored.
       ---------------------------------------------------------- */
    const initNavPill = (navEl) => {
        const linksEl = navEl?.querySelector(':scope > .nav-links, :scope > .vs-admin-nav__links');
        const pill = navEl?.querySelector('.vs-nav-pill');
        if (!navEl || !linksEl || !pill) return;

        const pillLinks = () => Array.from(linksEl.querySelectorAll(':scope > li > a'));
        const activeLink = () => pillLinks().find((link) => link.classList.contains('active'));
        const isMobile = () => window.matchMedia('(max-width: 991.98px)').matches;
        const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        const setPillTarget = (link) => {
            pillLinks().forEach((item) => {
                item.classList.toggle('is-pill-target', item === link);
            });
        };

        const movePill = (link) => {
            if (!link || isMobile() || reduceMotion()) {
                pill.hidden = true;
                setPillTarget(null);
                return;
            }

            const navRect = navEl.getBoundingClientRect();
            const linkRect = link.getBoundingClientRect();

            pill.hidden = false;
            pill.style.transform =
                `translate3d(${Math.round(linkRect.left - navRect.left)}px, ${Math.round(linkRect.top - navRect.top)}px, 0)`;
            pill.style.width = `${Math.round(linkRect.width)}px`;
            pill.style.height = `${Math.round(linkRect.height)}px`;
            setPillTarget(link);
        };

        const resetPill = () => movePill(activeLink());

        pillLinks().forEach((link) => {
            link.addEventListener('mouseenter', () => movePill(link));
            link.addEventListener('focus', () => movePill(link));
        });

        linksEl.addEventListener('mouseleave', resetPill);
        linksEl.addEventListener('focusout', (event) => {
            if (!linksEl.contains(event.relatedTarget)) resetPill();
        });

        window.addEventListener('resize', () => {
            if (isMobile() || reduceMotion()) {
                pill.hidden = true;
                setPillTarget(null);
            } else {
                resetPill();
            }
        });

        requestAnimationFrame(resetPill);
        window.addEventListener('load', resetPill);
    };

    document.querySelectorAll('.vs-patron-nav, .vs-admin-nav').forEach(initNavPill);

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

// === Shared page info guides ("How this works") ===
// One implementation for admin and patron pages: the trigger carries
// data-info-open="<dialog id>", the dialog carries a [data-info-close] button.
document.addEventListener('DOMContentLoaded', () => {
    const FOCUSABLE = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

    document.querySelectorAll('[data-info-open]').forEach((trigger) => {
        const modal = document.getElementById(trigger.getAttribute('data-info-open'));
        if (!modal) return;

        // position: fixed is relative to any transformed ancestor, so keep the
        // dialog at the document root while it is open.
        if (modal.parentNode !== document.body) document.body.appendChild(modal);

        const dialog = modal.querySelector('.vs-info__dialog');
        const closeBtn = modal.querySelector('[data-info-close]');
        let lastFocused = null;

        const focusables = () => Array.prototype.slice
            .call(dialog.querySelectorAll(FOCUSABLE))
            .filter((el) => el.offsetParent !== null);

        const onKeydown = (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                close();
                return;
            }
            if (event.key !== 'Tab') return;

            const items = focusables();
            if (!items.length) {
                event.preventDefault();
                dialog.focus();
                return;
            }
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

        const open = () => {
            lastFocused = trigger;
            window.vsModalPrepareOpen(modal);
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            trigger.setAttribute('aria-expanded', 'true');
            // Focus synchronously: the dialog is already displayed at this point.
            (focusables()[0] || dialog).focus();
            // Escape and the Tab trap live on the document while the dialog is
            // open, so they still work if focus ends up outside the dialog.
            document.addEventListener('keydown', onKeydown, true);
        };

        const close = () => {
            if (!modal.classList.contains('open')) return;
            document.removeEventListener('keydown', onKeydown, true);
            trigger.setAttribute('aria-expanded', 'false');
            modal.setAttribute('aria-hidden', 'true');
            window.vsModalCloseAfterFade(modal, () => {
                modal.classList.remove('open');
                if (lastFocused) lastFocused.focus();
            });
        };

        trigger.addEventListener('click', open);
        if (closeBtn) closeBtn.addEventListener('click', close);

        modal.addEventListener('click', (event) => {
            if (event.target === modal) close();
        });
    });
});
