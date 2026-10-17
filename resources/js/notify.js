/**
 * Shared confirmation modal for every site notification.
 *
 * Replaces the old bottom-right "#toast" element, which auto-dismissed and had
 * pointer-events: none, so users could never tap or confirm it.
 *
 * Public API:
 *   window.vsNotify(message, type, options?)  -> type: success | error | warning | info
 *   window.showAdminToast(message, type?)     -> backwards-compatible alias
 */
(function () {
    'use strict';

    if (window.__vsNotifyReady) return;
    window.__vsNotifyReady = true;

    var TITLES = {
        success: 'Success',
        error: 'Error',
        warning: 'Warning',
        info: 'Notice',
    };

    var ICONS = {
        success: '\u2713',
        error: '!',
        warning: '!',
        info: 'i',
    };

    var queue = [];
    var lastFocused = null;

    function elements() {
        return {
            root: document.getElementById('vsNotifyModal'),
            dialog: document.querySelector('#vsNotifyModal .vs-notify__dialog'),
            icon: document.getElementById('vsNotifyIcon'),
            title: document.getElementById('vsNotifyTitle'),
            message: document.getElementById('vsNotifyMessage'),
            ok: document.getElementById('vsNotifyOk'),
        };
    }

    function normalizeType(type) {
        type = String(type || 'info').toLowerCase();
        if (type === 'danger') return 'error';
        return TITLES[type] ? type : 'info';
    }

    function onKeydown(event) {
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
            return;
        }

        // The OK button is the only focusable control in the dialog.
        if (event.key === 'Tab') {
            var els = elements();
            if (els.ok) {
                event.preventDefault();
                els.ok.focus();
            }
        }
    }

    function open(item) {
        var els = elements();
        if (!els.root) return;

        lastFocused = document.activeElement;

        els.root.classList.remove(
            'vs-notify--success',
            'vs-notify--error',
            'vs-notify--warning',
            'vs-notify--info'
        );
        els.root.classList.add('vs-notify--' + item.type);

        els.title.textContent = item.title || TITLES[item.type];
        els.message.textContent = item.message;
        els.icon.textContent = ICONS[item.type];

        els.root.hidden = false;
        document.addEventListener('keydown', onKeydown);

        if (els.ok) els.ok.focus();
    }

    function close() {
        var els = elements();
        if (!els.root || els.root.hidden) return;

        document.removeEventListener('keydown', onKeydown);
        els.root.hidden = true;

        if (lastFocused && typeof lastFocused.focus === 'function') {
            try {
                lastFocused.focus();
            } catch (error) {
                /* element may no longer be in the document */
            }
        }
        lastFocused = null;

        if (queue.length) {
            var next = queue.shift();
            window.setTimeout(function () {
                open(next);
            }, 60);
        }
    }

    window.vsNotify = function (message, type, options) {
        if (message === undefined || message === null || String(message) === '') return;

        var item = {
            message: String(message),
            type: normalizeType(type),
            title: options && options.title ? String(options.title) : null,
        };

        var els = elements();
        if (!els.root) {
            // Safety net for a page that has not been wired to the component.
            window.alert(item.message);
            return;
        }

        if (!els.root.hidden) {
            queue.push(item);
            return;
        }

        open(item);
    };

    // Existing admin code calls window.showAdminToast(); keep it working.
    window.showAdminToast = function (message, type) {
        window.vsNotify(message, type || 'success');
    };

    document.addEventListener('DOMContentLoaded', function () {
        var els = elements();
        if (!els.root) return;

        els.root.addEventListener('click', function (event) {
            if (event.target.closest('[data-vs-notify-dismiss]')) {
                close();
            }
        });

        if (els.ok) {
            els.ok.addEventListener('click', function (event) {
                event.preventDefault();
                close();
            });
        }

        // Flash payload emitted by <x-ui.notification-modal />.
        if (window.vsFlash && window.vsFlash.message) {
            window.vsNotify(window.vsFlash.message, window.vsFlash.type || 'info');
            window.vsFlash = null;
        }

        // Redirect notifications, e.g. ?toast=success&toast_msg=Inquiry%20successfully%20created!
        var params = new URLSearchParams(window.location.search);
        var toastType = params.get('toast');
        var toastMessage = params.get('toast_msg');

        if (toastType && toastMessage) {
            var decoded = toastMessage;
            try {
                decoded = decodeURIComponent(toastMessage);
            } catch (error) {
                /* keep the raw value */
            }

            window.vsNotify(decoded, toastType);

            if (window.history && window.history.replaceState) {
                window.history.replaceState(null, '', window.location.pathname);
            }
        }
    });
})();
