/**
 * Escape HTML to prevent XSS
 */
function escHtml(str) {
    if (!str) return str;
    const div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
}

/**
 * Reservation status (inquiry.status enum) to badge class. The enum values
 * are Pending / In Progress / Completed / Cancelled.
 */
const RESERVATION_STATUS_CLASSES = {
    pending: "badge-warning",
    "in progress": "badge-info",
    completed: "badge-success",
    cancelled: "badge-danger",
    canceled: "badge-danger",
};

const RESERVATION_STATUS_TOKENS = {
    pending: "vs-badge--warning",
    "in progress": "vs-badge--info",
    completed: "vs-badge--success",
    cancelled: "vs-badge--danger",
    canceled: "vs-badge--danger",
};

/**
 * Payment status label map. The single place the display-only labels for the
 * payments.status enum live. The DB enum is not changed.
 */
const PAYMENT_STATUS = {
    pending: { label: "Awaiting Verification", badgeClass: "badge-warning", tokenClass: "vs-badge--warning" },
    approved: { label: "Payment Confirmed", badgeClass: "badge-success", tokenClass: "vs-badge--success" },
    rejected: { label: "Payment Rejected", badgeClass: "badge-danger", tokenClass: "vs-badge--danger" },
    refunded: { label: "Refunded", badgeClass: "badge-muted", tokenClass: "vs-badge--neutral" },
};

/**
 * Build a nice SVG icon by name
 */
function iconSvg(name) {
    const icons = {
        email: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>`,
        phone: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>`,
        user: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
        calendar: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>`,
        clock: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`,
        venue: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>`,
        package: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>`,
        status: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`,
        message: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>`,
    };
    return icons[name] || '';
}

/**
 * Build a section of reservation details
 */
function detailSection(title, rows) {
    const filtered = rows.filter(r => r.value !== undefined && r.value !== null && r.value !== '');
    if (filtered.length === 0) return '';
    return `
        <div class="detail-section">
            <div class="detail-section-title">${title}</div>
            <div class="detail-section-card">
                ${filtered.map(r => `
                    <div class="detail-row">
                        <span class="detail-label">${r.label}</span>
                        <span class="detail-value">
                            ${r.icon ? `<span class="detail-icon">${r.icon} ${escHtml(String(r.value))}</span>` : escHtml(String(r.value))}
                        </span>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("reservationForm");
    const modal = document.getElementById("reservationModal");
    const closeBtn = document.getElementById("closeReservationModal");
    const detailsContainer = document.getElementById("reservationDetails");

    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        const code = document.getElementById("reservation_code").value.trim();
        const email = document.getElementById("email").value.trim();

        if (!code || !email) return alert("Please enter both your reservation code and email address.");

        const csrf = document.querySelector('input[name="_token"]').value;

        try {
            const response = await fetch("/fetch-reservation", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf,
                },
                body: JSON.stringify({ reservation_code: code, email: email }),
            });

            // Rate limited by throttle:20,1 on the lookup route. The throttle
            // response is not JSON, so check the status before parsing.
            if (response.status === 429) {
                return alert("Too many attempts. Please wait a minute and try again.");
            }

            const data = await response.json();

            if (!data.success) {
                alert(data.message || "Reservation not found.");
                return;
            }

            const r = data.reservation;

            // Determine status badge class. The reservation status enum stores
            // Pending / In Progress / Completed / Cancelled.
            const statusRaw = String(r.Status || "Pending").trim().toLowerCase();
            const badgeClass = RESERVATION_STATUS_CLASSES[statusRaw] || "badge-muted";
            const statusTokenClass = RESERVATION_STATUS_TOKENS[statusRaw] || "vs-badge--neutral";

            // Payment status badge (second badge). No payment or a null status
            // renders nothing; an unrecognized status falls back to its escaped
            // raw value.
            let paymentBadgeHtml = "";
            const paymentRaw = r["Payment Status"];
            if (paymentRaw !== null && paymentRaw !== undefined && String(paymentRaw).trim() !== "") {
                const paymentKey = String(paymentRaw).trim().toLowerCase();
                const payment = PAYMENT_STATUS[paymentKey];
                const paymentLabel = payment ? payment.label : String(paymentRaw);
                const paymentBadgeClass = payment ? payment.badgeClass : "badge-muted";
                const paymentTokenClass = payment ? payment.tokenClass : "vs-badge--neutral";
                paymentBadgeHtml = `<span class="badge-modern ${paymentBadgeClass} vs-badge ${paymentTokenClass}">${escHtml(paymentLabel)}</span>`;
            }

            // Build organized sections: known keys first, then extras
            const knownKeys = ["Name", "Email", "Contact", "Date", "Time", "Venue", "Event Type", "Theme & Motif", "Status", "Payment Status"];
            const extras = Object.keys(r).filter(k => !knownKeys.includes(k));

            // Status badge header
            let headerHtml = `
                <div class="modal-detail-header">
                    <h3 class="detail-name">${escHtml(r.Name || "Reservation Details")}</h3>
                    <span class="badge-modern ${badgeClass} vs-badge ${statusTokenClass}">${escHtml(r.Status || "Pending")}</span>
                    ${paymentBadgeHtml}
                </div>
            `;

            // Contact section
            const contactRows = [
                { label: "Name", value: r.Name, icon: iconSvg("user") },
                { label: "Email", value: r.Email, icon: iconSvg("email") },
                { label: "Contact", value: r.Contact, icon: iconSvg("phone") },
            ];

            // Booking details section
            const bookingRows = [
                { label: "Date", value: r.Date, icon: iconSvg("calendar") },
                { label: "Time", value: r.Time, icon: iconSvg("clock") },
                { label: "Venue", value: r.Venue, icon: iconSvg("venue") },
            ];

            // Package info section
            const packageRows = [
                { label: "Event Type", value: r["Event Type"], icon: iconSvg("package") },
                { label: "Theme & Motif", value: r["Theme & Motif"] },
            ];

            // Extra dynamic fields
            let extraHtml = '';
            if (extras.length > 0) {
                const extraRows = extras.map(k => ({ label: k, value: r[k] }));
                extraHtml = detailSection("Additional Info", extraRows);
            }

            // Message field if it exists in extras
            const messageValue = r["Message"] || r["message"] || r["Additional message"];
            let messageHtml = '';
            if (messageValue) {
                messageHtml = `
                    <div class="detail-section">
                        <div class="detail-section-title">Message</div>
                        <div class="detail-message-block">${escHtml(String(messageValue))}</div>
                    </div>
                `;
            }

            detailsContainer.innerHTML = `
                ${headerHtml}
                ${detailSection("Contact", contactRows)}
                ${detailSection("Booking Details", bookingRows)}
                ${detailSection("Package Info", packageRows)}
                ${extraHtml}
                ${messageHtml}
            `;

            window.vsModalPrepareOpen(modal);
            modal.style.display = "block";
        } catch (err) {
            alert("An error occurred while fetching the reservation.");
            console.error(err);
        }
    });

    closeBtn.addEventListener("click", () => {
        window.vsModalCloseAfterFade(modal, () => {
            modal.style.display = "none";
        });
    });

    window.addEventListener("click", (e) => {
        if (e.target === modal) {
            window.vsModalCloseAfterFade(modal, () => {
                modal.style.display = "none";
            });
        }
    });
});
