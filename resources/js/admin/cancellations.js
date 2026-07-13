document.addEventListener("DOMContentLoaded", () => {
    // Make closeModal globally accessible for inline onclick usage
    window.closeModal = function (modalId) {
        document.getElementById(modalId).style.display = "none";
        document.getElementById(modalId).classList.remove('open');
    };

    function showToast(message, type) {
        const toast = document.getElementById("toast");
        toast.textContent = message;
        toast.className = `toast toast-${type}`;
        toast.style.display = "block";
        setTimeout(() => {
            toast.style.display = "none";
        }, 5000);
    }

    // Open approve modal
    document.querySelectorAll(".btn-approve[data-request-id]").forEach((btn) => {
        btn.addEventListener("click", () => {
            document.getElementById("approveRequestId").value = btn.dataset.requestId;
            document.getElementById("approveModal").style.display = "flex";
            document.getElementById("approveModal").classList.add('open');
        });
    });

    // Open deny modal
    document.querySelectorAll(".btn-deny[data-request-id]").forEach((btn) => {
        btn.addEventListener("click", () => {
            document.getElementById("denyRequestId").value = btn.dataset.requestId;
            document.getElementById("denyModal").style.display = "flex";
            document.getElementById("denyModal").classList.add('open');
        });
    });

    // Close modals when clicking outside
    window.addEventListener("click", (e) => {
        if (e.target.classList.contains("modal")) {
            e.target.style.display = "none";
            e.target.classList.remove('open');
        }
    });

    // Approve form
    document.getElementById("approveForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        const requestId = document.getElementById("approveRequestId").value;
        const adminNote = document.getElementById("approveNote").value.trim();
        const csrf = document.querySelector('input[name="_token"]').value;

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
                showToast(data.message, "success");
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message, "error");
            }
        } catch (err) {
            showToast("An error occurred.", "error");
            console.error(err);
        }
    });

    // Deny form
    document.getElementById("denyForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        const requestId = document.getElementById("denyRequestId").value;
        const adminNote = document.getElementById("denyNote").value.trim();
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
                showToast(data.message, "success");
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(data.message, "error");
            }
        } catch (err) {
            showToast("An error occurred.", "error");
            console.error(err);
        }
    });

    // Close modal buttons
    document.querySelectorAll(".close-btn, .admin-btn-ghost").forEach((btn) => {
        btn.addEventListener("click", () => {
            document.querySelectorAll(".modal").forEach((m) => {
                m.style.display = "none";
                m.classList.remove("open");
            });
        });
    });
});
