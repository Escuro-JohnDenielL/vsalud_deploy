document.addEventListener("DOMContentLoaded", function () {
    applyStatusColors();
    attachEventListeners();

    let selectedEmail = "";
    let inquiryId;

    const replyModal = document.getElementById("replyModal");
    const replyOptions = document.getElementById("replyOptions");
    const replyMessage = document.getElementById("replyMessage");
    const sendReplyBtn = document.getElementById("sendReplyBtn");
    const cancelReplyBtn = document.getElementById("cancelReplyBtn");
    const closeReplyModal = document.getElementById("closeReplyModal");

    function openReplyModal(status) {
        const suggestions = {
            Pending: [
                "Acknowledge receipt",
                "Let them know you're reviewing their request",
                "Mention expected response timeframe",
                "Ask for any additional details needed",
            ],
            "In Progress": [
                "Provide updates on planning progress",
                "Share preliminary ideas or suggestions",
                "Request clarification on specific aspects",
                "Schedule follow-up meeting/call",
            ],
            Completed: [
                "Confirm all arrangements are finalized",
                "Provide final event details and timeline",
                "Share contact information for day-of coordination",
                "Include any last-minute instructions",
            ],
        };

        replyOptions.innerHTML = `<option value="" disabled selected>Select a suggestion</option>`;
        if (suggestions[status]) {
            suggestions[status].forEach((text) => {
                const option = document.createElement("option");
                option.value = text;
                option.textContent = text;
                replyOptions.appendChild(option);
            });
        }

        replyMessage.value = "";
        replyModal.style.display = "block";
    }

    replyOptions.addEventListener("change", function () {
        replyMessage.value = this.value;
    });

    cancelReplyBtn.addEventListener("click", () => {
        replyModal.style.display = "none";
    });

    closeReplyModal.addEventListener("click", () => {
        replyModal.style.display = "none";
    });

    sendReplyBtn.addEventListener("click", () => {
        const message = replyMessage.value.trim();
        if (!message) return alert("Please enter a message.");

        fetch("/admin/send-reply", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                ).content,
            },
            body: JSON.stringify({
                email: selectedEmail,
                message: message,
                inquiry_id: inquiryId,
            }),
        })
            .then((res) => res.json())
            .then((data) => {
                alert(data.message || "Reply sent successfully.");
                replyModal.style.display = "none";
            })
            .catch((err) => {
                console.error("Error:", err);
                alert("Failed to send reply.");
            });
    });

    document.querySelectorAll(".reply-btn").forEach((button) => {
        button.addEventListener("click", function () {
            const row = this.closest("tr");

            inquiryId = row.dataset.inquiryId;

            const select = row.querySelector(".status-dropdown");
            const status = select ? select.value : "Pending";

            selectedEmail = row
                .querySelector("td:nth-child(2)")
                .textContent.trim(); // Capture email
            openReplyModal(status);
        });
    });
});

function applyStatusColors() {
    document.querySelectorAll(".status-dropdown").forEach((select) => {
        updateStatusColor(select);
        select.addEventListener("change", function () {
            updateStatusColor(this);
            saveStatusToDatabase(this, this.value);
        });
    });
}

function updateStatusColor(select) {
    const value = select.value;
    select.style.color = "black";
    select.style.backgroundColor = "white";
    select.style.fontWeight = "normal";

    switch (value) {
        case "Pending":
            select.style.backgroundColor = "#ffc107";
            select.style.color = "black";
            select.style.fontWeight = "bold";
            break;
        case "In Progress":
            select.style.backgroundColor = "#17a2b8";
            select.style.color = "white";
            select.style.fontWeight = "bold";
            break;
        case "Completed":
            select.style.backgroundColor = "#28a745";
            select.style.color = "white";
            select.style.fontWeight = "bold";
            break;
        case "Cancelled":
            select.style.backgroundColor = "#dc3545";
            select.style.color = "white";
            select.style.fontWeight = "bold";
            break;
        default:
            select.style.backgroundColor = "#f8f9fa";
            select.style.color = "#6c757d";
            break;
    }
}

function attachEventListeners() {
    document.querySelectorAll(".undo-btn").forEach((button) => {
        button.addEventListener("click", function () {
            const inquiryId = this.getAttribute("data-inquiry-id");
            if (confirm("Are you sure you want to undo this reservation?")) {
                fetch("undo_reservation.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ inquiry_id: inquiryId }),
                })
                    .then((res) => res.json())
                    .then((data) => {
                        if (data.success) {
                            alert("Undo successful. Please refresh the page.");
                            location.reload();
                        } else {
                            alert("Undo failed: " + data.message);
                        }
                    })
                    .catch((error) => {
                        console.error("Undo error:", error);
                        alert("Something went wrong.");
                    });
            }
        });
    });
}

function saveStatusToDatabase(selectElement, newStatus) {
    const inquiryId = selectElement
        .closest("tr")
        .getAttribute("data-inquiry-id");

    fetch(`/admin/inquiries/${inquiryId}/update-status`, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
        body: JSON.stringify({
            status: newStatus,
        }),
    })
        .then(async (response) => {
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(
                    `Server error ${response.status}: ${errorText}`
                );
            }
            return response.json();
        })
        .then((data) => {
            if (data.success) {
                console.log("Status updated successfully.");
            } else {
                console.error("Failed to update status:", data.message);
            }
        })
        .catch((error) => {
            console.error("Error:", error.message);
        });
}
