// Helper: escape HTML to prevent XSS when injecting user data
function escHtml(str) {
    if (!str) return str;
    const div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
}

// Reservation Logs JavaScript Functions
document.addEventListener("DOMContentLoaded", function () {
    setupEventListeners();

    paginateTable("paymentTableBody");
    paginateTable("reservationTableBody");

    const receiptModal = document.getElementById("receiptModal");
    const modalImage = document.getElementById("receiptImage1");
    const closeReceiptBtn = document.getElementById("closeReceiptModal");

    // Open receipt modal
    document.querySelectorAll(".receipt-link1").forEach((link) => {
        link.addEventListener("click", function (e) {
            e.preventDefault();
            const receiptUrl = this.getAttribute("data-receipt");
            modalImage.src = receiptUrl;
            receiptModal.style.display = "flex";
            receiptModal.classList.add('open');
        });
    });

    // Close receipt modal
    closeReceiptBtn.addEventListener("click", function () {
        receiptModal.style.display = "none";
        receiptModal.classList.remove('open');
        modalImage.src = "";
    });

    // Close receipt modal when clicking outside
    window.addEventListener("click", function (e) {
        if (e.target === receiptModal) {
            receiptModal.style.display = "none";
            receiptModal.classList.remove('open');
            modalImage.src = "";
        }
    });
});

let currentPages = {
    paymentTableBody: 1,
    reservationTableBody: 1,
};

const rowsPerPage = 5;

function paginateTable(tableId) {
    const tbody = document.getElementById(tableId);
    const rows = Array.from(tbody.querySelectorAll("tr"));
    const totalPages = Math.ceil(rows.length / rowsPerPage);
    const pageInfo = document.getElementById(
        tableId.replace("TableBody", "PageInfo")
    );
    const currentPage = currentPages[tableId];

    // Hide all rows
    rows.forEach((row) => (row.style.display = "none"));

    // Show rows for current page
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    rows.slice(start, end).forEach((row) => (row.style.display = ""));

    if (pageInfo) {
        pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
    }
}

function nextPage(tableId) {
    const tbody = document.getElementById(tableId);
    const rows = Array.from(tbody.querySelectorAll("tr"));
    const totalPages = Math.ceil(rows.length / rowsPerPage);

    if (currentPages[tableId] < totalPages) {
        currentPages[tableId]++;
        paginateTable(tableId);
    }
}

function prevPage(tableId) {
    if (currentPages[tableId] > 1) {
        currentPages[tableId]--;
        paginateTable(tableId);
    }
}

// Setup for pagination and dropdown events
function setupEventListeners() {
    const prevBtn = document.getElementById("prevBtn");
    const nextBtn = document.getElementById("nextBtn");

    if (prevBtn) {
        prevBtn.disabled = true;
        prevBtn.style.opacity = "0.5";
    }
    if (nextBtn) {
        nextBtn.disabled = true;
        nextBtn.style.opacity = "0.5";
    }

    document.addEventListener("change", function (e) {
        if (e.target.classList.contains("status-dropdown")) {
            handleStatusChange(e.target);
        }
    });

    // Add event listener for modal close buttons
    document.addEventListener("click", function (e) {
        if (
            e.target.classList.contains("modal-close") ||
            e.target.getAttribute("onclick") === "closeModal()"
        ) {
            closeModal();
        }
    });

    // Close modal when clicking outside
    document.addEventListener("click", function (e) {
        const modal = document.getElementById("viewModal");
        if (e.target === modal) {
            closeModal();
        }
    });
}

// STATUS CHANGE HANDLER
function handleStatusChange(selectElement) {
    const reservationId = selectElement.dataset.id;
    const newStatus = selectElement.value;

    showNotification(`Status changed to ${newStatus}`, "success");

    // Optional: Send status update to backend
    updateReservationStatus(reservationId, newStatus);
}

// VIEW BUTTON HANDLER
function viewReservation(id) {
    const modal = document.getElementById("viewModal");
    const body = document.getElementById("modalBody");

    modal.style.display = "flex";
    modal.classList.add('open');
    body.innerHTML = `Loading reservation #${id}...`;

    // Get CSRF token
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    fetch(`/admin/reservations/${id}`, {
        method: "GET",
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            ...(csrfToken && { "X-CSRF-TOKEN": csrfToken }),
        },
    })
        .then((response) => {
            // Check if the response is JSON
            const contentType = response.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                throw new Error(
                    "Server returned HTML instead of JSON. Check your routes."
                );
            }

            if (!response.ok) {
                return response.json().then((err) => {
                    throw new Error(err.message || `HTTP ${response.status}`);
                });
            }
            return response.json();
        })
        .then((data) => {
            // Check if data has error property
            if (data.error) {
                throw new Error(data.message);
            }

            const status = (data.status || "N/A").toLowerCase();
            let badgeClass = "info";
            if (status === "active") badgeClass = "success";
            else if (status === "completed") badgeClass = "info";
            else if (status === "canceled") badgeClass = "danger";

            const statusLabel = data.status ? data.status.charAt(0).toUpperCase() + data.status.slice(1) : "N/A";

            body.innerHTML = `
            <!-- Header: Name + Status badge -->
            <div class="modal-detail-header">
                <h3 class="detail-name">${escHtml(data.patron?.name || "N/A")}</h3>
                <span class="badge-modern ${badgeClass}">${statusLabel}</span>
            </div>

            <!-- Contact -->
            <div class="detail-section">
                <div class="detail-section-title">Contact</div>
                <div class="detail-row">
                    <span class="detail-label">Email</span>
                    <span class="detail-value">
                        <span class="detail-icon">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            ${escHtml(data.patron?.email || "N/A")}
                        </span>
                    </span>
                </div>
            </div>

            <!-- Booking Details -->
            <div class="detail-section">
                <div class="detail-section-title">Booking Details</div>
                <div class="detail-section-card">
                    <div class="detail-row">
                        <span class="detail-label">Code</span>
                        <span class="detail-value"><span class="code-chip">${escHtml(data.tracking_code || "N/A")}</span></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Date</span>
                        <span class="detail-value">
                            <span class="detail-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                ${escHtml(data.date || "N/A")}
                            </span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Time</span>
                        <span class="detail-value">
                            <span class="detail-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                ${escHtml(data.time || "N/A")}
                            </span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Venue</span>
                        <span class="detail-value">
                            <span class="detail-icon">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                ${escHtml(data.venue || "N/A")}
                            </span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Package Info -->
            <div class="detail-section">
                <div class="detail-section-title">Package Info</div>
                <div class="detail-row">
                    <span class="detail-label">Event Type</span>
                    <span class="detail-value">${escHtml(data.event_type || "N/A")}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Theme</span>
                    <span class="detail-value">${escHtml(data.theme_motif || "N/A")}</span>
                </div>
                <div class="detail-row" style="border:none;">
                    <span class="detail-label">Message</span>
                    <span class="detail-value"><div class="detail-message-block">${escHtml(data.message || "N/A")}</div></span>
                </div>
            </div>
        `;
        })
        .catch((err) => {
            console.error("Error fetching reservation:", err);
            body.innerHTML = `<p style="color:red;">Failed to load: ${err.message}</p>`;
        });
}

function closeModal() {
    const viewModal = document.getElementById("viewModal");
    const viewModalPayment = document.getElementById("viewModalPayment");
    if (viewModal) {
        viewModal.style.display = "none";
        viewModal.classList.remove('open');
    }
    if (viewModalPayment) {
        viewModalPayment.style.display = "none";
        viewModalPayment.classList.remove('open');
    }
}

// DELETE BUTTON HANDLER
let pendingDeleteId = null;

function deleteReservation(id) {
    const modal = document.getElementById('confirmDeleteReservationModal');
    document.getElementById('confirmDeleteReservationMessage').textContent = `Are you sure you want to delete reservation #${id}?`;
    modal.style.display = 'flex';
    modal.classList.add('open');
    pendingDeleteId = id;
}

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('confirmDeleteReservationModal');
    const closeBtn = document.getElementById('closeConfirmDeleteModal');
    const noBtn = document.getElementById('confirmDeleteReservationNo');
    const yesBtn = document.getElementById('confirmDeleteReservationYes');

    function closeModal() {
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('open');
        }
        pendingDeleteId = null;
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (noBtn) noBtn.addEventListener('click', closeModal);
    if (modal) {
        window.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
    }

    if (yesBtn) {
        yesBtn.addEventListener('click', function() {
            const id = pendingDeleteId;
            closeModal();
            if (!id) return;
            executeDelete(id);
        });
    }
});

function executeDelete(id) {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    fetch(`/admin/reservations/${id}`, {
        method: "DELETE",
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            ...(csrfToken && { "X-CSRF-TOKEN": csrfToken }),
        },
    })
            .then((response) => {
                const contentType = response.headers.get("content-type");
                if (!contentType || !contentType.includes("application/json")) {
                    throw new Error(
                        "Server returned HTML instead of JSON. Check your routes."
                    );
                }

                if (!response.ok) {
                    return response.json().then((err) => {
                        throw new Error(
                            err.message || `HTTP ${response.status}`
                        );
                    });
                }
                return response.json();
            })
            .then((data) => {
                if (data.error) {
                    throw new Error(data.message);
                }

                showNotification(`Reservation #${id} deleted`, "success");
                // Optionally reload the page or remove row from DOM
                setTimeout(() => window.location.reload(), 1000);
            })
            .catch((err) => {
                console.error("Error deleting reservation:", err);
                showNotification(`Failed to delete: ${err.message}`, "error");
            });
}

// RECEIPT VIEW (stub)
function viewReceipt(file) {
    showNotification(`Opening receipt: ${file}`, "info");
}

// NOTIFICATION SYSTEM
function showNotification(message, type = "info") {
    const notification = document.createElement("div");
    notification.className = `notification notification-${type}`;
    notification.textContent = message;

    if (!document.getElementById("notificationStyles")) {
        addNotificationStyles();
    }

    document.body.appendChild(notification);

    setTimeout(() => notification.remove(), 3000);
}

function addNotificationStyles() {
    const styles = `
    <style id="notificationStyles">
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: 500;
            z-index: 1001;
            animation: slideIn 0.3s ease;
        }
        .notification-success { background: #28a745; }
        .notification-error { background: #dc3545; }
        .notification-info { background: #17a2b8; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>`;
    document.head.insertAdjacentHTML("beforeend", styles);
}

// Update reservation status via AJAX (same pattern as inquiries)
function updateReservationStatus(id, status) {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    fetch(`/admin/reservations/${id}/status`, {
        method: "PATCH",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": csrfToken,
        },
        body: JSON.stringify({ status: status }),
    })
        .then(async (response) => {
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`Server error ${response.status}: ${errorText}`);
            }
            return response.json();
        })
        .then((data) => {
            if (data.success) {
                console.log("Reservation status updated successfully.");
            } else {
                console.error("Failed to update status:", data.message);
                showNotification(data.message || "Failed to update status", "error");
            }
        })
        .catch((error) => {
            console.error("Error updating reservation status:", error.message);
            showNotification("Failed to update reservation status", "error");
        });
}

function deleteReservationFromServer(id) {
    // You can use fetch or axios to delete reservation
    console.log(`Deleting reservation ${id} from server`);
}

// PAYMENT STATUS CHANGE HANDLER
document.addEventListener("change", function (e) {
    if (e.target.classList.contains("payment-status-dropdown")) {
        const paymentId = e.target.dataset.paymentId;
        const newStatus = e.target.value;
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

        fetch(`/admin/payments/${paymentId}/status`, {
            method: "PATCH",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
            },
            body: JSON.stringify({ status: newStatus }),
        })
            .then((res) => res.json())
            .then((data) => {
                if (data.success) {
                    showNotification(data.message, "success");
                } else {
                    showNotification(data.message || "Failed to update status", "error");
                }
            })
            .catch(() => {
                showNotification("Failed to update payment status", "error");
            });
    }
});

// Make functions accessible globally to Blade
window.viewReservation = viewReservation;
window.deleteReservation = deleteReservation;
window.viewReceipt = viewReceipt;
window.closeModal = closeModal;
window.nextPage = nextPage;
window.prevPage = prevPage;
