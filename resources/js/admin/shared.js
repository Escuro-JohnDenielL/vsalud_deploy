// Shared admin UI behaviour (batch 3): row-action overflow menus and
// status-select colour variants. Loaded once from layouts/admin.
(function () {
  var VARIANT_CLASSES = ['success', 'warning', 'danger', 'info'];
  var OVERFLOW_GAP = 6;
  var VIEWPORT_PAD = 8;

  function menuOf(overflow) {
    return overflow.__vsMenu || overflow.querySelector('[data-overflow-menu]');
  }
  function toggleOf(overflow) { return overflow.querySelector('[data-overflow-toggle]'); }

  // The menu is position:fixed, so ANY ancestor with a transform becomes its
  // containing block and the viewport coordinates land in the wrong place. The
  // package cards prove it: .package-card:hover applies translateY(-3px), so the
  // menu opened hundreds of pixels away from its toggle. Move the menu to the body
  // while it is open; body carries .vs-layout--admin, so the styling still applies.
  function detachMenu(overflow, menu) {
    if (menu.parentNode === document.body) return;
    overflow.__vsMenu = menu;
    document.body.appendChild(menu);
  }

  function reattachMenu(overflow) {
    var menu = overflow.__vsMenu;
    if (!menu) return;
    if (menu.parentNode === document.body) {
      // The row or card can be re-rendered while the menu is open; do not leak it.
      if (overflow.isConnected) {
        overflow.appendChild(menu);
      } else {
        menu.remove();
      }
    }
    menu.style.left = '';
    menu.style.top = '';
  }

  function itemsOf(menu) {
    return Array.prototype.slice.call(menu.querySelectorAll('.vs-overflow__item'));
  }

  function focusItem(el) {
    if (!el) return;
    try {
      el.focus({ preventScroll: true });
    } catch (err) {
      el.focus();
    }
  }

  // The menu is position:fixed so it escapes the table scroll box:
  // .vs-table-wrap is overflow:auto and would otherwise clip it.
  function positionOverflow(overflow) {
    var menu = menuOf(overflow);
    var toggle = toggleOf(overflow);
    if (!menu || !toggle || menu.hidden) return;

    var t = toggle.getBoundingClientRect();
    var mw = menu.offsetWidth;
    var mh = menu.offsetHeight;

    var left = t.right - mw;
    var maxLeft = window.innerWidth - VIEWPORT_PAD - mw;
    if (left > maxLeft) left = maxLeft;
    if (left < VIEWPORT_PAD) left = VIEWPORT_PAD;

    var top = t.bottom + OVERFLOW_GAP;
    if (top + mh > window.innerHeight - VIEWPORT_PAD) {
      var above = t.top - OVERFLOW_GAP - mh;
      top = above >= VIEWPORT_PAD ? above : Math.max(VIEWPORT_PAD, window.innerHeight - VIEWPORT_PAD - mh);
    }

    // Never let the menu land off-screen, whatever the toggle position is.
    var maxTop = Math.max(VIEWPORT_PAD, window.innerHeight - VIEWPORT_PAD - mh);
    if (top > maxTop) top = maxTop;
    if (top < VIEWPORT_PAD) top = VIEWPORT_PAD;

    menu.style.left = Math.round(left) + 'px';
    menu.style.top = Math.round(top) + 'px';
  }

  // Enhance every open menu so keyboard users get real menu semantics.
  function enhanceMenu(menu) {
    menu.setAttribute('role', 'menu');
    menu.setAttribute('aria-orientation', 'vertical');
    menu.querySelectorAll('.vs-overflow__separator').forEach(function (sep) {
      sep.setAttribute('role', 'separator');
    });
    itemsOf(menu).forEach(function (item) {
      item.setAttribute('role', 'menuitem');
      item.setAttribute('tabindex', '-1');
    });
  }

  function closeOverflow(overflow) {
    var menu = menuOf(overflow);
    var toggle = toggleOf(overflow);
    if (menu) menu.hidden = true;
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
    reattachMenu(overflow);
  }

  function openOverflow(overflow, focusLast) {
    var menu = menuOf(overflow);
    var toggle = toggleOf(overflow);
    if (!menu) return;
    enhanceMenu(menu);
    detachMenu(overflow, menu);
    menu.hidden = false;
    positionOverflow(overflow);
    if (toggle) toggle.setAttribute('aria-expanded', 'true');
    var items = itemsOf(menu);
    focusItem(focusLast ? items[items.length - 1] : items[0]);
  }

  function closeAllOverflows(except) {
    document.querySelectorAll('[data-overflow]').forEach(function (o) {
      if (o !== except) closeOverflow(o);
    });
  }

  function moveFocus(items, from, step) {
    if (!items.length) return;
    var index = items.indexOf(from);
    if (index === -1) index = step > 0 ? -1 : 0;
    focusItem(items[(index + step + items.length) % items.length]);
  }

  document.addEventListener('click', function (e) {
    var toggle = e.target.closest('[data-overflow-toggle]');
    if (toggle) {
      e.preventDefault();
      var overflow = toggle.closest('[data-overflow]');
      var wasOpen = !menuOf(overflow).hidden;
      closeAllOverflows(overflow);
      if (wasOpen) { closeOverflow(overflow); } else { openOverflow(overflow); }
      return;
    }

    // Choosing a menu item closes the menu (so its own confirm modal is on top).
    if (e.target.closest('[data-overflow-menu]')) {
      closeAllOverflows();
      return;
    }

    if (!e.target.closest('[data-overflow]')) closeAllOverflows();
  });

  document.addEventListener('keydown', function (e) {
    var toggle = e.target.closest ? e.target.closest('[data-overflow-toggle]') : null;

    if (toggle && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
      e.preventDefault();
      var overflow = toggle.closest('[data-overflow]');
      var menu = menuOf(overflow);
      var wasOpen = menu && !menu.hidden;
      closeAllOverflows(overflow);
      if (wasOpen) {
        var items = itemsOf(menu);
        focusItem(e.key === 'ArrowDown' ? items[0] : items[items.length - 1]);
      } else {
        openOverflow(overflow, e.key === 'ArrowUp');
      }
      return;
    }

    var openMenu = e.target.closest ? e.target.closest('[data-overflow-menu]') : null;
    if (openMenu) {
      var menuItems = itemsOf(openMenu);
      if (e.key === 'ArrowDown') { e.preventDefault(); moveFocus(menuItems, e.target, 1); return; }
      if (e.key === 'ArrowUp') { e.preventDefault(); moveFocus(menuItems, e.target, -1); return; }
      if (e.key === 'Home') { e.preventDefault(); focusItem(menuItems[0]); return; }
      if (e.key === 'End') { e.preventDefault(); focusItem(menuItems[menuItems.length - 1]); return; }
      if (e.key === 'Tab') { closeAllOverflows(); return; }
    }

    if (e.key !== 'Escape') return;
    document.querySelectorAll('[data-overflow]').forEach(function (o) {
      var m = menuOf(o);
      if (m && !m.hidden) {
        closeOverflow(o);
        focusItem(toggleOf(o));
      }
    });
  });

  // A fixed menu cannot follow its row, so close it rather than let it drift.
  window.addEventListener('scroll', function () { closeAllOverflows(); }, true);
  window.addEventListener('resize', function () { closeAllOverflows(); });

  function syncStatusSelect(select) {
    var variants = {};
    try { variants = JSON.parse(select.getAttribute('data-vs-variants') || '{}'); } catch (err) { variants = {}; }
    var variant = variants[select.value] || '';
    VARIANT_CLASSES.forEach(function (v) {
      select.classList.remove('vs-status-select--' + v);
    });
    if (variant && VARIANT_CLASSES.indexOf(variant) !== -1) {
      select.classList.add('vs-status-select--' + variant);
    }
  }

  function initStatusSelects(root) {
    (root || document).querySelectorAll('[data-vs-status-select]').forEach(function (select) {
      syncStatusSelect(select);
      if (select.dataset.vsBound === 'true') return;
      select.dataset.vsBound = 'true';
      select.addEventListener('change', function () { syncStatusSelect(select); });
    });
  }

  // Pages that swap rows in via AJAX call this after re-rendering.
  window.vsInitAdminUi = function (root) { initStatusSelects(root); };

  document.addEventListener('DOMContentLoaded', function () {
    initStatusSelects(document);
  });
})();
