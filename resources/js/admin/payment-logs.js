// Payment Logs - status updates & receipt modal

document.addEventListener('DOMContentLoaded', function () {

    // ── Receipt Modal (image or PDF) ──
    const receiptModal = document.getElementById('receiptModal');
    const closeReceiptBtn = document.getElementById('closeReceiptModal');
    const receiptImage = document.getElementById('receiptImage1');
    const receiptFrame = document.getElementById('receiptFrame1');
    const receiptMissing = document.getElementById('receiptMissing');

    function openReceipt(url) {
        if (receiptImage) { receiptImage.style.display = 'none'; receiptImage.src = ''; }
        if (receiptFrame) { receiptFrame.style.display = 'none'; receiptFrame.src = ''; }
        if (receiptMissing) receiptMissing.style.display = 'none';

        if (url && /\.pdf($|\?)/i.test(url)) {
            if (receiptFrame) { receiptFrame.src = url; receiptFrame.style.display = 'block'; }
        } else if (url && receiptImage) {
            // If the image fails to load (e.g. the file was lost from
            // ephemeral storage on Railway), show a clear message instead
            // of a blank image.
            receiptImage.onerror = function () {
                receiptImage.style.display = 'none';
                if (receiptMissing) receiptMissing.style.display = 'block';
            };
            receiptImage.src = url;
            receiptImage.style.display = 'block';
        } else if (receiptMissing) {
            receiptMissing.style.display = 'block';
        }

        receiptModal.style.display = 'flex';
        receiptModal.classList.add('open');
    }

    function closeReceipt() {
        receiptModal.style.display = 'none';
        receiptModal.classList.remove('open');
        if (receiptImage) { receiptImage.onerror = null; receiptImage.src = ''; }
        if (receiptFrame) receiptFrame.src = '';
        if (receiptMissing) receiptMissing.style.display = 'none';
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
    // Rejected also allows an optional note. Pending saves directly with no
    // email and no modal.
    const statusModal = document.getElementById('statusModal');
    const statusModalMessage = document.getElementById('statusModalMessage');
    const statusModalNoteWrapper = document.getElementById('statusModalNoteWrapper');
    const statusModalNote = document.getElementById('statusModalNote');
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
            // The note field only appears for rejected.
            statusModalNoteWrapper.style.display = newStatus === 'rejected' ? 'block' : 'none';
        }
        if (statusModalNote) {
            statusModalNote.value = '';
        }
        if (statusModalMessage) {
            statusModalMessage.textContent = `Change this payment status to ${newStatus}? An email will be sent to the patron.`;
        }

        if (statusModal) {
            statusModal.style.display = 'flex';
            statusModal.classList.add('open');
        }
    }

    // Cancel or X restores the dropdown to the value it had before the change.
    function closeStatusModal(revert) {
        if (statusModal) {
            statusModal.style.display = 'none';
            statusModal.classList.remove('open');
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
            const adminNote = newStatus === 'rejected' && statusModalNote
                ? statusModalNote.value.trim()
                : null;

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
