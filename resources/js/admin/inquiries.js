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
    const modalCloseTimers = new WeakMap();

    function openModal(modal) {
        const closeTimer = modalCloseTimers.get(modal);
        if (closeTimer) window.clearTimeout(closeTimer);
        modalCloseTimers.delete(modal);
        modal.classList.remove("vs-modal--closing");
        modal.style.display = "flex";
        modal.classList.add("open");
    }

    function closeModal(modal) {
        if (modal.classList.contains("vs-modal--closing")) return;

        const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        if (reduceMotion) {
            modal.style.display = "none";
            modal.classList.remove("open", "vs-modal--closing");
            return;
        }

        modal.classList.add("vs-modal--closing");
        const durationValue = getComputedStyle(document.documentElement)
            .getPropertyValue("--vs-duration-base")
            .trim();
        const duration = Number.parseFloat(durationValue) || 200;
        const durationMs = durationValue.endsWith("ms") ? duration : duration * 1000;
        const closeTimer = window.setTimeout(() => {
            modal.style.display = "none";
            modal.classList.remove("open", "vs-modal--closing");
            modalCloseTimers.delete(modal);
        }, durationMs);
        modalCloseTimers.set(modal, closeTimer);
    }

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
        openModal(viewInquiryModal);
    }

    function closeViewInquiryModalFn() {
        closeModal(viewInquiryModal);
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
        openModal(replyModal);
    }

    cancelReplyBtn.addEventListener("click", () => {
        closeModal(replyModal);
    });

    closeReplyModal.addEventListener("click", () => {
        closeModal(replyModal);
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
                if (data.success && data.mail_sent === false) {
                    window.showAdminToast('Reply could not be emailed to the patron.', 'warning');
                } else if (data.success) {
                    window.showAdminToast(data.message || 'Reply sent successfully.', 'success');
                } else {
                    window.showAdminToast(data.message || 'Failed to send reply.', 'error');
                }
                closeModal(replyModal);
            })
            .catch((err) => {
                console.error("Error:", err);
                window.showAdminToast('Failed to send reply.', 'error');
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
                aiDraftStatus.textContent = "Draft generated. Review and edit before sending.";
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
            aiDraftLabel.textContent = "AI Draft Reply";
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
    document.querySelectorAll(".vs-status-select").forEach((select) => {
        updateStatusColor(select);
        select.addEventListener("change", function () {
            updateStatusColor(this);
            saveStatusToDatabase(this, this.value);
        });
    });
}

function updateStatusColor(select) {
    const value = select.value;
    select.classList.remove(
        "vs-status-select--pending",
        "vs-status-select--in-progress",
        "vs-status-select--completed",
        "vs-status-select--cancelled",
        "vs-status-select--neutral"
    );

    switch (value) {
        case "Pending":
            select.classList.add("vs-status-select--pending");
            break;
        case "In Progress":
            select.classList.add("vs-status-select--in-progress");
            break;
        case "Completed":
            select.classList.add("vs-status-select--completed");
            break;
        case "Cancelled":
            select.classList.add("vs-status-select--cancelled");
            break;
        default:
            select.classList.add("vs-status-select--neutral");
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
                if (data.mail_sent === false) {
                    window.showAdminToast('Status saved, but the email to the patron failed to send.', 'warning');
                } else {
                    console.log("Status updated successfully.");
                }
            } else {
                console.error("Failed to update status:", data.message);
                window.showAdminToast(data.message || 'Failed to update status.', 'error');
            }
        })
        .catch((error) => {
            console.error("Error:", error.message);
        });
}
