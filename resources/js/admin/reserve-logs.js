// Reservation Logs JavaScript Functions
document.addEventListener("DOMContentLoaded", function () {
    setupEventListeners();

    paginateTable("paymentTableBody");
    paginateTable("reservationTableBody");

    const modal = document.getElementById("viewModal1");
    const modalImage = document.getElementById("receiptImage1");
    const closeModalBtn = document.querySelector(".close1");

    // Open modal
    document.querySelectorAll(".receipt-link1").forEach((link) => {
        link.addEventListener("click", function (e) {
            e.preventDefault();
            const receiptUrl = this.getAttribute("data-receipt");
            modalImage.src = receiptUrl;
            modal.style.display = "block";
        });
    });

    // Close modal
    closeModalBtn.addEventListener("click", function () {
        modal.style.display = "none";
        modalImage.src = "";
    });

    // Close modal when clicking outside
    window.addEventListener("click", function (e) {
        if (e.target === modal) {
            modal.style.display = "none";
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

            body.innerHTML = `
            <p><strong>Name:</strong> ${data.patron?.name || "N/A"}</p>
            <p><strong>Email:</strong> ${data.patron?.email || "N/A"}</p>
            <p><strong>Date:</strong> ${data.date || "N/A"}</p>
            <p><strong>Time:</strong> ${data.time || "N/A"}</p>
            <p><strong>Venue:</strong> ${data.venue || "N/A"}</p>
            <p><strong>Event Type:</strong> ${data.event_type || "N/A"}</p>
            <p><strong>Theme:</strong> ${data.theme_motif || "N/A"}</p>
            <p><strong>Message:</strong> ${data.message || "N/A"}</p>
            <p><strong>Status:</strong> ${data.status || "N/A"}</p>
        `;
        })
        .catch((err) => {
            console.error("Error fetching reservation:", err);
            body.innerHTML = `<p style="color:red;">Failed to load: ${err.message}</p>`;
        });
}

function closeModal() {
    document.getElementById("viewModal").style.display = "none";
}

// DELETE BUTTON HANDLER
function deleteReservation(id) {
    if (confirm(`Are you sure you want to delete reservation #${id}?`)) {
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

// BACKEND HOOKS (to implement later)
function updateReservationStatus(id, status) {
    // You can use fetch or axios to call your backend route
    console.log(`Updating reservation ${id} to status: ${status}`);
}

function deleteReservationFromServer(id) {
    // You can use fetch or axios to delete reservation
    console.log(`Deleting reservation ${id} from server`);
}

// Make functions accessible globally to Blade
window.viewReservation = viewReservation;
window.deleteReservation = deleteReservation;
window.viewReceipt = viewReceipt;
window.closeModal = closeModal;
window.nextPage = nextPage;
window.prevPage = prevPage;
