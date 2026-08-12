document.addEventListener("DOMContentLoaded", function () {
    applyStatusColors();

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

    // View Inquiry modal
    const viewInquiryModal = document.getElementById("viewInquiryModal");
    const closeViewInquiryModal = document.getElementById("closeViewInquiryModal");

    function escHtml(str) {
        if (!str) return str;
        const div = document.createElement("div");
        div.textContent = str;
        return div.innerHTML;
    }

    function openViewInquiryModal(btn) {
        document.getElementById("view-name").textContent = btn.dataset.name || "N/A";
        const emailEl = document.getElementById("view-email");
        const email = btn.dataset.email || "";
        emailEl.innerHTML = '<a href="mailto:' + encodeURIComponent(email) + '">' + escHtml(email) + '</a>';
        document.getElementById("view-contact").textContent = btn.dataset.contact || "-";
        document.getElementById("view-code").textContent = btn.dataset.code || "-";
        document.getElementById("view-date").textContent = btn.dataset.date || "-";
        document.getElementById("view-time").textContent = btn.dataset.time || "-";
        document.getElementById("view-venue").textContent = btn.dataset.venue || "-";
        document.getElementById("view-event-type").textContent = btn.dataset.eventType || "-";
        document.getElementById("view-theme").textContent = btn.dataset.theme || "-";
        document.getElementById("view-status").textContent = btn.dataset.status || "Pending";
        document.getElementById("view-message").textContent = btn.dataset.message || "No message provided.";
        viewInquiryModal.style.display = "flex";
        viewInquiryModal.classList.add("open");
    }

    function closeViewInquiryModalFn() {
        viewInquiryModal.style.display = "none";
        viewInquiryModal.classList.remove("open");
    }

    closeViewInquiryModal.addEventListener("click", closeViewInquiryModalFn);
    window.addEventListener("click", (e) => {
        if (e.target === viewInquiryModal) closeViewInquiryModalFn();
    });

    document.querySelectorAll(".view-inquiry-btn").forEach((button) => {
        button.addEventListener("click", function () {
            openViewInquiryModal(this);
        });
    });

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
