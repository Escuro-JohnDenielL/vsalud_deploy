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

    // Period picker. Only used when a reservation cannot be created because the
    // inquiry carries no AM/PM period.
    const periodModal = document.getElementById("periodModal");
    const periodModalSelect = document.getElementById("periodModalSelect");
    const confirmPeriodBtn = document.getElementById("confirmPeriodBtn");
    const cancelPeriodBtn = document.getElementById("cancelPeriodBtn");

    if (periodModal) {
        let resolvePeriod = null;

        openPeriodModal = () =>
            new Promise((resolve) => {
                resolvePeriod = resolve;
                if (periodModalSelect) periodModalSelect.value = "AM";
                openModal(periodModal);
            });

        const finishPeriod = (value) => {
            closeModal(periodModal);
            const resolve = resolvePeriod;
            resolvePeriod = null;
            if (resolve) resolve(value);
        };

        confirmPeriodBtn?.addEventListener("click", () => {
            const value = (periodModalSelect?.value || "").trim().toUpperCase();
            finishPeriod(value === "AM" || value === "PM" ? value : null);
        });
        cancelPeriodBtn?.addEventListener("click", () => finishPeriod(null));
        window.addEventListener("keydown", (e) => {
            if (e.key === "Escape" && resolvePeriod) finishPeriod(null);
        });
    }

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
        if (!message) {
            window.vsNotify("Please enter a message.", "warning");
            return;
        }

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
        // Last value the server accepted, used to put the dropdown back when a
        // change is rejected.
        select.dataset.vsLastValue = select.value;

        select.addEventListener("change", function () {
            updateStatusColor(this);
            saveStatusToDatabase(this, this.value);
        });
    });
}

let openPeriodModal = null;

// Resolves with "AM", "PM" or null. Wired up when the modal exists on the page.
function askForPeriod() {
    return openPeriodModal ? openPeriodModal() : Promise.resolve(null);
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

function revertStatus(selectElement, status) {
    if (!selectElement) return;
    selectElement.value = status || "";
    updateStatusColor(selectElement);
}

function saveStatusToDatabase(selectElement, newStatus) {
    const row = selectElement.closest("tr");
    const inquiryId = row ? row.getAttribute("data-inquiry-id") : null;

    if (!inquiryId) return;

    // The value the server last accepted. A rejected change is rolled back to
    // this so the dropdown never claims a status the database does not have.
    const previousStatus = selectElement.dataset.vsLastValue || "";

    const send = (period) => {
        const body = { status: newStatus };
        if (period) body.period = period;

        return fetch(`/admin/inquiries/${inquiryId}/update-status`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
            body: JSON.stringify(body),
        });
    };

    const accept = (data) => {
        selectElement.dataset.vsLastValue = newStatus;

        if (data && data.mail_sent === false) {
            window.showAdminToast('Status saved, but the email to the patron failed to send.', 'warning');
            return;
        }

        window.showAdminToast('Inquiry status updated.', 'success');
    };

    const reject = (data) => {
        revertStatus(selectElement, previousStatus);
        window.showAdminToast(
            (data && data.message) || 'Failed to update status.',
            'error'
        );
    };

    send()
        .then(async (response) => {
            const data = await response.json().catch(() => ({}));

            // 422 with needs_period: the inquiry has no period, so the venue
            // cannot be reserved until the admin picks one.
            if (response.status === 422 && data.needs_period) {
                const period = await askForPeriod();

                if (!period) {
                    revertStatus(selectElement, previousStatus);
                    return;
                }

                const retry = await send(period);
                const retryData = await retry.json().catch(() => ({}));

                if (!retry.ok || !retryData.success) {
                    reject(retryData);
                    return;
                }

                accept(retryData);
                return;
            }

            // 409 when the venue was taken, 503 when the slot could not be
            // secured. Either way the status was not saved.
            if (!response.ok || !data.success) {
                reject(data);
                return;
            }

            accept(data);
        })
        .catch((error) => {
            console.error("Error:", error.message);
            reject(null);
        });
}
