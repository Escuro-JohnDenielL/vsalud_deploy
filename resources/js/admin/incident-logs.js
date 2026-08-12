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
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast(res.message, 'success');
                closeModal('logIncidentModal');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(res.message || 'Failed to log incident.', 'error');
            }
        })
        .catch(() => showToast('An error occurred.', 'error'));
    });

    // --- View Incident ---
    document.querySelectorAll('.view-incident-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
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
        });
    });

    // --- Resolve / Update Incident ---
    document.querySelectorAll('.resolve-incident-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            document.getElementById('resolveIncidentId').value = id;
            document.getElementById('resolveIncidentForm').reset();
            document.getElementById('resolveResolvedAt').value = new Date().toISOString().slice(0, 16);
            openModal('resolveIncidentModal');
        });
    });

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

        fetch(window.incidentLogRoutes.update + '/' + id, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data),
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast(res.message, 'success');
                closeModal('resolveIncidentModal');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(res.message || 'Failed to update incident.', 'error');
            }
        })
        .catch(() => showToast('An error occurred.', 'error'));
    });

    // --- Delete Incident ---
    let deleteIncidentId = null;
    document.querySelectorAll('.delete-incident-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            deleteIncidentId = this.dataset.id;
            const title = this.dataset.title;
            document.getElementById('confirmDeleteMessage').textContent =
                `Are you sure you want to delete "${escapeHtml(title)}"? This action cannot be undone.`;
            openModal('confirmDeleteModal');
        });
    });

    document.getElementById('confirmDeleteYes')?.addEventListener('click', function() {
        if (!deleteIncidentId) return;

        fetch(window.incidentLogRoutes.destroy + '/' + deleteIncidentId, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showToast(res.message, 'success');
                closeModal('confirmDeleteModal');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(res.message || 'Failed to delete incident.', 'error');
            }
        })
        .catch(() => showToast('An error occurred.', 'error'));
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
