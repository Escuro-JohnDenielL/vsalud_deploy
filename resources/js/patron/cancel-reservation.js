document.addEventListener("DOMContentLoaded", () => {
    const lookupForm = document.getElementById("lookupForm");
    const cancelForm = document.getElementById("cancelForm");
    const detailsDiv = document.getElementById("reservationDetails");
    const detailsContent = document.getElementById("detailsContent");
    const messageContainer = document.getElementById("messageContainer");

    function showMessage(message, type = "info") {
        messageContainer.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
    }

    function clearMessage() {
        messageContainer.innerHTML = "";
    }

    function escapeHtml(value) {
        return String(value ?? "").replace(/[&<>"']/g, (char) => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '\"': "&quot;",
            "'": "&#39;",
        })[char]);
    }

    // Mirror vs_date(): "Aug 25, 2026"
    function formatDate(value) {
        if (!value) return "None";
        const raw = String(value).trim();
        const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);
        const date = match
            ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
            : new Date(raw);
        if (Number.isNaN(date.getTime())) return raw;
        return date.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
    }

    // Mirror vs_time(): "9:00 AM"
    function formatTimeToken(token) {
        const clean = token.trim().toLowerCase().replace(/\s+/g, "");
        const match = clean.match(/^(\d{1,2})(?::(\d{2}))?(am|pm)?$/);
        if (!match) return token.trim();
        let hours = parseInt(match[1], 10);
        const minutes = match[2] ? parseInt(match[2], 10) : 0;
        const meridiem = match[3];
        if (meridiem === "pm" && hours < 12) hours += 12;
        if (meridiem === "am" && hours === 12) hours = 0;
        const date = new Date();
        date.setHours(hours, minutes, 0, 0);
        return date.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit" });
    }

    // Reservation times are ranges ("9am-1pm") -> "9:00 AM to 1:00 PM"
    function formatTime(value) {
        if (!value) return "None";
        const raw = String(value).trim();
        const parts = raw.split(/\s*(?:[-\u2013]|\bto\b)\s*/i).filter(Boolean);
        if (parts.length === 0) return raw;
        return parts.map(formatTimeToken).join(" to ");
    }

    // Step 1: Look up reservation by tracking code plus booking email
    lookupForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        clearMessage();

        const code = document.getElementById("tracking_code").value.trim();
        const email = document.getElementById("lookup_email").value.trim();
        if (!code) {
            showMessage("Please enter a reservation code.", "error");
            return;
        }
        if (!email) {
            showMessage("Please enter the email address you used to book.", "error");
            return;
        }

        const csrf = document.querySelector('input[name="_token"]').value;

        try {
            const response = await fetch("/cancel-reservation/lookup", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf,
                },
                body: JSON.stringify({ tracking_code: code, email: email }),
            });

            const data = await response.json();

            if (!data.success) {
                showMessage(data.message || "Reservation not found.", "error");
                detailsDiv.style.display = "none";
                return;
            }

            // Only these four fields are returned; the page never receives the
            // patron's name or email address back from the server.
            const r = data.reservation;
            detailsContent.innerHTML = `
                <table class="details-table">
                    <tr><td><strong>Date:</strong></td><td>${escapeHtml(formatDate(r.date))}</td></tr>
                    <tr><td><strong>Venue:</strong></td><td>${escapeHtml(r.venue)}</td></tr>
                    <tr><td><strong>Package:</strong></td><td>${escapeHtml(r.package)}</td></tr>
                    <tr><td><strong>Status:</strong></td><td>${escapeHtml(r.status)}</td></tr>
                </table>
            `;

            detailsDiv.style.display = "block";
            showMessage("Reservation found. Fill in the reason below to submit your cancellation request.", "success");
        } catch (err) {
            showMessage("An error occurred. Please try again.", "error");
            console.error(err);
        }
    });

    // Step 2: Confirm, then submit the cancellation request
    const confirmDialog = document.getElementById("cancelConfirm");
    const confirmSubmitBtn = document.getElementById("cancelConfirmSubmit");
    const confirmDismissBtn = document.getElementById("cancelConfirmDismiss");
    let lastFocused = null;

    function openConfirm() {
        lastFocused = document.activeElement;
        confirmDialog.hidden = false;
        confirmSubmitBtn.focus();
    }

    function closeConfirm() {
        confirmDialog.hidden = true;
        if (lastFocused && typeof lastFocused.focus === "function") {
            lastFocused.focus();
        }
    }

    async function submitCancellation() {
        closeConfirm();
        clearMessage();

        const reserveCode = document.getElementById("tracking_code").value.trim();
        const patronEmail = document.getElementById("lookup_email").value.trim();
        const reason = document.getElementById("reason").value.trim();
        const csrf = document.querySelector('input[name="_token"]').value;

        try {
            const response = await fetch("/cancel-reservation/submit", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf,
                },
                body: JSON.stringify({
                    tracking_code: reserveCode,
                    patron_email: patronEmail,
                    reason: reason,
                }),
            });

            const data = await response.json();

            if (data.success) {
                showMessage(data.message, "success");
                // Hide the form and show a success state
                cancelForm.style.display = "none";
                detailsDiv.querySelector("h3").textContent = "Request Submitted";
            } else {
                showMessage(data.message || "Failed to submit request.", "error");
            }
        } catch (err) {
            showMessage("An error occurred. Please try again.", "error");
            console.error(err);
        }
    }

    cancelForm.addEventListener("submit", (e) => {
        e.preventDefault();
        openConfirm();
    });

    confirmSubmitBtn.addEventListener("click", submitCancellation);
    confirmDismissBtn.addEventListener("click", closeConfirm);

    confirmDialog.addEventListener("click", (e) => {
        if (e.target === confirmDialog) closeConfirm();
    });

    document.addEventListener("keydown", (e) => {
        if (!confirmDialog.hidden && e.key === "Escape") {
            closeConfirm();
        }
    });
});
