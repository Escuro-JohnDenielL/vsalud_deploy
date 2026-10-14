document.addEventListener("DOMContentLoaded", () => {
    // Make closeModal globally accessible for inline onclick usage
    window.closeModal = function (modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;
        window.vsModalCloseAfterFade(modal, () => {
            modal.style.display = "none";
            modal.classList.remove('open');
        });
    };

    // Open approve modal
    document.querySelectorAll(".btn-approve[data-request-id]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const modal = document.getElementById("approveModal");
            document.getElementById("approveRequestId").value = btn.dataset.requestId;
            window.vsModalPrepareOpen(modal);
            modal.style.display = "flex";
            modal.classList.add('open');
        });
    });

    // Open deny modal
    document.querySelectorAll(".btn-deny[data-request-id]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const modal = document.getElementById("denyModal");
            document.getElementById("denyRequestId").value = btn.dataset.requestId;
            window.vsModalPrepareOpen(modal);
            modal.style.display = "flex";
            modal.classList.add('open');
        });
    });

    // Close modals when clicking outside
    window.addEventListener("click", (e) => {
        if (e.target.classList.contains("modal")) {
            window.vsModalCloseAfterFade(e.target, () => {
                e.target.style.display = "none";
                e.target.classList.remove('open');
            });
        }
    });

    // Mirrors App\Support\AuditNote::MIN_LENGTH. A decision needs a reason.
    const MIN_REASON_LENGTH = 5;

    // Approve form
    document.getElementById("approveForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        const requestId = document.getElementById("approveRequestId").value;
        const adminNote = document.getElementById("approveNote").value.trim();
        const csrf = document.querySelector('input[name="_token"]').value;

        if (adminNote.length < MIN_REASON_LENGTH) {
            window.showAdminToast(`Add a reason of at least ${MIN_REASON_LENGTH} characters.`, 'error');
            document.getElementById("approveNote").focus();
            return;
        }

        try {
            const response = await fetch(`/admin/cancellations/${requestId}/approve`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf,
                },
                body: JSON.stringify({ admin_note: adminNote }),
            });

            const data = await response.json();

            if (data.success) {
                if (data.mail_sent === false) {
                    window.showAdminToast('Status saved, but the email to the patron failed to send.', 'warning');
                } else {
                    window.showAdminToast(data.message, 'success');
                }
                setTimeout(() => location.reload(), 1500);
            } else {
                window.showAdminToast(data.message, 'error');
            }
        } catch (err) {
            window.showAdminToast('An error occurred.', 'error');
            console.error(err);
        }
    });

    // Deny form
    document.getElementById("denyForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        const requestId = document.getElementById("denyRequestId").value;
        const adminNote = document.getElementById("denyNote").value.trim();

        if (adminNote.length < MIN_REASON_LENGTH) {
            window.showAdminToast(`Add a reason of at least ${MIN_REASON_LENGTH} characters.`, 'error');
            document.getElementById("denyNote").focus();
            return;
        }
        const csrf = document.querySelector('input[name="_token"]').value;

        try {
            const response = await fetch(`/admin/cancellations/${requestId}/deny`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf,
                },
                body: JSON.stringify({ admin_note: adminNote }),
            });

            const data = await response.json();

            if (data.success) {
                if (data.mail_sent === false) {
                    window.showAdminToast('Status saved, but the email to the patron failed to send.', 'warning');
                } else {
                    window.showAdminToast(data.message, 'success');
                }
                setTimeout(() => location.reload(), 1500);
            } else {
                window.showAdminToast(data.message, 'error');
            }
        } catch (err) {
            window.showAdminToast('An error occurred.', 'error');
            console.error(err);
        }
    });

    // Close modal buttons
    document.querySelectorAll(".close-btn, .admin-btn-ghost").forEach((btn) => {
        btn.addEventListener("click", () => {
            document.querySelectorAll(".modal").forEach((m) => {
                window.vsModalCloseAfterFade(m, () => {
                    m.style.display = "none";
                    m.classList.remove("open");
                });
            });
        });
    });
});
