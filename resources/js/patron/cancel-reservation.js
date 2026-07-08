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

    // Step 1: Look up reservation by tracking code
    lookupForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        clearMessage();

        const code = document.getElementById("tracking_code").value.trim();
        if (!code) {
            showMessage("Please enter a reservation code.", "error");
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
                body: JSON.stringify({ tracking_code: code }),
            });

            const data = await response.json();

            if (!data.success) {
                showMessage(data.message || "Reservation not found.", "error");
                detailsDiv.style.display = "none";
                return;
            }

            // Populate reservation details
            const r = data.reservation;
            detailsContent.innerHTML = `
                <table class="details-table">
                    <tr><td><strong>Code:</strong></td><td>${r.tracking_code}</td></tr>
                    <tr><td><strong>Name:</strong></td><td>${r.patron_name}</td></tr>
                    <tr><td><strong>Email:</strong></td><td>${r.patron_email}</td></tr>
                    <tr><td><strong>Date:</strong></td><td>${r.date}</td></tr>
                    <tr><td><strong>Time:</strong></td><td>${r.time}</td></tr>
                    <tr><td><strong>Venue:</strong></td><td>${r.venue}</td></tr>
                    <tr><td><strong>Event Type:</strong></td><td>${r.event_type}</td></tr>
                    <tr><td><strong>Theme/Motif:</strong></td><td>${r.theme_motif}</td></tr>
                </table>
            `;

            // Set hidden fields
            document.getElementById("reserve_id").value = r.reserve_id;
            document.getElementById("patron_email").value = r.patron_email;

            detailsDiv.style.display = "block";
            showMessage("Reservation found. Fill in the reason below to submit your cancellation request.", "success");
        } catch (err) {
            showMessage("An error occurred. Please try again.", "error");
            console.error(err);
        }
    });

    // Step 2: Submit cancellation request
    cancelForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        clearMessage();

        const reserveId = document.getElementById("reserve_id").value;
        const patronEmail = document.getElementById("patron_email").value;
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
                    reserve_id: reserveId,
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
    });
});
