/**
 * Audit Logs page.
 *
 * The table itself is server-rendered (plain GET form, so every filtered view has
 * a shareable URL). This file only handles the detail modal: it fetches one entry
 * as JSON and fills the modal's fields with textContent — never innerHTML —
 * because descriptions, IP addresses and user agents are attacker-controlled.
 */
(function () {
    'use strict';

    const routes = window.auditLogRoutes || {};
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // --- Modal helpers (mirrors resources/js/admin/incident-logs.js) ---
    window.closeModal = window.closeModal || function (id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.remove('open');
    };

    function openModal(id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.add('open');
    }

    document.querySelectorAll('.modal').forEach((modal) => {
        modal.addEventListener('click', function (e) {
            if (e.target === this) this.classList.remove('open');
        });
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal('auditDetailModal');
    });

    // --- Detail modal ---
    const modal = document.getElementById('auditDetailModal');
    if (!modal) return;

    const fields = {
        subtitle: document.getElementById('auditDetailSubtitle'),
        when: document.getElementById('auditDetailWhen'),
        admin: document.getElementById('auditDetailAdmin'),
        category: document.getElementById('auditDetailCategory'),
        activity: document.getElementById('auditDetailActivity'),
        subject: document.getElementById('auditDetailSubject'),
        request: document.getElementById('auditDetailRequest'),
        ip: document.getElementById('auditDetailIp'),
        logId: document.getElementById('auditDetailLogId'),
        description: document.getElementById('auditDetailDescription'),
        userAgent: document.getElementById('auditDetailUserAgent'),
    };

    const DASH = '—';

    function setText(node, value) {
        if (node) node.textContent = value === null || value === undefined || value === '' ? DASH : String(value);
    }

    function isPristine() {
        return fields.when && fields.when.textContent === '—';
    }

    function showEntry(entry) {
        setText(fields.subtitle, entry.event_label);

        setText(fields.when, entry.created_at);
        setText(
            fields.admin,
            entry.admin_email
                ? `${entry.admin_name} · ${entry.admin_email}${entry.admin_role ? ` · ${entry.admin_role}` : ''}`
                : entry.admin_name
        );

        if (fields.category) {
            fields.category.textContent = entry.category_label || DASH;
            fields.category.className = `audit-cat audit-cat-${entry.category_key || 'system'}`;
        }

        setText(fields.activity, entry.event_label);
        setText(fields.subject, entry.subject);
        setText(
            fields.request,
            entry.http_method ? `${entry.http_method} ${entry.route_name || ''}`.trim() : entry.route_name
        );
        setText(fields.ip, entry.ip_address);
        setText(fields.logId, entry.log_id);
        setText(fields.description, entry.description);
        setText(fields.userAgent, entry.user_agent);

        fields.description?.scrollTo({ top: 0 });
        fields.userAgent?.scrollTo({ top: 0 });
    }

    function showMessage(message) {
        setText(fields.subtitle, 'Unable to load this entry');
        setText(fields.description, message);
    }

    document.addEventListener('click', (e) => {
        const button = e.target.closest('.audit-view-btn');
        if (!button) return;

        const id = button.dataset.id;
        if (!id) return;

        openModal('auditDetailModal');

        // Only clear the previous entry's contents, not the layout placeholders.
        if (!isPristine()) {
            setText(fields.subtitle, 'Loading entry…');
            ['when', 'admin', 'activity', 'subject', 'request', 'ip', 'logId', 'description', 'userAgent']
                .forEach((key) => setText(fields[key], DASH));
        }

        const url = (routes.show || '').replace('__ID__', encodeURIComponent(id));

        fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken || '',
            },
            credentials: 'same-origin',
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(
                        response.status === 403
                            ? 'You do not have permission to view this entry.'
                            : 'The entry could not be retrieved.'
                    );
                }
                return response.json();
            })
            .then(showEntry)
            .catch((error) => showMessage(error.message || 'The entry could not be retrieved.'));
    });

    // --- Filter selects apply immediately; dates and search use the Apply button ---
    document.querySelectorAll('[data-audit-autosubmit]').forEach((select) => {
        select.addEventListener('change', () => select.form?.submit());
    });
})();
