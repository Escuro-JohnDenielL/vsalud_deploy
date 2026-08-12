// Payment Logs — status updates & receipt modal

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
        if (receiptImage) receiptImage.src = '';
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

    // ── Payment Status Update ──
    document.querySelectorAll('.payment-status-dropdown').forEach(select => {
        select.addEventListener('change', function () {
            const paymentId = this.dataset.paymentId;
            const newStatus = this.value;

            fetch(`/admin/payments/${paymentId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('Payment status updated successfully.', 'success');
                } else {
                    showToast(data.message || 'Failed to update status.', 'error');
                }
            })
            .catch(() => {
                showToast('Network error. Please try again.', 'error');
            });
        });
    });

    function showToast(message, type) {
        const toast = document.getElementById('toast');
        if (!toast) return;
        toast.textContent = message;
        toast.className = 'toast toast-' + type;
        toast.style.display = 'block';
        setTimeout(() => { toast.style.display = 'none'; }, 4000);
    }
});
