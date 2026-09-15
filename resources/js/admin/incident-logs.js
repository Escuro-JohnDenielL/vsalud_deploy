(function() {
    'use strict';

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // --- Toast helper ---
    let toastHideTimer = null;
    function showToast(message, type) {
        const toast = document.getElementById('toast');
        if (!toast) return;
        clearTimeout(toastHideTimer);
        toast.textContent = message;
        toast.className = 'toast-notification toast-' + type;
        // Force reflow so the entrance animation restarts on repeated toasts
        void toast.offsetWidth;
        toast.classList.add('show');
        toastHideTimer = setTimeout(() => {
            toast.classList.remove('show');
            toast.textContent = '';
        }, 4000);
    }

    // --- Modal helpers ---
    window.closeModal = function(id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.remove('open');
    };

    function openModal(id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.add('open');
    }

    // Close modal on backdrop click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('open');
            }
        });
    });

    // --- In-place table refresh ---
    // Log/update/delete responses carry fresh stats + rows + pagination, so the
    // page never has to reload just to reveal the change the user just made.
    function parseResponse(r) {
        return r.json().then(data => {
            if (!r.ok) {
                const validationError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
                throw new Error(validationError || data?.message || 'Request failed.');
            }
            return data;
        });
    }

    function currentPage() {
        const page = new URLSearchParams(window.location.search).get('page');
        return page ? (parseInt(page, 10) || 1) : 1;
    }

    // Keep ?page= in step with the page the payload actually returned (logging
    // always lands on page 1; a delete can pull the user back to an earlier one).
    function syncPage(page) {
        if (!page || page === currentPage() || !window.history.replaceState) return;
        const url = new URL(window.location.href);
        if (page === 1) {
            url.searchParams.delete('page');
        } else {
            url.searchParams.set('page', page);
        }
        window.history.replaceState(null, '', url.pathname + url.search);
    }

    function applyPayload(payload, focusId) {
        if (!payload) return;

        const tbody = document.getElementById('incidentRowsBody');
        if (tbody && typeof payload.rows === 'string') {
            tbody.innerHTML = payload.rows;
        }

        const pagination = document.getElementById('incidentPagination');
        if (pagination && typeof payload.pagination === 'string') {
            pagination.innerHTML = payload.pagination;
        }

        if (payload.stats) {
            Object.keys(payload.stats).forEach(key => {
                const el = document.querySelector('[data-stat="' + key + '"]');
                if (el) el.textContent = payload.stats[key];
            });
        }

        syncPage(payload.page);

        if (focusId && tbody) {
            const row = tbody.querySelector('tr[data-id="' + focusId + '"]');
            if (row) {
                row.classList.add('row-flash');
                row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => row.classList.remove('row-flash'), 2600);
            }
        }
    }

    // --- Log Incident ---
    document.getElementById('logIncidentBtn')?.addEventListener('click', function() {
        document.getElementById('logIncidentForm').reset();
        document.getElementById('incidentDetectedAt').value = new Date().toISOString().slice(0, 16);
        openModal('logIncidentModal');
    });

    document.getElementById('logIncidentForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());

        fetch(window.incidentLogRoutes.store, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        })
        .then(parseResponse)
        .then(res => {
            showToast(res.message, 'success');
            closeModal('logIncidentModal');
            // The new incident sorts to the top of page 1 — show it right there.
            applyPayload(res.payload, res.focus_id);
        })
        .catch(err => showToast(err.message || 'Failed to log incident.', 'error'));
    });

    // --- Row actions (delegated) ---
    // Rows are re-rendered in place after every change, so listeners live on the
    // table body instead of on individual buttons.
    document.getElementById('incidentRowsBody')?.addEventListener('click', function(e) {
        const viewBtn = e.target.closest('.view-incident-btn');
        if (viewBtn) { openIncidentDetail(viewBtn.dataset.id); return; }

        const resolveBtn = e.target.closest('.resolve-incident-btn');
        if (resolveBtn) { openResolveModal(resolveBtn.dataset.id); return; }

        const deleteBtn = e.target.closest('.delete-incident-btn');
        if (deleteBtn) { openDeleteModal(deleteBtn.dataset.id, deleteBtn.dataset.title); return; }
    });

    // --- View Incident ---
    function openIncidentDetail(id) {
        const body = document.getElementById('incidentDetailBody');
        body.innerHTML = '<p class="text-center text-muted">Loading...</p>';
        openModal('viewIncidentModal');

        fetch(window.incidentLogRoutes.show + '/' + id, {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(incident => {
            const severityClass = {
                critical: 'danger',
                high: 'warning',
                medium: 'info',
                low: 'success'
            }[incident.severity] || 'info';

            const statusClass = {
                open: 'danger',
                investigating: 'warning',
                resolved: 'success',
                closed: 'info'
            }[incident.status] || 'info';

            body.innerHTML = `
                <div class="detail-section">
                    <div class="detail-section-title">Overview</div>
                    <div class="detail-section-card">
                        <div class="detail-row">
                            <span class="detail-label">Title</span>
                            <span class="detail-value"><strong>${escapeHtml(incident.title)}</strong></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Severity</span>
                            <span class="detail-value"><span class="badge-modern ${severityClass}">${ucfirst(incident.severity)}</span></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Status</span>
                            <span class="detail-value"><span class="badge-modern ${statusClass}">${ucfirst(incident.status)}</span></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Detected</span>
                            <span class="detail-value">${formatDate(incident.detected_at)}</span>
                        </div>
                        ${incident.resolved_at ? `<div class="detail-row"><span class="detail-label">Resolved</span><span class="detail-value">${formatDate(incident.resolved_at)}</span></div>` : ''}
                        <div class="detail-row" style="border:none;">
                            <span class="detail-label">Reported By</span>
                            <span class="detail-value">${escapeHtml(incident.reported_by || '—')}</span>
                        </div>
                    </div>
                </div>
                <div class="detail-section">
                    <div class="detail-section-title">Description</div>
                    <div class="detail-description-block">${escapeHtml(incident.description)}</div>
                </div>
                ${incident.resolution ? `
                <div class="detail-section">
                    <div class="detail-section-title">Resolution</div>
                    <div class="detail-description-block">${escapeHtml(incident.resolution)}</div>
                </div>
                ` : ''}
            `;
        })
        .catch(() => {
            body.innerHTML = '<p class="text-center text-danger">Failed to load incident details.</p>';
        });
    }

    // --- Resolve / Update Incident ---
    function openResolveModal(id) {
        document.getElementById('resolveIncidentId').value = id;
        document.getElementById('resolveIncidentForm').reset();
        document.getElementById('resolveResolvedAt').value = new Date().toISOString().slice(0, 16);
        openModal('resolveIncidentModal');
    }

    // Toggle resolved_at field visibility based on status
    document.getElementById('resolveStatus')?.addEventListener('change', function() {
        const group = document.getElementById('resolvedAtGroup');
        if (this.value === 'resolved' || this.value === 'closed') {
            group.style.display = 'block';
        } else {
            group.style.display = 'none';
        }
    });
    // Initial state
    document.getElementById('resolvedAtGroup') && (document.getElementById('resolvedAtGroup').style.display = 'none');

    document.getElementById('resolveIncidentForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('resolveIncidentId').value;
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        delete data.incident_id;

        // Remove resolved_at if not needed
        const status = data.status;
        if (status !== 'resolved' && status !== 'closed') {
            delete data.resolved_at;
        }

        fetch(window.incidentLogRoutes.update + '/' + id + '?page=' + currentPage(), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        })
        .then(parseResponse)
        .then(res => {
            showToast(res.message, 'success');
            closeModal('resolveIncidentModal');
            applyPayload(res.payload, res.focus_id);
        })
        .catch(err => showToast(err.message || 'Failed to update incident.', 'error'));
    });

    // --- Delete Incident ---
    let deleteIncidentId = null;
    function openDeleteModal(id, title) {
        deleteIncidentId = id;
        // textContent escapes on its own, so the title must NOT be pre-escaped here
        document.getElementById('confirmDeleteMessage').textContent =
            `Are you sure you want to delete "${title || 'this incident'}"? This action cannot be undone.`;
        openModal('confirmDeleteModal');
    }

    document.getElementById('confirmDeleteYes')?.addEventListener('click', function() {
        if (!deleteIncidentId) return;

        fetch(window.incidentLogRoutes.destroy + '/' + deleteIncidentId + '?page=' + currentPage(), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        })
        .then(parseResponse)
        .then(res => {
            showToast(res.message, 'success');
            closeModal('confirmDeleteModal');
            applyPayload(res.payload);
            deleteIncidentId = null;
        })
        .catch(err => showToast(err.message || 'Failed to delete incident.', 'error'));
    });

    // --- Utilities ---
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function ucfirst(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function formatDate(dateStr) {
        if (!dateStr) return '—';
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-US', {
            month: 'short', day: 'numeric', year: 'numeric',
            hour: 'numeric', minute: '2-digit', hour12: true
        });
    }
})();
