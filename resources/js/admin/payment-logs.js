// Payment Logs — status updates & receipt modal

document.addEventListener('DOMContentLoaded', function () {

    // ── Receipt Modal ──
    const receiptModal = document.getElementById('receiptModal');
    const closeReceiptBtn = document.getElementById('closeReceiptModal');
    const receiptImage = document.getElementById('receiptImage1');

    document.querySelectorAll('.receipt-link1').forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const src = this.dataset.receipt;
            if (src) {
                receiptImage.src = src;
                receiptModal.style.display = 'flex';
                receiptModal.classList.add('open');
            }
        });
    });

    if (closeReceiptBtn) {
        closeReceiptBtn.addEventListener('click', function () {
            receiptModal.style.display = 'none';
            receiptModal.classList.remove('open');
            receiptImage.src = '';
        });
    }

    window.addEventListener('click', function (e) {
        if (e.target === receiptModal) {
            receiptModal.style.display = 'none';
            receiptModal.classList.remove('open');
            receiptImage.src = '';
        }
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
