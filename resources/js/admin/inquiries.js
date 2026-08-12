document.addEventListener("DOMContentLoaded", function () {
    applyStatusColors();
    attachEventListeners();

    let selectedEmail = "";
    let inquiryId;

    const replyModal = document.getElementById("replyModal");
    const replyMessage = document.getElementById("replyMessage");
    const sendReplyBtn = document.getElementById("sendReplyBtn");
    const cancelReplyBtn = document.getElementById("cancelReplyBtn");
    const closeReplyModal = document.getElementById("closeReplyModal");
    const aiDraftBtn = document.getElementById("aiDraftBtn");
    const aiDraftLabel = document.getElementById("aiDraftLabel");
    const aiDraftStatus = document.getElementById("aiDraftStatus");

    function openReplyModal() {
        replyMessage.value = "";
        aiDraftStatus.textContent = "";
        aiDraftStatus.className = "ai-draft-status";
        replyModal.style.display = "flex";
        replyModal.classList.add('open');
    }

    cancelReplyBtn.addEventListener("click", () => {
        replyModal.style.display = "none";
        replyModal.classList.remove('open');
    });

    closeReplyModal.addEventListener("click", () => {
        replyModal.style.display = "none";
        replyModal.classList.remove('open');
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
                replyModal.classList.remove('open');
            })
            .catch((err) => {
                console.error("Error:", err);
                alert("Failed to send reply.");
            });
    });

    // AI-assisted reply draft (Google Gemini)
    aiDraftBtn.addEventListener("click", async () => {
        if (!inquiryId) return;

        aiDraftBtn.disabled = true;
        aiDraftLabel.textContent = "Generating draft...";
        aiDraftStatus.textContent = "";
        aiDraftStatus.className = "ai-draft-status";

        try {
            const res = await fetch("/admin/inquiries/draft-reply", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                },
                body: JSON.stringify({ inquiry_id: inquiryId }),
            });

            const data = await res.json();

            if (data.success) {
                replyMessage.value = data.draft;
                aiDraftStatus.textContent = "Draft generated — review and edit before sending.";
                aiDraftStatus.className = "ai-draft-status ai-draft-success";
            } else {
                aiDraftStatus.textContent = data.message || "Could not generate a draft.";
                aiDraftStatus.className = "ai-draft-status ai-draft-error";
            }
        } catch (err) {
            console.error("Error:", err);
            aiDraftStatus.textContent = "Failed to reach the AI service.";
            aiDraftStatus.className = "ai-draft-status ai-draft-error";
        } finally {
            aiDraftBtn.disabled = false;
            aiDraftLabel.textContent = "✨ AI Draft Reply";
        }
    });

    document.querySelectorAll(".reply-btn").forEach((button) => {
        button.addEventListener("click", function () {
            inquiryId = this.dataset.inquiryId;
            selectedEmail = this.dataset.email;
            openReplyModal();
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
            const modal = document.getElementById('confirmUndoModal');
            const message = document.getElementById('confirmUndoMessage');
            message.textContent = 'Are you sure you want to undo this reservation?';
            modal.style.display = 'flex';

            const closeModal = document.getElementById('closeUndoModal');
            const noBtn = document.getElementById('confirmUndoNo');
            const yesBtn = document.getElementById('confirmUndoYes');

            function closeConfirmModal() {
                modal.style.display = 'none';
                modal.classList.remove('open');
                closeModal.removeEventListener('click', closeConfirmModal);
                noBtn.removeEventListener('click', closeConfirmModal);
                yesBtn.removeEventListener('click', handleConfirm);
            }

            function handleConfirm() {
                closeConfirmModal();
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

            closeModal.addEventListener('click', closeConfirmModal);
            noBtn.addEventListener('click', closeConfirmModal);
            yesBtn.addEventListener('click', handleConfirm);
            window.addEventListener('click', function handler(e) {
                if (e.target === modal) {
                    closeConfirmModal();
                    window.removeEventListener('click', handler);
                }
            });
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
