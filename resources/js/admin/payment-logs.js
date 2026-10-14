// Payment Logs - status updates & receipt modal

document.addEventListener('DOMContentLoaded', function () {

    // The wide page is centered with a transform, which creates a stacking context.
    // Keep fixed dialogs at the document root so their backdrop covers the navbar too.
    document.querySelectorAll('.modal').forEach(modal => {
        document.body.appendChild(modal);
    });

    const paymentInfoModal = document.getElementById('paymentInfoModal');
    const paymentInfoOpen = document.getElementById('paymentInfoOpen');
    const paymentInfoClose = paymentInfoModal?.querySelector('.js-modal-close');
    let activePaymentInfoTrigger = null;

    paymentInfoOpen?.addEventListener('click', function () {
        window.vsModalPrepareOpen(paymentInfoModal);
        paymentInfoModal.classList.add('open');
        paymentInfoModal.setAttribute('aria-hidden', 'false');
        paymentInfoClose?.focus();
    });

    const closePaymentInfo = () => {
        if (!paymentInfoModal) return;
        paymentInfoModal.setAttribute('aria-hidden', 'true');
        window.vsModalCloseAfterFade(paymentInfoModal, () => {
            paymentInfoModal.classList.remove('open');
            paymentInfoOpen?.focus();
        });
    };

    paymentInfoClose?.addEventListener('click', closePaymentInfo);
    paymentInfoModal?.addEventListener('click', function (event) {
        if (event.target === paymentInfoModal) closePaymentInfo();
    });

    document.querySelectorAll('[data-payment-info-target]').forEach(button => {
        button.addEventListener('click', function () {
            const modal = document.getElementById(this.dataset.paymentInfoTarget);
            if (!modal) return;
            activePaymentInfoTrigger = this;
            window.vsModalPrepareOpen(modal);
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            modal.querySelector('.js-payment-modal-close')?.focus();
        });
    });

    document.querySelectorAll('.payment-details-modal').forEach(modal => {
        const close = () => {
            modal.setAttribute('aria-hidden', 'true');
            window.vsModalCloseAfterFade(modal, () => {
                modal.classList.remove('open');
                activePaymentInfoTrigger?.focus();
                activePaymentInfoTrigger = null;
            });
        };

        modal.querySelector('.js-payment-modal-close')?.addEventListener('click', close);
        modal.addEventListener('click', function (event) {
            if (event.target === modal) close();
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && paymentInfoModal?.classList.contains('open')) {
            closePaymentInfo();
        }
        if (event.key === 'Escape') {
            document.querySelector('.payment-details-modal.open .js-payment-modal-close')?.click();
        }
    });

    document.querySelectorAll('.payment-group-toggle, .payment-group-details-toggle').forEach(button => {
        button.addEventListener('click', function () {
            const groupId = this.getAttribute('aria-controls');
            const rows = Array.from(document.querySelectorAll('.payment-child-container'))
                .filter(row => row.dataset.parentGroup === groupId);
            const willExpand = this.getAttribute('aria-expanded') !== 'true';

            rows.forEach(row => {
                row.hidden = !willExpand;
            });
            document.querySelectorAll(`[aria-controls="${groupId}"]`).forEach(toggle => {
                toggle.setAttribute('aria-expanded', String(willExpand));
            });

            const indicator = this.querySelector('.payment-group-indicator');
            if (indicator) indicator.textContent = willExpand ? '-' : '+';

            const detailsToggle = document.querySelector(`.payment-group-details-toggle[aria-controls="${groupId}"]`);
            if (detailsToggle) {
                detailsToggle.textContent = willExpand
                    ? 'Hide payment details'
                    : `View ${detailsToggle.dataset.paymentCount} payments`;
            }
        });
    });

    // ── Receipt Modal (image or PDF) ──
    const receiptModal = document.getElementById('receiptModal');
    const closeReceiptBtn = document.getElementById('closeReceiptModal');
    const receiptImage = document.getElementById('receiptImage1');
    const receiptFrame = document.getElementById('receiptFrame1');
    const receiptMissing = document.getElementById('receiptMissing');

    function openReceipt(url) {
        if (receiptImage) { receiptImage.hidden = true; receiptImage.src = ''; }
        if (receiptFrame) { receiptFrame.hidden = true; receiptFrame.src = ''; }
        if (receiptMissing) receiptMissing.hidden = true;

        if (url && /\.pdf($|\?)/i.test(url)) {
            if (receiptFrame) { receiptFrame.src = url; receiptFrame.hidden = false; }
        } else if (url && receiptImage) {
            // If the image fails to load (e.g. the file was lost from
            // ephemeral storage on Railway), show a clear message instead
            // of a blank image.
            receiptImage.onerror = function () {
                receiptImage.hidden = true;
                if (receiptMissing) receiptMissing.hidden = false;
            };
            receiptImage.src = url;
            receiptImage.hidden = false;
        } else if (receiptMissing) {
            receiptMissing.hidden = false;
        }

        window.vsModalPrepareOpen(receiptModal);
        receiptModal.classList.add('open');
    }

    function closeReceipt() {
        window.vsModalCloseAfterFade(receiptModal, () => {
            receiptModal.classList.remove('open');
            if (receiptImage) { receiptImage.onerror = null; receiptImage.src = ''; }
            if (receiptFrame) receiptFrame.src = '';
            if (receiptMissing) receiptMissing.hidden = true;
        });
    }

    document.querySelectorAll('.receipt-link1').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            openReceipt(this.dataset.receipt);
        });
    });

    if (closeReceiptBtn) {
        closeReceiptBtn.addEventListener('click', closeReceipt);
    }

    window.addEventListener('click', function (e) {
        if (e.target === receiptModal) closeReceipt();
    });

    // Payment Status Update
    // Approved, rejected, and refunded go through a confirm modal first.
    // Rejected and refunded need a reason; pending saves directly with no
    // email and no modal.
    const statusModal = document.getElementById('statusModal');
    const statusModalMessage = document.getElementById('statusModalMessage');
    const statusModalNoteWrapper = document.getElementById('statusModalNoteWrapper');
    const statusModalNote = document.getElementById('statusModalNote');

    // Mirrors App\Support\AuditNote::MIN_LENGTH.
    const MIN_STATUS_REASON_LENGTH = 5;
    const confirmStatusBtn = document.getElementById('confirmStatusModal');
    const cancelStatusBtn = document.getElementById('cancelStatusModal');
    const closeStatusBtn = document.getElementById('closeStatusModal');

    // The row and status waiting for confirmation.
    let pendingSelect = null;
    let pendingPaymentId = null;
    let pendingStatus = null;

    function sendStatusUpdate(select, paymentId, newStatus, adminNote) {
        fetch(`/admin/payments/${paymentId}/status`, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ status: newStatus, admin_note: adminNote })
        })
        .then(async res => ({ response: res, data: await res.json() }))
        .then(({ response, data }) => {
            if (response.ok && data.success) {
                if (select) select.dataset.previousStatus = newStatus;
                if (data.mail_sent === false) {
                    window.showAdminToast('Status saved, but the email to the patron failed to send.', 'warning');
                } else {
                    window.showAdminToast(data.message || 'Payment status updated successfully.', 'success');
                }
            } else {
                // Roll the dropdown back to the value it had before the change.
                if (select) select.value = select.dataset.previousStatus;
                window.showAdminToast(data.message || 'Failed to update status.', 'error');
            }
        })
        .catch(() => {
            if (select) select.value = select.dataset.previousStatus;
            window.showAdminToast('Network error. Please try again.', 'error');
        });
    }

    function openStatusModal(select, newStatus) {
        pendingSelect = select;
        pendingPaymentId = select.dataset.paymentId;
        pendingStatus = newStatus;

        if (statusModalNoteWrapper) {
            // The note is required for rejected and refunded; both are email
            // notifications the patron reads and both need a stated reason.
            statusModalNoteWrapper.hidden = !["rejected", "refunded"].includes(newStatus);
        }
        if (statusModalNote) {
            statusModalNote.value = '';
        }
        if (statusModalMessage) {
            statusModalMessage.textContent = `Change this payment status to ${newStatus}? An email will be sent to the patron.`;
        }

        if (statusModal) {
            window.vsModalPrepareOpen(statusModal);
            statusModal.classList.add('open');
        }
    }

    // Cancel or X restores the dropdown to the value it had before the change.
    function closeStatusModal(revert) {
        if (statusModal) {
            window.vsModalCloseAfterFade(statusModal, () => {
                statusModal.classList.remove('open');
                if (revert && pendingSelect) {
                    pendingSelect.value = pendingSelect.dataset.previousStatus;
                }
                pendingSelect = null;
                pendingPaymentId = null;
                pendingStatus = null;
                if (statusModalNote) statusModalNote.value = '';
            });
            return;
        }
        if (revert && pendingSelect) {
            pendingSelect.value = pendingSelect.dataset.previousStatus;
        }
        pendingSelect = null;
        pendingPaymentId = null;
        pendingStatus = null;
        if (statusModalNote) statusModalNote.value = '';
    }

    document.querySelectorAll('.payment-status-dropdown').forEach(select => {
        // Track the saved value so a cancel or a failed request can restore it.
        select.dataset.previousStatus = select.value;

        select.addEventListener('change', function () {
            const newStatus = this.value;

            if (newStatus === 'pending') {
                // Pending sends no email, so skip the confirmation step.
                sendStatusUpdate(this, this.dataset.paymentId, newStatus, null);
                return;
            }

            openStatusModal(this, newStatus);
        });
    });

    if (confirmStatusBtn) {
        confirmStatusBtn.addEventListener('click', function () {
            const select = pendingSelect;
            const paymentId = pendingPaymentId;
            const newStatus = pendingStatus;
            const needsNote = newStatus === "rejected" || newStatus === "refunded";
            const adminNote = needsNote && statusModalNote
                ? statusModalNote.value.trim()
                : null;

            // Mirrors App\Support\AuditNote: a refund has to be explained.
            if (newStatus === "refunded" && (adminNote || "").length < MIN_STATUS_REASON_LENGTH) {
                if (statusModalMessage) {
                    statusModalMessage.textContent = `A reason of at least ${MIN_STATUS_REASON_LENGTH} characters is required to mark this payment as refunded.`;
                }
                statusModalNote?.focus();
                return;
            }

            closeStatusModal(false);
            sendStatusUpdate(select, paymentId, newStatus, adminNote);
        });
    }

    if (cancelStatusBtn) {
        cancelStatusBtn.addEventListener('click', function () {
            closeStatusModal(true);
        });
    }

    if (closeStatusBtn) {
        closeStatusBtn.addEventListener('click', function () {
            closeStatusModal(true);
        });
    }

    window.addEventListener('click', function (e) {
        if (e.target === statusModal) closeStatusModal(true);
    });
});
